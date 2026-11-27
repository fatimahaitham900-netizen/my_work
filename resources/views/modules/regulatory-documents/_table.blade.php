@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
    $activeDoc = $activeDocumentType ?? '';
    $hijriHeader = match ($activeDoc) {
        'فحص دوري' => 'تاريخ الفحص (هـ)',
        'كرت تشغيل' => 'تاريخ كارت التشغيل (هـ)',
        'استمارة' => 'تاريخ الاستمارة (هـ)',
        'تأمين' => 'تاريخ التأمين (هـ)',
        default => 'التاريخ الهجري (هـ)',
    };
    $daysHeader = match ($activeDoc) {
        'فحص دوري' => 'الأيام المتبقية للفحص',
        'كرت تشغيل' => 'الأيام المتبقية للكرت',
        'استمارة' => 'الأيام المتبقية للاستمارة',
        'تأمين' => 'الأيام المتبقية للتأمين',
        default => 'الأيام المتبقية',
    };
    $showRenewalStatusColumn = $activeDoc !== 'فحص دوري';
    $totalColumns = $showRenewalStatusColumn ? 11 : 10;
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">رقم اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">نوع النقل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الفرع الحالي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الرقم التسلسلي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">{{ $hijriHeader }}</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الميلادي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 {{ $showRenewalStatusColumn ? 'border-e-2' : '' }} border-slate-700 px-3.5 py-4 text-center">{{ $daysHeader }}</th>
                @if ($showRenewalStatusColumn)
                    <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3.5 py-4 text-center">الموقف من التجديد</th>
                @endif
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $document)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = $document->days_remaining;
                    $isMissing = $document->compliance_status === \App\Enums\ComplianceStatus::Missing || $days === null;

                    if ($isMissing) {
                        $daysLabel = 'وثيقة مفقودة';
                        $daysBadge = 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35';
                    } elseif ($days < 0) {
                        $daysLabel = 'منتهي منذ '.abs($days).' يوم';
                        $daysBadge = 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35';
                    } elseif ($days <= 30) {
                        $daysLabel = $days === 0 ? 'ينتهي اليوم' : 'متبقي '.$days.' يوم';
                        $daysBadge = 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35';
                    } else {
                        $daysLabel = 'متبقي '.$days.' يوم';
                        $daysBadge = 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/35';
                    }

                    $transportBadge = $document->transport_type === 'نقل خاص'
                        ? 'bg-purple-100 text-purple-950 ring-1 ring-inset ring-purple-600/30'
                        : 'bg-sky-100 text-sky-950 ring-1 ring-inset ring-sky-600/30';

                    $renewalBadge = match ($document->renewal_status) {
                        'مطلوب تجديد' => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        'عمر افتراضي' => 'bg-purple-100 text-purple-950 ring-1 ring-inset ring-purple-600/35',
                        default => 'bg-slate-100 text-slate-800',
                    };
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-2.5 py-1 font-mono text-xs font-black text-white shadow-xs">
                                {{ $document->entity_identifier ?: '—' }}
                            </span>
                            @if ($activeDoc === '' && $document->document_type)
                                <span class="inline-flex items-center rounded-md bg-slate-200/80 px-2 py-0.5 text-[11px] font-extrabold text-slate-800">
                                    {{ $document->document_type }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $document->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $document->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        @if ($document->transport_type)
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $transportBadge }}">
                                {{ $document->transport_type }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        @if ($document->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $document->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-extrabold text-slate-900 tabular-nums">
                        {{ $document->document_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-extrabold text-slate-900 tabular-nums">
                        {{ $document->hijri_expiry_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-black text-slate-950 tabular-nums">
                        {{ $document->expiry_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap {{ $showRenewalStatusColumn ? 'border-e-2 border-slate-400' : '' }} px-3.5 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-black tabular-nums {{ $daysBadge }}">
                            {{ $daysLabel }}
                        </span>
                    </td>
                    @if ($showRenewalStatusColumn)
                        <td class="whitespace-nowrap px-3.5 py-3.5 text-center">
                            @if ($document->renewal_status)
                                <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-black {{ $renewalBadge }}">
                                    {{ $document->renewal_status }}
                                </span>
                            @else
                                <span class="font-bold text-slate-400">—</span>
                            @endif
                        </td>
                    @endif
                    <x-module.extra-cells :headers="$extraHeaders" :record="$document" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalColumns + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد سجلات مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب تغيير الفلاتر أو إضافة سجل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif
