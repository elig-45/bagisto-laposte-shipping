<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Elig45\LaPosteShipping\Models\ProductProfile;
use Elig45\LaPosteShipping\Models\RateCard;
use Elig45\LaPosteShipping\Services\CsvRates;
use Elig45\LaPosteShipping\Services\Decimal;
use Elig45\LaPosteShipping\Services\RateResolver;
use Elig45\LaPosteShipping\Services\ServiceCatalog;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminController extends Controller
{
    public function index(): View
    {
        $this->canView();
        $services = ServiceCatalog::all();
        foreach ($services as $serviceCode => $service) {
            $services[$serviceCode]['default_csv'] = ServiceCatalog::defaultCsv($serviceCode);
        }

        return view('laposte::admin', [
            'cards' => RateCard::with('tiers')->orderByDesc('effective_from')->paginate(25),
            'profiles' => ProductProfile::orderBy('product_id')->limit(100)->get(),
            'services' => $services,
        ]);
    }

    public function store(Request $request, CsvRates $csv): RedirectResponse
    {
        $this->canManage();
        $data = $this->validateGrid($request);
        $rows = $this->decodeCsv($csv, $data['tiers_csv'], $data['service_code']);
        $this->guardEffectiveDate($data['effective_from']);

        DB::transaction(function () use ($data, $rows): void {
            $duplicate = RateCard::query()->where('service_code', $data['service_code'])
                ->where('zone', 'metropolitan_france')
                ->where('effective_from', $data['effective_from'])
                ->lockForUpdate()->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['effective_from' => trans('laposte::app.duplicate_date')]);
            }
            $card = RateCard::create([
                'service_code' => $data['service_code'],
                'zone' => 'metropolitan_france',
                'source_url' => $data['source_url'],
                'effective_from' => $data['effective_from'],
                'enabled' => true,
            ]);
            $card->tiers()->createMany($rows);
        });
        return back()->with('success', trans('laposte::app.saved'));
    }

    public function update(Request $request, RateCard $card, CsvRates $csv): RedirectResponse
    {
        $this->canManage();
        $this->guardFutureCard($card);
        $data = $request->validate([
            'source_url' => ['required', 'url', 'max:500', 'regex:/^https:\/\//'],
            'enabled' => ['required', 'boolean'],
            'tiers_csv' => ['required', 'string', 'max:32768'],
        ]);
        $tiers = $this->decodeCsv($csv, $data['tiers_csv'], $card->service_code);
        DB::transaction(function () use ($card, $data, $tiers): void {
            $card->update(['source_url' => $data['source_url'], 'enabled' => (bool) $data['enabled']]);
            $card->tiers()->delete();
            $card->tiers()->createMany($tiers);
        });
        return back()->with('success', trans('laposte::app.saved'));
    }

    public function destroy(RateCard $card): RedirectResponse
    {
        $this->canManage();
        $this->guardFutureCard($card);
        $card->delete();
        return back()->with('success', trans('laposte::app.deleted'));
    }

    public function import(Request $request, CsvRates $csv): RedirectResponse
    {
        $this->canManage();
        $request->validate([
            'service_code' => ['required', Rule::in(array_keys(ServiceCatalog::all()))],
            'effective_from' => 'required|date_format:Y-m-d|after:today',
            'source_url' => ['required', 'url', 'max:500', 'regex:/^https:\/\//'],
            'csv' => 'required|file|max:64|mimes:csv,txt',
        ]);
        $raw = file_get_contents($request->file('csv')->getRealPath());
        $this->decodeCsv($csv, (string) $raw, (string) $request->input('service_code'));
        $request->merge(['tiers_csv' => $raw]);
        return $this->store($request, $csv);
    }

    public function export(RateCard $card): StreamedResponse
    {
        $this->canView();
        return response()->streamDownload(function () use ($card): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['max_weight_g', 'classic_cents', 'online_cents', 'enabled']);
            foreach ($card->tiers as $tier) {
                fputcsv($handle, [$tier->max_weight_g, $tier->classic_cents, $tier->online_cents, (int) $tier->enabled]);
            }
            fclose($handle);
        }, 'laposte-'.$card->service_code.'-'.$card->effective_from->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $this->canManage();
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'eligible' => 'required|boolean',
            'length_mm' => 'required|integer|between:1,1500',
            'width_mm' => 'required|integer|between:1,1500',
            'thickness_mm' => 'required|integer|between:1,1500',
            'packing_rule' => ['required', Rule::in(['flat_stack'])],
        ]);
        ProductProfile::updateOrCreate(['product_id' => $data['product_id']], $data);
        return back()->with('success', trans('laposte::app.saved'));
    }

    public function destroyProfile(ProductProfile $profile): RedirectResponse
    {
        $this->canManage();
        $profile->delete();
        return back()->with('success', trans('laposte::app.deleted'));
    }

    public function preview(Request $request, RateResolver $rates): RedirectResponse
    {
        $this->canView();
        $data = $request->validate([
            'service_code' => ['required', Rule::in(array_keys(ServiceCatalog::all()))],
            'weight_g' => ['required', 'regex:/^\d{1,5}(\.\d{1,3})?$/'],
            'price_mode' => ['required', Rule::in(['classic', 'online'])],
        ]);
        try {
            $weight = Decimal::gramsMg($data['weight_g']);
            $rate = $rates->resolveForService($data['service_code'], $weight, $data['price_mode']);
        } catch (InvalidArgumentException) {
            $rate = null;
        }
        return back()->with('preview', $rate
            ? number_format($rate['cents'] / 100, 2, ',', ' ').' €'
            : trans('laposte::app.unavailable'));
    }

    private function canView(): void
    {
        abort_unless(bouncer()->hasPermission('sales.laposte'), 403);
    }

    private function canManage(): void
    {
        abort_unless(bouncer()->hasPermission('sales.laposte.manage'), 403);
    }

    private function validateGrid(Request $request): array
    {
        return $request->validate([
            'service_code' => ['required', Rule::in(array_keys(ServiceCatalog::all()))],
            'effective_from' => 'required|date_format:Y-m-d|after:today',
            'source_url' => ['required', 'url', 'max:500', 'regex:/^https:\/\//'],
            'tiers_csv' => 'required|string|max:32768',
        ]);
    }

    private function decodeCsv(CsvRates $csv, string $content, string $serviceCode): array
    {
        try {
            return $csv->parse($content, $serviceCode);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['tiers_csv' => $e->getMessage()]);
        }
    }

    private function guardEffectiveDate(string $date): void
    {
        abort_unless($date > now()->toDateString(), 422, trans('laposte::app.historical_immutable'));
    }

    private function guardFutureCard(RateCard $card): void
    {
        abort_unless($card->effective_from->isFuture(), 422, trans('laposte::app.historical_immutable'));
    }
}
