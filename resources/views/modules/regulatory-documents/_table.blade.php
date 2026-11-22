@php
    $activeDoc = $activeDocumentType ?? '';
    $hijriHeader = match ($activeDoc) {
        'فحص دوري' => 'تاريخ الفحص (هـ)',
        'كرت تشغيل' => 'تاريخ كارت التشغيل (هـ)',
        'استمارة' => 'تاريخ الاستمارة (هـ)',
        default => 'التاريخ الهجري (هـ)',
    };
    $daysHeader = match ($activeDoc) {
        'فحص دوري' => 'الأيام المتبقية للفحص',
        'كرت تشغيل' => 'الأيام المتبقية للكرت',
        'استمارة' => 'الأيام المتبقية للاستمارة',
        default => 'الأيام المتبقية',
    };
    $showRenewalStatusColumn = $activeDoc !== 'فحص دوري';
    $totalColumns = $showRenewalStatusColumn ? 12 : 11;
@endphp

<div class="overflow-x-auto max-h-[68vh]">
    <table class="min-w-full border-collapse text-xs">
        <thead class="sticky top-0 z-10 bg-slate-900 text-white">
            <tr>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-2.5 py-3 text-center font-bold">م</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-start font-bold">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-start font-bold">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">رقم اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">نوع النقل</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">الفرع الحالي</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">الرقم التسلسلي</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">{{ $hijriHeader }}</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">الميلادي</th>
                <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">{{ $daysHeader }}</th>
                @if ($showRenewalStatusColumn)
                    <th scope="col" class="whitespace-nowrap border-e border-slate-700 px-3 py-3 text-center font-bold">الموقف من التجديد</th>
                @endif
                <th scope="col" class="whitespace-nowrap px-3 py-3 text-center font-bold">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
            @forelse ($records as $document)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = $document->days_remaining;
                    $isMissing = $document->compliance_status === \App\Enums\ComplianceStatus::Missing || $days === null;

                    if ($isMissing) {
                        $daysLabel = 'وثيقة مفقودة';
                        $daysBadge = 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/30';
                    } elseif ($days < 0) {
                        $daysLabel = 'منتهي منذ '.abs($days).' يوم';
                        $daysBadge = 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/30';
                    } elseif ($days <= 30) {
                        $daysLabel = $days === 0 ? 'ينتهي اليوم' : 'متبقي '.$days.' يوم';
                        $daysBadge = 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-600/30';
                    } else {
                        $daysLabel = 'متبقي '.$days.' يوم';
                        $daysBadge = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/25';
                    }

                    $transportBadge = $document->transport_type === 'نقل خاص'
                        ? 'bg-purple-50 text-purple-800 ring-1 ring-inset ring-purple-600/20'
                        : 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-600/20';

                    $renewalBadge = match ($document->renewal_status) {
                        'مطلوب تجديد' => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-600/30',
                        'عمر افتراضي' => 'bg-purple-100 text-purple-900 ring-1 ring-inset ring-purple-600/30',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp
                <tr class="even:bg-slate-50/70 hover:bg-sky-50/50 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-200 px-2.5 py-3 text-center font-extrabold text-slate-600 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3">
                        <p dir="ltr" class="font-mono text-xs font-extrabold text-slate-900 text-end">{{ $document->entity_identifier ?: '—' }}</p>
                        @if ($document->document_type)
                            <span class="mt-0.5 inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">
                                {{ $document->document_type }}
                            </span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 font-bold text-slate-800">
                        {{ $document->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-bold text-slate-900">
                        {{ $document->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                        @if ($document->transport_type)
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold {{ $transportBadge }}">
                                {{ $document->transport_type }}
                            </span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-bold text-slate-800">
                        {{ $document->branch ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-mono font-bold text-slate-700 tabular-nums">
                        {{ $document->document_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-mono font-semibold text-slate-700 tabular-nums">
                        {{ $document->hijri_expiry_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center font-mono font-bold text-slate-900 tabular-nums">
                        {{ $document->expiry_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums {{ $daysBadge }}">
                            {{ $daysLabel }}
                        </span>
                    </td>
                    @if ($showRenewalStatusColumn)
                        <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                            @if ($document->renewal_status)
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-extrabold {{ $renewalBadge }}">
                                    {{ $document->renewal_status }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$document" :label="($document->document_type ?: 'وثيقة').' — '.($document->entity_identifier ?: '#'.$document->id)" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalColumns }}" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد سجلات مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة سجل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
