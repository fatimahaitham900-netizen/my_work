@php
    $showMonthCol = ($activeMonth ?? 'all') === 'all';
    $colSpanBeforeAmount = $showMonthCol ? 4 : 3;
    $totalCols = $showMonthCol ? 15 : 14;
@endphp

<div class="overflow-x-auto max-h-[72vh]">
    <table class="min-w-full border-collapse text-[13px]">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b-2 border-e border-slate-700 px-2.5 py-3.5 text-center">م</th>
                @if ($showMonthCol)
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الشهر</th>
                @endif
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">الكود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-emerald-500/70 bg-emerald-500/20 px-3.5 py-3.5 text-center text-emerald-200">المبلغ</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">اسم المستفيد</th>
                <th scope="col" class="min-w-60 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">نوع العطل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الحالة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">رقم الـ PO / المرحلة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">تاريخ التحويل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">تاريخ التوريد</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">تاريخ الإصلاح</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">موقف الإصلاح</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $amount = (float) $item->amount;
                    $stage = (string) ($item->transfer_stage ?? '');
                    $status = (string) ($item->repair_status ?? '');
                    $upperStatus = strtoupper($status);

                    $stageBadge = match (true) {
                        str_contains($stage, 'لم يتم التحويل') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        str_contains($stage, 'دعم مالي') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        str_contains($stage, 'تم التحويل') => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/25',
                        default => 'bg-slate-100 text-slate-800',
                    };

                    $repairBadge = match (true) {
                        str_contains($status, 'لم يتم') || str_contains($status, 'متعطل') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                        str_contains($upperStatus, 'STOK') || str_contains($upperStatus, 'STOCK') => 'bg-indigo-100 text-indigo-900 ring-1 ring-inset ring-indigo-600/30',
                        str_contains($status, 'تم') => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors {{ str_contains($status, 'لم يتم') ? 'bg-rose-50/35' : '' }}">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-2.5 py-3 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    @if ($showMonthCol)
                        <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                            <span class="inline-flex items-center rounded-md bg-slate-900 px-2 py-0.5 text-[11px] font-extrabold text-white">
                                شهر {{ $item->support_month }}
                            </span>
                        </td>
                    @endif
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-extrabold text-slate-950">
                        {{ $item->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300">
                            {{ $item->equipment_code ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 bg-emerald-50/35 px-3.5 py-3 text-center font-extrabold text-slate-950 tabular-nums">
                        @if ($amount > 0)
                            {{ number_format($amount, 0) }}
                        @else
                            <span class="text-xs font-bold text-slate-400">مدمج</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold text-slate-900">
                        {{ $item->beneficiary_name ?: '—' }}
                    </td>
                    <td class="min-w-60 max-w-sm border-e border-slate-300 px-3.5 py-3 text-start font-bold leading-relaxed text-slate-900">
                        {{ $item->fault_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        @if ($item->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 ring-1 ring-inset ring-sky-600/25">
                                {{ $item->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center text-xs font-bold {{ str_contains((string) $item->case_status, 'تحقيق') ? 'text-rose-700' : 'text-slate-700' }}">
                        {{ $item->case_status ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <div class="flex flex-col items-center gap-1">
                            @if ($item->po_number)
                                <code dir="ltr" class="inline-block rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-extrabold text-slate-900 ring-1 ring-inset ring-slate-300">
                                    {{ $item->po_number }}
                                </code>
                            @endif
                            @if ($stage !== '')
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold {{ $stageBadge }}">
                                    {{ $stage }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900 tabular-nums">
                        {{ $item->transfer_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold {{ $item->supply_date === 'مديونية' ? 'text-amber-700' : 'text-slate-900' }} tabular-nums">
                        {{ $item->supply_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold {{ $item->repair_date === 'مديونية' ? 'text-amber-700' : 'text-slate-900' }} tabular-nums">
                        {{ $item->repair_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($status !== '')
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold {{ $repairBadge }}">
                                {{ $status }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$item" :label="$item->equipment_code ?: ($item->equipment_type ?: 'تحويل دعم مالي')" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalCols }}" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد تحويلات دعم مالي مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الشهر أو الفلاتر أو إضافة تحويل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-900 text-xs font-extrabold text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="{{ $colSpanBeforeAmount }}" class="border-e border-slate-700 px-4 py-3.5 text-center text-sm font-extrabold">
                        الإجمالي ({{ $records->total() }} سجل)
                    </td>
                    <td class="whitespace-nowrap border-e border-emerald-500/70 bg-emerald-600/90 px-4 py-3.5 text-center text-sm font-extrabold text-white tabular-nums">
                        {{ number_format((float) $filteredAmountTotal, 0) }} ر.س
                    </td>
                    <td colspan="10" class="px-4 py-3.5 text-start text-xs font-semibold text-slate-300">
                        يتم احتساب إجمالي المبالغ المعروضة وفق الشهر والفلاتر النشطة حالياً
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />

