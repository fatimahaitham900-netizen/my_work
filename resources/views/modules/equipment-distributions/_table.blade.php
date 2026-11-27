@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" empty-message="لا توجد سجلات في توزيعة المعدات مطابقة لمعايير البحث" />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الموقع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">حالة المعدة</th>
                <th scope="col" class="min-w-52 border-b-2 border-slate-700 px-4 py-4 text-center">ملاحظة</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $statusText = (string) ($item->equipment_status ?? '');
                    $statusBadge = match (true) {
                        str_contains($statusText, 'تشغيل') => 'bg-emerald-100 text-emerald-950 ring-1 ring-inset ring-emerald-600/35',
                        str_contains($statusText, 'متعطلة') || str_contains($statusText, 'عطل') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                        str_contains($statusText, 'متوقفة') || str_contains($statusText, 'بدون عمل') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        default => 'bg-slate-100 text-slate-900 ring-1 ring-inset ring-slate-400/40',
                    };
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $item->equipment_type ?: '—' }}
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
                        {{ $item->site_location ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($statusText !== '')
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-52 px-4 py-3.5 text-center text-sm font-bold text-slate-900">
                        {{ $item->notes !== null && trim((string) $item->notes) !== '' ? $item->notes : '—' }}
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$item" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد بيانات في توزيعة المعدات حالياً</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV أو إضافة سجل جديد من الأعلى.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

