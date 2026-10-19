<x-admin::layouts>
    <x-slot:title>@lang('laposte::app.admin_section')</x-slot:title>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">@lang('laposte::app.admin_section')</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.safety')</p>
        </div>
        <a class="secondary-button" href="{{ route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers']) }}">@lang('laposte::app.back')</a>
    </div>
    @if (session('success'))<p role="status" class="mt-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-green-800 dark:border-gray-700 dark:bg-gray-800 dark:text-green-200">{{ session('success') }}</p>@endif
    @if (session('preview'))<p role="status" class="mt-4 rounded border border-blue-200 bg-blue-50 px-3 py-2 text-blue-800 dark:border-gray-700 dark:bg-gray-800 dark:text-blue-200">{{ session('preview') }}</p>@endif
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-red-800 dark:border-gray-700 dark:bg-gray-800 dark:text-red-200">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="box-shadow mt-5 rounded border border-gray-200 bg-white p-5 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        <h2 class="mb-3 text-xl font-semibold dark:text-white">@lang('laposte::app.rate_cards')</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
                <thead class="bg-gray-50 text-gray-900 dark:bg-gray-800 dark:text-gray-100"><tr><th class="p-2">@lang('laposte::app.service')</th><th class="p-2">@lang('laposte::app.effective_from')</th><th class="p-2">Source</th><th class="p-2">@lang('laposte::app.rates')</th><th class="p-2">CSV</th></tr></thead>
                <tbody>
                @foreach($cards as $card)
                    <tr class="border-t border-gray-200 align-top dark:border-gray-700">
                        <td class="p-2">@lang('laposte::app.service_'.$card->service_code)</td>
                        <td class="p-2">{{ $card->effective_from->format('Y-m-d') }}</td>
                        <td class="p-2"><a class="text-blue-600 underline dark:text-blue-400" href="{{ $card->source_url }}" target="_blank" rel="noopener noreferrer">{{ parse_url($card->source_url, PHP_URL_HOST) }}</a></td>
                        <td class="p-2">
                            @foreach($card->tiers as $tier)
                                <div>{{ $tier->max_weight_g }} g — {{ number_format($tier->classic_cents / 100, 2, ',', ' ') }} € / {{ number_format($tier->online_cents / 100, 2, ',', ' ') }} € @if(!$tier->enabled)(@lang('laposte::app.disabled'))@endif</div>
                            @endforeach
                        </td>
                        <td class="p-2">
                            <a class="text-blue-600 underline dark:text-blue-400" href="{{ route('admin.laposte.rates.export', $card) }}">Export</a>
                            @if ($card->effective_from->isFuture() && bouncer()->hasPermission('sales.laposte.manage'))
                                <details class="mt-2"><summary class="cursor-pointer text-gray-700 dark:text-gray-200">@lang('laposte::app.edit')</summary>
                                    <form method="POST" action="{{ route('admin.laposte.rates.update', $card) }}" class="mt-2">
                                        @csrf @method('PUT')
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Source HTTPS<input name="source_url" type="url" required maxlength="500" value="{{ $card->source_url }}" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></label>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.status')
                                            <select name="enabled" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><option value="1" @selected($card->enabled)>On</option><option value="0" @selected(!$card->enabled)>Off</option></select>
                                        </label>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">CSV</label>
                                        <textarea class="w-full rounded border border-gray-300 bg-white p-2 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" rows="8" name="tiers_csv" required>max_weight_g,classic_cents,online_cents,enabled
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.service')<select id="laposte-grid-service" name="service_code" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required>
                @foreach($services as $serviceCode => $service)<option value="{{ $serviceCode }}" data-source="{{ $service['source_url'] }}" data-csv="{{ str_replace("\n", '|', $service['default_csv']) }}" @selected($serviceCode === 'lettre_verte_suivie')>@lang('laposte::app.service_'.$serviceCode)</option>@endforeach
            </select></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.effective_from')<input name="effective_from" type="date" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Source HTTPS<input id="laposte-grid-source" name="source_url" type="url" value="https://www.laposte.fr/tarif-lettre-suivie" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">CSV (max_weight_g,classic_cents,online_cents,enabled) — dernière tranche selon le service choisi</label>
            <textarea id="laposte-grid-csv" name="tiers_csv" rows="8" class="w-full rounded border border-gray-300 bg-white p-2 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required>{{ $services['lettre_verte_suivie']['default_csv'] }}</textarea>
            <button class="primary-button" type="submit">@lang('laposte::app.save')</button>
        </form>
        <script>
            document.getElementById('laposte-grid-service').addEventListener('change', function () {
                const option = this.options[this.selectedIndex];
                document.getElementById('laposte-grid-source').value = option.dataset.source;
                document.getElementById('laposte-grid-csv').value = option.dataset.csv.split('|').join('\n');
            });
        </script>
        <h3 class="mt-6 text-lg font-semibold dark:text-white">@lang('laposte::app.import')</h3>
        <form action="{{ route('admin.laposte.rates.import') }}" method="POST" enctype="multipart/form-data" class="mt-3 grid gap-3">@csrf
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.service')<select name="service_code" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required>
                @foreach($services as $serviceCode => $service)<option value="{{ $serviceCode }}">@lang('laposte::app.service_'.$serviceCode)</option>@endforeach
            </select></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.effective_from')<input name="effective_from" type="date" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Source HTTPS<input name="source_url" type="url" value="https://www.laposte.fr/tarif-lettre-suivie" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <input name="csv" type="file" accept=".csv,text/csv" class="text-sm text-gray-700 dark:text-gray-200" required>
            <button class="primary-button" type="submit">@lang('laposte::app.import')</button>
        </form>
        @endif
    </section>

    <section class="box-shadow mt-5 rounded border border-gray-200 bg-white p-5 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        <h2 class="text-xl font-semibold dark:text-white">@lang('laposte::app.preview')</h2>
        <p class="my-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.preview_notice')</p>
        <form method="POST" action="{{ route('admin.laposte.preview') }}" class="grid gap-3">@csrf
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.service')<select name="service_code" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required>
                @foreach($services as $serviceCode => $service)<option value="{{ $serviceCode }}">@lang('laposte::app.service_'.$serviceCode)</option>@endforeach
            </select></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.weight_g')<input name="weight_g" value="20" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.price_mode')<select name="price_mode" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><option value="classic">@lang('laposte::app.classic')</option><option value="online">@lang('laposte::app.online')</option></select></label>
            <button class="primary-button" type="submit">@lang('laposte::app.preview')</button>
        </form>
    </section>

    <section class="box-shadow mt-5 rounded border border-gray-200 bg-white p-5 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
        <h2 class="text-xl font-semibold dark:text-white">@lang('laposte::app.profiles')</h2>
        <p class="my-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.flat_rule')</p>
        <p class="my-2 text-sm text-gray-600 dark:text-gray-300">@lang('laposte::app.package_rules')</p>
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
            <thead class="bg-gray-50 text-gray-900 dark:bg-gray-800 dark:text-gray-100"><tr><th class="p-2">Product ID</th><th class="p-2">@lang('laposte::app.eligible')</th><th class="p-2">mm (L × W × T)</th><th class="p-2"></th></tr></thead>
            <tbody>
            @foreach ($profiles as $profile)
                <tr class="border-t border-gray-200 dark:border-gray-700"><td class="p-2">{{ $profile->product_id }}</td><td class="p-2">{{ $profile->eligible ? '✓' : '—' }}</td>
                    <td class="p-2">{{ $profile->length_mm }} × {{ $profile->width_mm }} × {{ $profile->thickness_mm }}</td>
                    <td class="p-2">@if(bouncer()->hasPermission('sales.laposte.manage'))<form method="POST" action="{{ route('admin.laposte.profiles.destroy', $profile) }}">@csrf @method('DELETE')<button class="secondary-button">@lang('laposte::app.delete')</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @if (bouncer()->hasPermission('sales.laposte.manage'))
        <h3 class="mt-5 text-lg font-semibold dark:text-white">@lang('laposte::app.profile_edit')</h3>
        <form method="POST" action="{{ route('admin.laposte.profiles.store') }}" class="mt-3 grid gap-3">@csrf
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Product ID <input name="product_id" min="1" type="number" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.eligible')<select name="eligible" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"><option value="1">Oui</option><option value="0">Non</option></select></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.length_mm')<input name="length_mm" min="1" max="1500" type="number" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.width_mm')<input name="width_mm" min="1" max="1500" type="number" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">@lang('laposte::app.thickness_mm')<input name="thickness_mm" min="1" max="1500" type="number" class="w-full rounded border border-gray-300 bg-white p-2 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required></label>
            <input type="hidden" name="packing_rule" value="flat_stack">
            <button class="primary-button" type="submit">@lang('laposte::app.save')</button>
        </form>
        @endif
    </section>
</x-admin::layouts>
