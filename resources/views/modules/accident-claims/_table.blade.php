@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
    $isAccidentsTab = ($activeTab ?? \App\Models\AccidentClaim::TYPE_ACCIDENT) === \App\Models\AccidentClaim::TYPE_ACCIDENT;
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" empty-message="سجل الحوادث نظيف ولا توجد حوادث مسجلة" />
@elseif ($isAccidentsTab)
    <div class="overflow-x-auto max-h-[76vh]">
        <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
                <tr>
                    <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">كود المستخدم</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الفرع</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">السجل</th>
                    <th scope="col" class="min-w-60 border-b-2 border-slate-700 px-4 py-4 text-center">ملاحظات</th>
                    <x-module.extra-headers :headers="$extraHeaders" />
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse ($records as $item)
                    @php
                        $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    @endphp
                    <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                        <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                            {{ $rowNumber }}
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white">
                                {{ $item->user_code ?: ($item->vehicle_code ?: '—') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            @if ($item->branch)
                                <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                    {{ $item->branch }}
                                </span>
                            @else
                                <span class="font-bold text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-black text-slate-950">
                            {{ $item->record_holder ?: '—' }}
                        </td>
                        <td class="min-w-60 px-4 py-3.5 text-center text-sm font-bold text-slate-900">
                            {{ $item->notes ?: '—' }}
                        </td>
                        <x-module.extra-cells :headers="$extraHeaders" :record="$item" />
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($extraHeaders) }}" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>
                            <p class="text-base font-black text-slate-700">سجل الحوادث نظيف ولا توجد حوادث مسجلة</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">يمكنك إضافة حادث جديد أو استيراد ملف Excel/CSV من الأعلى.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-module.pagination :paginator="$records" />
@else
    <div class="overflow-x-auto max-h-[76vh]">
        <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
                <tr>
                    <th scope="col" class="w-12 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">كود السيارة واللوحة</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">تاريخ وموقع الحادث</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">السائق</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">رقم تقرير نجم / المطالبة</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">نسبة المسؤولية</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-emerald-500/80 bg-emerald-900/60 px-4 py-4 text-center text-emerald-100">التكلفة والتعويض</th>
                    <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-4 py-4 text-center">حالة المطالبة</th>
                    <x-module.extra-headers :headers="$extraHeaders" />
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse ($records as $item)
                    @php
                        $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                        $statusText = (string) ($item->claim_status ?? '');
                        $statusBadge = match (true) {
                            str_contains($statusText, 'تم التعويض') || str_contains($statusText, 'مغلق') => 'bg-emerald-100 text-emerald-950 ring-1 ring-inset ring-emerald-600/35',
                            str_contains($statusText, 'مرفوعة') || str_contains($statusText, 'تحت') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                            str_contains($statusText, 'مرفوض') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                            default => 'bg-slate-100 text-slate-900 ring-1 ring-inset ring-slate-400/40',
                        };
                        $faultPct = $item->fault_percentage;
                        $faultBadge = match (true) {
                            $faultPct === null => 'bg-slate-100 text-slate-600',
                            $faultPct === 0 => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30',
                            $faultPct >= 75 => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                            default => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        };
                    @endphp
                    <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                        <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                            {{ $rowNumber }}
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-2.5 py-1 font-mono text-xs font-black text-white">
                                {{ $item->vehicle_code ?: '—' }}
                            </span>
                            @if ($item->plate_number)
                                <p class="mt-1 text-xs font-extrabold text-slate-700">{{ $item->plate_number }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            <p class="text-sm font-black text-slate-950 tabular-nums">{{ $item->accident_date?->format('Y-m-d') ?? '—' }}</p>
                            @if ($item->accident_location || $item->branch)
                                <p class="mt-0.5 text-xs font-bold text-slate-600">
                                    {{ $item->accident_location ?: '' }}{{ $item->accident_location && $item->branch ? ' — ' : '' }}{{ $item->branch ?: '' }}
                                </p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            <p class="text-sm font-black text-slate-950">{{ $item->driver_name ?: '—' }}</p>
                            @if ($item->employee_id)
                                <p dir="ltr" class="mt-0.5 font-mono text-xs font-bold text-slate-500">{{ $item->employee_id }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                            @if ($item->najm_report_number)
                                <p dir="ltr" class="font-mono text-xs font-black text-slate-950">{{ $item->najm_report_number }}</p>
                            @endif
                            @if ($item->claim_number)
                                <p dir="ltr" class="mt-0.5 font-mono text-xs font-extrabold text-sky-800">{{ $item->claim_number }}</p>
                            @endif
                            @if ($item->insurance_company)
                                <p class="mt-0.5 text-[11px] font-bold text-slate-600">{{ $item->insurance_company }}</p>
                            @endif
                            @if (! $item->najm_report_number && ! $item->claim_number && ! $item->insurance_company)
                                <span class="font-bold text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black tabular-nums {{ $faultBadge }}">
                                {{ $faultPct !== null ? $faultPct.'%' : '—' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/40 px-4 py-3.5 text-center tabular-nums">
                            <p class="text-xs font-extrabold text-rose-800">
                                التكلفة: {{ number_format((float) $item->estimated_cost, 0) }} ر.س
                            </p>
                            <p class="mt-0.5 text-xs font-black text-emerald-800">
                                التعويض: {{ number_format((float) $item->compensation_amount, 0) }} ر.س
                            </p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-center">
                            @if ($statusText !== '')
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $statusBadge }}">
                                    {{ $statusText }}
                                </span>
                            @else
                                <span class="font-bold text-slate-400">—</span>
                            @endif
                        </td>
                        <x-module.extra-cells :headers="$extraHeaders" :record="$item" />
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 8 + count($extraHeaders) }}" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>
                            <p class="text-base font-black text-slate-700">لا توجد مطالبات تأمين مسجلة حالياً</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">يمكنك إضافة مطالبة جديدة أو استيراد ملف Excel/CSV من الأعلى.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-module.pagination :paginator="$records" />
@endif
