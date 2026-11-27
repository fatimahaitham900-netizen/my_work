@php
    $grandTotal = (int) ($siteSummary['grand_total'] ?? 0);
    $statuses = $siteSummary['statuses'] ?? [];
    $statusTotals = $siteSummary['status_totals'] ?? [];
    $branchGroups = $siteSummary['branch_groups'] ?? [];
    $rowCounter = 0;
@endphp

<div class="space-y-6 p-4 sm:p-6">
    @if ($grandTotal === 0)
        <div class="rounded-2xl border-2 border-slate-200 bg-slate-50 px-6 py-12 text-center">
            <p class="text-base font-black text-slate-900">لا توجد بيانات لعرض الملخص حسب الموقع</p>
            <p class="mt-1 text-xs font-bold text-slate-500">قم باستيراد ملف Excel/CSV لتوزيعة المعدات ليتم حساب الملخص تلقائياً.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
            <div class="bg-slate-950 px-5 py-3.5 text-center text-base sm:text-lg font-black text-white">
                ملخص توزيعة المعدات حسب الفرع والموقع (الإجمالي: {{ number_format($grandTotal) }} معدة)
            </div>
            <div class="max-h-[74vh] overflow-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-xs sm:text-[13px] font-black text-white shadow-md">
                        <tr>
                            <th scope="col" class="sticky top-0 z-20 w-12 border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الفرع</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الموقع</th>
                            @foreach ($statuses as $status)
                                <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3.5 py-3.5 text-center">
                                    {{ $status }}
                                </th>
                            @endforeach
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-sky-600 bg-sky-900 px-4 py-3.5 text-center text-sky-100">الإجمالي</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">النسبة</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($branchGroups as $group)
                            @php
                                $siteCount = count($group['sites']);
                            @endphp
                            @foreach ($group['sites'] as $row)
                                @php $rowCounter++; @endphp
                                <tr class="border-b border-slate-400 even:bg-slate-50/80 hover:bg-sky-50/60 transition-colors">
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                        {{ $rowCounter }}
                                    </td>
                                    @if ($loop->first)
                                        <td
                                            rowspan="{{ $siteCount }}"
                                            class="whitespace-nowrap border-e-2 border-b-2 border-slate-700 bg-sky-50/70 px-4 py-3 text-center align-middle"
                                        >
                                            <a
                                                href="{{ route('equipment-distributions.index', ['view' => 'table', 'branch' => $group['branch']]) }}"
                                                class="inline-flex items-center rounded-full bg-slate-900 px-3.5 py-1 text-xs sm:text-sm font-black text-white shadow-xs hover:bg-sky-700 transition"
                                            >
                                                {{ $group['branch'] }}
                                            </a>
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-950">
                                        <a
                                            href="{{ route('equipment-distributions.index', ['view' => 'table', 'branch' => $group['branch'], 'site_location' => $row['site']]) }}"
                                            class="text-slate-950 hover:text-blue-700 hover:underline"
                                        >
                                            {{ $row['site'] }}
                                        </a>
                                    </td>
                                    @foreach ($statuses as $status)
                                        @php
                                            $val = (int) ($row['counts'][$status] ?? 0);
                                            $valColor = match (true) {
                                                str_contains($status, 'تشغيل') => 'text-emerald-700',
                                                str_contains($status, 'متعطلة') || str_contains($status, 'عطل') => 'text-rose-700',
                                                str_contains($status, 'متوقفة') || str_contains($status, 'بدون عمل') => 'text-amber-700',
                                                default => 'text-slate-950',
                                            };
                                        @endphp
                                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3 text-center text-sm font-black tabular-nums {{ $valColor }}">
                                            @if ($val > 0)
                                                <a
                                                    href="{{ route('equipment-distributions.index', ['view' => 'table', 'branch' => $group['branch'], 'site_location' => $row['site'], 'equipment_status' => $status]) }}"
                                                    class="hover:underline"
                                                >
                                                    {{ number_format($val) }}
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-sky-50/70 px-4 py-3 text-center text-base font-black text-sky-950 tabular-nums">
                                        <a
                                            href="{{ route('equipment-distributions.index', ['view' => 'table', 'branch' => $group['branch'], 'site_location' => $row['site']]) }}"
                                            class="inline-flex items-center justify-center rounded-lg px-2.5 py-0.5 text-sky-950 hover:bg-sky-200/70 hover:text-blue-800 transition"
                                        >
                                            {{ number_format($row['total']) }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-xs font-black text-slate-900 tabular-nums">
                                        {{ $row['percentage'] }}%
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Branch Subtotal Row --}}
                            <tr class="border-b-2 border-slate-900 bg-amber-50/80 text-sm font-black text-slate-950">
                                <td colspan="3" class="border-e-2 border-slate-500 px-4 py-2.5 text-center text-xs sm:text-sm font-black text-amber-950">
                                    إجمالي فرع {{ $group['branch'] }} ({{ $siteCount }} {{ $siteCount === 1 ? 'موقع' : 'مواقع' }})
                                </td>
                                @foreach ($statuses as $status)
                                    @php $bVal = (int) ($group['status_totals'][$status] ?? 0); @endphp
                                    <td class="whitespace-nowrap border-e-2 border-slate-500 px-3.5 py-2.5 text-center text-sm font-black text-slate-950 tabular-nums">
                                        {{ $bVal > 0 ? number_format($bVal) : '' }}
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap border-e-2 border-slate-500 bg-amber-200/80 px-4 py-2.5 text-center text-base font-black text-slate-950 tabular-nums">
                                    {{ number_format($group['total']) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-center text-xs font-black text-amber-950 tabular-nums">
                                    {{ $group['percentage'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-950 text-sm font-black text-white">
                        <tr class="border-t-2 border-slate-700">
                            <td colspan="3" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">الإجمالي العام</td>
                            @foreach ($statuses as $status)
                                @php $colTotal = (int) ($statusTotals[$status] ?? 0); @endphp
                                <td class="whitespace-nowrap border-e-2 border-slate-700 px-3.5 py-3.5 text-center tabular-nums">
                                    {{ $colTotal > 0 ? number_format($colTotal) : '' }}
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap border-e-2 border-slate-700 bg-sky-700 px-4 py-3.5 text-center text-base font-black text-white tabular-nums">
                                {{ number_format($grandTotal) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-center tabular-nums">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
