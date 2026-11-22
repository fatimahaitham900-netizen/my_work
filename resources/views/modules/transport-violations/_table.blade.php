<div class="overflow-x-auto max-h-[74vh]">
    <table class="min-w-full border-collapse text-[13px]">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b-2 border-e border-slate-700 px-2.5 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">رقم المخالفة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">السجل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">التاريخ</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-rose-500/70 bg-rose-500/20 px-3.5 py-3.5 text-center text-rose-200">المبلغ</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">الإدارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">مدينة المخالفة</th>
                <th scope="col" class="min-w-60 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">شرح المخالفة</th>
                <th scope="col" class="min-w-48 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">حالة الاعتراض</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">رقم اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الكود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">حالة الموظف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">التحميل</th>
                <th scope="col" class="min-w-44 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">موقف التصريح</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $violation)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $amount = (float) $violation->amount;

                    $recordHolder = (string) ($violation->record_holder ?? '');
                    $holderBadge = match (true) {
                        $recordHolder === 'الموظف' => 'bg-purple-100 text-purple-900 ring-1 ring-inset ring-purple-600/25',
                        str_contains($recordHolder, 'للمقاولات') => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/25',
                        str_contains($recordHolder, 'المحدودة') => 'bg-indigo-100 text-indigo-900 ring-1 ring-inset ring-indigo-600/25',
                        default => 'bg-slate-100 text-slate-800',
                    };

                    $objection = (string) ($violation->objection_status ?? '');
                    $objectionBadge = match (true) {
                        str_contains($objection, 'انتظار') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        str_contains($objection, 'الرفض') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        str_contains($objection, 'لا يوجد دليل') => 'bg-orange-100 text-orange-900 ring-1 ring-inset ring-orange-600/30',
                        str_contains($objection, 'لم يتم تقديمه') => 'bg-slate-200/90 text-slate-800 ring-1 ring-inset ring-slate-500/25',
                        default => 'bg-slate-100 text-slate-600',
                    };

                    $empStatus = (string) ($violation->employee_status ?? '');
                    $empBadge = match (true) {
                        str_contains($empStatus, 'متواجد') => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30',
                        str_contains($empStatus, 'خروج نهائي') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        str_contains($empStatus, 'مجاز') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        $empStatus !== '' => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/25',
                        default => '',
                    };

                    $permit = (string) ($violation->permit_status ?? '');
                    $permitBadge = match (true) {
                        str_contains($permit, 'يوجد تصريح') => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30',
                        str_contains($permit, 'لا يستدعي') => 'bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-600/25',
                        str_contains($permit, 'لا يوجد') => 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-600/25',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-2.5 py-3 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300 tabular-nums">
                            {{ $violation->violation_number ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start">
                        @if ($recordHolder !== '')
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold {{ $holderBadge }}">
                                {{ $recordHolder }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900 tabular-nums">
                        {{ $violation->violation_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 bg-rose-50/40 px-3.5 py-3 text-center">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums {{ $amount >= 10000 ? 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35' : 'bg-white text-slate-950 ring-1 ring-inset ring-slate-300' }}">
                            {{ number_format($amount, 0) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($violation->department)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-300">
                                {{ $violation->department }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900">
                        {{ $violation->city ?: '—' }}
                    </td>
                    <td class="min-w-60 max-w-xs border-e border-slate-300 px-3.5 py-3 text-start font-bold leading-relaxed text-slate-900">
                        {{ $violation->violation_description ?: '—' }}
                    </td>
                    <td class="min-w-48 max-w-xs border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($objection !== '')
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold leading-snug {{ $objectionBadge }}">
                                {{ $objection }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-extrabold text-slate-900">
                        {{ $violation->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        @if ($violation->equipment_code)
                            <code dir="ltr" class="inline-block rounded-lg bg-slate-900/5 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300">
                                {{ $violation->equipment_code }}
                            </code>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold text-slate-900">
                        {{ $violation->driver_name ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-extrabold text-slate-900 tabular-nums">
                        <span dir="ltr">{{ $violation->employee_id ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($empStatus !== '')
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold {{ $empBadge }}">
                                {{ $empStatus }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900">
                        @if ($violation->liability)
                            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-extrabold text-indigo-900 ring-1 ring-inset ring-indigo-600/25">
                                {{ $violation->liability }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-44 max-w-xs border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($permit !== '')
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-bold leading-snug {{ $permitBadge }}">
                                {{ $permit }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$violation" :label="$violation->violation_number ?: 'مخالفة نقل'" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد مخالفات وزارة نقل مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة مخالفة جديدة.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-900 text-xs font-extrabold text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="4" class="border-e border-slate-700 px-4 py-3.5 text-center text-sm font-extrabold">
                        الإجمالي العام للنتائج ({{ $records->total() }} مخالفة)
                    </td>
                    <td class="whitespace-nowrap border-e border-rose-500/70 bg-rose-600/90 px-4 py-3.5 text-center text-sm font-extrabold text-white tabular-nums">
                        {{ number_format((float) $filteredAmountTotal, 0) }} ر.س
                    </td>
                    <td colspan="12" class="px-4 py-3.5 text-start text-xs font-semibold text-slate-300">
                        يتم احتساب إجمالي المبالغ تلقائياً وفق الفلاتر النشطة حالياً
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />

