@php
    $rows = $equipmentDriversSummary['rows'] ?? [];
    $totals = $equipmentDriversSummary['totals'] ?? [
        'company_total' => 0,
        'company_drivers' => 0,
        'external_drivers' => 0,
        'no_driver_count' => 0,
        'broken_count' => 0,
    ];
@endphp

<div class="space-y-6 p-4 sm:p-6">
    @if (count($rows) === 0)
        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-base font-black text-slate-900">لا توجد بيانات لعرض إجمالي المعدات والسائقين</p>
            <p class="mt-1 text-xs font-bold text-slate-500">قم باستيراد ملف توزيعة المعدات وملف السائقين ليتم الربط والحساب تلقائياً.</p>
        </div>
    @else
        {{-- Executive KPI Strip --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-2xl border-2 border-slate-900 bg-white p-4 text-center shadow-xs">
                <p class="text-xs font-black text-slate-600">إجمالي الشركة (المعدات)</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-slate-950 tabular-nums">{{ number_format((int) $totals['company_total']) }}</p>
            </div>
            <div class="rounded-2xl border-2 border-emerald-700 bg-emerald-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-emerald-900">سائقين على كفالة الشركة</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-emerald-700 tabular-nums">{{ number_format((int) $totals['company_drivers']) }}</p>
            </div>
            <div class="rounded-2xl border-2 border-sky-700 bg-sky-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-sky-900">سائقين عمالة خارجية</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-sky-700 tabular-nums">{{ number_format((int) $totals['external_drivers']) }}</p>
            </div>
            <div class="rounded-2xl border-2 border-amber-600 bg-amber-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-amber-900">معدات بدون سائق</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-amber-700 tabular-nums">{{ number_format((int) $totals['no_driver_count']) }}</p>
            </div>
            <div class="rounded-2xl border-2 border-rose-600 bg-rose-50/70 p-4 text-center shadow-xs">
                <p class="text-xs font-black text-rose-900">المعدات المتعطلة</p>
                <p class="mt-1 text-2xl sm:text-3xl font-black text-rose-700 tabular-nums">{{ number_format((int) $totals['broken_count']) }}</p>
            </div>
        </div>

        {{-- Main Executive Table --}}
        <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
            <div class="bg-slate-950 px-5 py-3.5 text-center text-base sm:text-lg font-black text-white">
                إجمالي المعدات والسائقين حسب نوع المعدة (إجمالي الشركة: {{ number_format((int) $totals['company_total']) }} معدة)
            </div>
            <div class="max-h-[74vh] overflow-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-xs sm:text-[13px] font-black text-white shadow-md">
                        <tr>
                            <th scope="col" class="sticky top-0 z-20 w-12 border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">نوع المعدة</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-800 px-4 py-3.5 text-center">إجمالي الشركة</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-emerald-700 bg-emerald-900 px-4 py-3.5 text-center text-emerald-100">سائقين على كفالة الشركة</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-sky-700 bg-sky-900 px-4 py-3.5 text-center text-sky-100">سائقين عمالة خارجية</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-amber-700 bg-amber-900 px-4 py-3.5 text-center text-amber-100">معدات بدون سائق</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-rose-700 bg-rose-900 px-4 py-3.5 text-center text-rose-100">المعدات المتعطلة</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($rows as $row)
                            <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-indigo-50/50 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-950">
                                    <a
                                        href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                        class="text-slate-950 hover:text-indigo-700 hover:underline"
                                    >
                                        {{ $row['type'] }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-50/80 px-4 py-3 text-center text-base font-black text-slate-950 tabular-nums">
                                    <a
                                        href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                        class="hover:text-indigo-700 hover:underline"
                                    >
                                        {{ number_format((int) $row['company_total']) }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/40 px-4 py-3 text-center text-base font-black text-emerald-800 tabular-nums">
                                    @if ($row['company_drivers'] > 0)
                                        <a
                                            href="{{ route('drivers.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                            class="hover:underline"
                                        >
                                            {{ number_format((int) $row['company_drivers']) }}
                                        </a>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-sky-50/40 px-4 py-3 text-center text-base font-black text-sky-800 tabular-nums">
                                    @if ($row['external_drivers'] > 0)
                                        <a
                                            href="{{ route('drivers.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                            class="hover:underline"
                                        >
                                            {{ number_format((int) $row['external_drivers']) }}
                                        </a>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-amber-50/40 px-4 py-3 text-center text-base font-black text-amber-800 tabular-nums">
                                    @if ($row['no_driver_count'] > 0)
                                        <a
                                            href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type'], 'equipment_status' => 'متوقفة بدون سائق']) }}"
                                            class="hover:underline"
                                        >
                                            {{ number_format((int) $row['no_driver_count']) }}
                                        </a>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap bg-rose-50/40 px-4 py-3 text-center text-base font-black text-rose-800 tabular-nums">
                                    @if ($row['broken_count'] > 0)
                                        <a
                                            href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type'], 'equipment_status' => 'متعطلة في الورشة']) }}"
                                            class="hover:underline"
                                        >
                                            {{ number_format((int) $row['broken_count']) }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-950 text-sm font-black text-white">
                        <tr class="border-t-2 border-slate-700">
                            <td colspan="2" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">الإجمالي العام</td>
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-slate-800 px-4 py-3.5 text-center text-base tabular-nums">
                                {{ number_format((int) $totals['company_total']) }}
                            </td>
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-emerald-800 px-4 py-3.5 text-center text-base tabular-nums">
                                {{ number_format((int) $totals['company_drivers']) }}
                            </td>
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-sky-800 px-4 py-3.5 text-center text-base tabular-nums">
                                {{ number_format((int) $totals['external_drivers']) }}
                            </td>
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-amber-700 px-4 py-3.5 text-center text-base tabular-nums">
                                {{ number_format((int) $totals['no_driver_count']) }}
                            </td>
                            <td class="whitespace-nowrap bg-rose-800 px-4 py-3.5 text-center text-base tabular-nums">
                                {{ number_format((int) $totals['broken_count']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>

