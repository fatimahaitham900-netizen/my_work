@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
    $activeDoc = $activeDocumentType ?? '';
    $statusHeader = match ($activeDoc) {
        'فحص دوري' => 'الأيام وحالة الفحص الدوري',
        'استمارة' => 'الأيام وحالة الاستمارة',
        'تأمين' => 'الأيام وحالة التأمين',
        default => 'الأيام والحالة',
    };
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">كود السيارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">رقم اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">نوع السيارة والموديل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">المشروع / الموقع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">تاريخ الانتهاء (هجري)</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">تاريخ الانتهاء (ميلادي)</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-rose-500/80 bg-rose-900/60 px-4 py-4 text-center text-rose-100">{{ $statusHeader }}</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $document)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = $document->days_remaining;

                    if ($days === null) {
                        $daysLabel = $document->status_label ?: '—';
                        $daysBadge = 'bg-slate-100 text-slate-800';
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
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                                {{ $document->car_code ?: '—' }}
                            </span>
                            @if ($activeDoc === '' && $document->document_type)
                                <span class="inline-flex items-center rounded-md bg-slate-200/80 px-2 py-0.5 text-[11px] font-extrabold text-slate-800">
                                    {{ $document->document_type }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $document->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $document->car_type_model ?: '—' }}
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
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-extrabold text-slate-900">
                        {{ $document->project_location ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-extrabold text-slate-900 tabular-nums">
                        {{ $document->hijri_expiry_date ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-black text-slate-950 tabular-nums">
                        {{ $document->expiry_date?->format('Y-m-d') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap bg-rose-50/40 px-4 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs sm:text-sm font-black tabular-nums {{ $daysBadge }}">
                            {{ $daysLabel }}
                        </span>
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$document" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 9 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد سجلات أوراق نظامية للسيارات حالياً</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV أو إضافة سجل جديد من الأعلى.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

