<x-admin::layouts>
    <x-slot:title>@lang('laposte::app.admin_section')</x-slot:title>
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">@lang('laposte::app.admin_section')</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.safety')</p>
        </div>
        <a class="secondary-button" href="{{ route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers']) }}">@lang('laposte::app.back')</a>
    </div>
    @if (session('success'))<p role="status" class="mt-4 text-green-700">{{ session('success') }}</p>@endif
    @if (session('preview'))<p role="status" class="mt-4 text-blue-700">{{ session('preview') }}</p>@endif
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded border p-3 text-red-700">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="box-shadow mt-5 rounded bg-white p-5 dark:bg-gray-900">
        <h2 class="mb-3 text-xl font-semibold dark:text-white">@lang('laposte::app.rate_cards')</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm dark:text-gray-200">
                <thead><tr><th class="p-2">@lang('laposte::app.effective_from')</th><th class="p-2">Source</th><th class="p-2">@lang('laposte::app.rates')</th><th class="p-2">CSV</th></tr></thead>
                <tbody>
                @foreach($cards as $card)
                    <tr class="border-t align-top">
                        <td class="p-2">{{ $card->effective_from->format('Y-m-d') }}</td>
                        <td class="p-2"><a class="text-blue-600 underline" href="{{ $card->source_url }}" target="_blank" rel="noopener noreferrer">{{ parse_url($card->source_url, PHP_URL_HOST) }}</a></td>
                        <td class="p-2">
                            @foreach($card->tiers as $tier)
                                <div>{{ $tier->max_weight_g }} g — {{ number_format($tier->classic_cents / 100, 2, ',', ' ') }} € / {{ number_format($tier->online_cents / 100, 2, ',', ' ') }} € @if(!$tier->enabled)(@lang('laposte::app.disabled'))@endif</div>
                            @endforeach
                        </td>
                        <td class="p-2">
                            <a class="text-blue-600 underline" href="{{ route('admin.laposte.rates.export', $card) }}">Export</a>
                            @if ($card->effective_from->isFuture() && bouncer()->hasPermission('sales.laposte.manage'))
                                <details class="mt-2"><summary>@lang('laposte::app.edit')</summary>
                                    <form method="POST" action="{{ route('admin.laposte.rates.update', $card) }}" class="mt-2">
                                        @csrf @method('PUT')
                                        <label class="block">Source HTTPS<input name="source_url" type="url" required maxlength="500" value="{{ $card->source_url }}" class="w-full rounded border p-2 dark:bg-gray-800"></label>
                                        <label class="block">@lang('laposte::app.status')
                                            <select name="enabled" class="w-full rounded border p-2 dark:bg-gray-800"><option value="1" @selected($card->enabled)>On</option><option value="0" @selected(!$card->enabled)>Off</option></select>
                                        </label>
                                        <label class="block">CSV</label>
                                        <textarea class="w-full rounded border p-2 font-mono text-xs dark:bg-gray-800" rows="8" name="tiers_csv" required>max_weight_g,classic_cents,online_cents,enabled
@foreach($card->tiers as $tier){{ $tier->max_weight_g }},{{ $tier->classic_cents }},{{ $tier->online_cents }},{{ (int)$tier->enabled }}
@endforeach</textarea>
                                        <button class="primary-button" type="submit">@lang('laposte::app.save')</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.laposte.rates.destroy', $card) }}" class="mt-3">@csrf @method('DELETE')<button class="secondary-button" type="submit">@lang('laposte::app.delete')</button></form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $cards->links() }}</div>
        @if(bouncer()->hasPermission('sales.laposte.manage'))
        <h3 class="mt-6 text-lg font-semibold dark:text-white">@lang('laposte::app.new_grid')</h3>
        <form method="POST" action="{{ route('admin.laposte.rates.store') }}" class="mt-3 grid gap-3">@csrf
            <label class="block">@lang('laposte::app.effective_from')<input name="effective_from" type="date" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">Source HTTPS<input name="source_url" type="url" value="https://www.laposte.fr/tarif-lettre-suivie" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">CSV (max_weight_g,classic_cents,online_cents,enabled)</label>
            <textarea name="tiers_csv" rows="8" class="w-full rounded border p-2 font-mono dark:bg-gray-800" required>max_weight_g,classic_cents,online_cents,enabled
20,202,199,1
100,360,347,1
250,574,568,1
500,791,771,1
1000,979,964,1
2000,1164,1156,1</textarea>
            <button class="primary-button" type="submit">@lang('laposte::app.save')</button>
        </form>
        <h3 class="mt-6 text-lg font-semibold dark:text-white">@lang('laposte::app.import')</h3>
        <form action="{{ route('admin.laposte.rates.import') }}" method="POST" enctype="multipart/form-data" class="mt-3 grid gap-3">@csrf
            <label class="block">@lang('laposte::app.effective_from')<input name="effective_from" type="date" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">Source HTTPS<input name="source_url" type="url" value="https://www.laposte.fr/tarif-lettre-suivie" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <input name="csv" type="file" accept=".csv,text/csv" required>
            <button class="primary-button" type="submit">@lang('laposte::app.import')</button>
        </form>
        @endif
    </section>

    <section class="box-shadow mt-5 rounded bg-white p-5 dark:bg-gray-900">
        <h2 class="text-xl font-semibold dark:text-white">@lang('laposte::app.preview')</h2>
        <p class="my-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.preview_notice')</p>
        <form method="POST" action="{{ route('admin.laposte.preview') }}" class="grid gap-3">@csrf
            <label class="block">@lang('laposte::app.weight_g')<input name="weight_g" value="20" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">@lang('laposte::app.price_mode')<select name="price_mode" class="w-full rounded border p-2 dark:bg-gray-800"><option value="classic">@lang('laposte::app.classic')</option><option value="online">@lang('laposte::app.online')</option></select></label>
            <button class="primary-button" type="submit">@lang('laposte::app.preview')</button>
        </form>
    </section>

    <section class="box-shadow mt-5 rounded bg-white p-5 dark:bg-gray-900">
        <h2 class="text-xl font-semibold dark:text-white">@lang('laposte::app.profiles')</h2>
        <p class="my-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.flat_rule')</p>
        <table class="w-full text-sm dark:text-gray-200">
            <tr><th>Product ID</th><th>@lang('laposte::app.eligible')</th><th>mm (L × W × T)</th><th></th></tr>
            @foreach ($profiles as $profile)
                <tr class="border-t"><td class="p-2">{{ $profile->product_id }}</td><td>{{ $profile->eligible ? '✓' : '—' }}</td>
                    <td>{{ $profile->length_mm }} × {{ $profile->width_mm }} × {{ $profile->thickness_mm }}</td>
                    <td>@if(bouncer()->hasPermission('sales.laposte.manage'))<form method="POST" action="{{ route('admin.laposte.profiles.destroy', $profile) }}">@csrf @method('DELETE')<button class="secondary-button">@lang('laposte::app.delete')</button></form>@endif</td>
                </tr>
            @endforeach
        </table>
        @if (bouncer()->hasPermission('sales.laposte.manage'))
        <h3 class="mt-5 text-lg font-semibold dark:text-white">@lang('laposte::app.profile_edit')</h3>
        <form method="POST" action="{{ route('admin.laposte.profiles.store') }}" class="mt-3 grid gap-3">@csrf
            <label class="block">Product ID <input name="product_id" min="1" type="number" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">@lang('laposte::app.eligible')<select name="eligible" class="w-full rounded border p-2 dark:bg-gray-800"><option value="1">Oui</option><option value="0">Non</option></select></label>
            <label class="block">@lang('laposte::app.length_mm')<input name="length_mm" min="1" max="600" type="number" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">@lang('laposte::app.width_mm')<input name="width_mm" min="1" max="600" type="number" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <label class="block">@lang('laposte::app.thickness_mm')<input name="thickness_mm" min="1" max="29" type="number" class="w-full rounded border p-2 dark:bg-gray-800" required></label>
            <input type="hidden" name="packing_rule" value="flat_stack">
            <button class="primary-button" type="submit">@lang('laposte::app.save')</button>
        </form>
        @endif
    </section>
</x-admin::layouts>
