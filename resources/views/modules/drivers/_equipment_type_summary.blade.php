@php
    $grandTotal = (int) ($summary['grand_total'] ?? 0);
    $totalSalary = (float) ($summary['total_salary'] ?? 0);
    $branches = $summary['branches'] ?? [];
    $branchTotals = $summary['branch_totals'] ?? [];
    $equipmentTypeRows = $summary['equipment_type_rows'] ?? [];
@endphp

<div class="space-y-6 p-4 sm:p-6">
    @if ($grandTotal === 0)
        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-base font-black text-slate-900">لا توجد بيانات سائقين لعرض الملخص حسب نوع المعدة</p>
            <p class="mt-1 text-xs font-bold text-slate-500">قم باستيراد ملف السائقين وسيتم إنشاء الملخص تلقائياً من توزيعة المعدات.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
            <div class="bg-slate-950 px-5 py-3.5 text-center text-base sm:text-lg font-black text-white">
                ملخص السائقين حسب نوع المعدة والفرع (الإجمالي: {{ number_format($grandTotal) }} سائق)
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-slate-900 text-xs sm:text-[13px] font-black text-white">
                        <tr>
                            <th scope="col" class="w-12 border-b-2 border-e-2 border-slate-700 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-3.5 text-center">نوع المعدة</th>
                            @foreach ($branches as $branch)
                                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-3.5 text-center">
                                    {{ $branch }}
                                </th>
                            @endforeach
                            <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-sky-500/80 bg-sky-700/40 px-4 py-3.5 text-center text-sky-100">الإجمالي</th>
                            <th scope="col" class="whitespace-nowrap border-b-2 border-emerald-500/80 bg-emerald-800/50 px-4 py-3.5 text-center text-emerald-100">إجمالي الرواتب (ر.س)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($equipmentTypeRows as $row)
                            <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-sky-50/60 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-950">
                                    <a
                                        href="{{ route('drivers.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                        class="text-slate-950 hover:text-blue-700 hover:underline"
                                    >
                                        {{ $row['type'] }}
                                    </a>
                                </td>
                                @foreach ($branches as $branch)
                                    @php $val = (int) ($row['counts'][$branch] ?? 0); @endphp
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                        @if ($val > 0)
                                            <a
                                                href="{{ route('drivers.index', ['view' => 'table', 'equipment_type' => $row['type'], 'branch' => $branch]) }}"
                                                class="hover:text-blue-700 hover:underline"
                                            >
                                                {{ number_format($val) }}
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-sky-50/70 px-4 py-3 text-center text-base font-black text-sky-950 tabular-nums">
                                    <a
                                        href="{{ route('drivers.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
                                        class="inline-flex items-center justify-center rounded-lg px-2.5 py-0.5 text-sky-950 hover:bg-sky-200/70 hover:text-blue-800 transition"
                                    >
                                        {{ number_format($row['total']) }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap bg-emerald-50/60 px-4 py-3 text-center text-sm font-black text-emerald-900 tabular-nums">
                                    {{ $row['salary'] > 0 ? number_format($row['salary'], 0) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-950 text-sm font-black text-white">
                        <tr class="border-t-2 border-slate-700">
                            <td colspan="2" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">الإجمالي العام</td>
                            @foreach ($branches as $branch)
                                @php $colTotal = (int) ($branchTotals[$branch] ?? 0); @endphp
                                <td class="whitespace-nowrap border-e-2 border-slate-700 px-3.5 py-3.5 text-center tabular-nums">
                                    {{ $colTotal > 0 ? number_format($colTotal) : '' }}
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-sky-700 px-4 py-3.5 text-center text-base font-black text-white tabular-nums">
                                {{ number_format($grandTotal) }}
                            </td>
                            <td class="whitespace-nowrap bg-emerald-700 px-4 py-3.5 text-center text-base font-black text-white tabular-nums">
                                {{ number_format($totalSalary, 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>

