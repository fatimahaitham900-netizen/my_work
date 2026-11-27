@php
    $grandTotal = (int) ($summary['grand_total'] ?? 0);
    $branches = $summary['branches'] ?? [];
    $branchRows = $summary['branch_rows'] ?? [];
    $branchTotals = $summary['branch_totals'] ?? [];
@endphp

<div class="space-y-6 p-4 sm:p-6">
    @if ($grandTotal === 0)
        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-base font-black text-slate-900">لا توجد بيانات لعرض الملخص حسب النوع</p>
            <p class="mt-1 text-xs font-bold text-slate-500">قم باستيراد ملف Excel/CSV لتوزيعة المعدات ليتم حساب الملخص تلقائياً.</p>
        </div>
    @else
        {{-- ملخص توزيعة المعدات حسب النوع والفرع --}}
        <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
            <div class="bg-slate-950 px-5 py-3.5 text-center text-base sm:text-lg font-black text-white">
                ملخص توزيعة المعدات حسب النوع والفرع (الإجمالي: {{ number_format($grandTotal) }} معدة)
            </div>
            <div class="max-h-[74vh] overflow-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-xs sm:text-[13px] font-black text-white shadow-md">
                        <tr>
                            <th scope="col" class="sticky top-0 z-20 w-12 border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">نوع المعدة</th>
                            @foreach ($branches as $branch)
                                <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3.5 py-3.5 text-center">
                                    {{ $branch }}
                                </th>
                            @endforeach
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-sky-600 bg-sky-900 px-4 py-3.5 text-center text-sky-100">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($branchRows as $row)
                            <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-sky-50/60 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-950">
                                    <a
                                        href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type']]) }}"
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
                                                href="{{ route('equipment-distributions.index', ['view' => 'table', 'equipment_type' => $row['type'], 'branch' => $branch]) }}"
                                                class="hover:text-blue-700 hover:underline"
                                            >
                                                {{ number_format($val) }}
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap bg-sky-50/70 px-4 py-3 text-center text-base font-black text-sky-950 tabular-nums">
                                    {{ number_format($row['total']) }}
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
                            <td class="whitespace-nowrap bg-sky-700 px-4 py-3.5 text-center text-base font-black text-white tabular-nums">
                                {{ number_format($grandTotal) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
