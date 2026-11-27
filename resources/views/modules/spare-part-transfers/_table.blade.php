@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" />
@else
<div class="overflow-x-auto max-h-[74vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-14 border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">م</th>
                <th scope="col" class="min-w-80 border-b-2 border-e border-slate-700 px-4 py-3.5 text-start">البند</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">المعدة المنقول منها</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">المعدة المنقول إليها</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-amber-500/70 bg-amber-500/20 px-4 py-3.5 text-center text-amber-200">التاريخ</th>
                <th scope="col" class="min-w-44 border-b-2 border-slate-700 px-4 py-3.5 text-start">ملاحظة</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $transfer)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $unknownSource = $transfer->hasUnknownSource();
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-3 py-3.5 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="min-w-80 border-e border-slate-300 px-4 py-3.5 text-start font-extrabold leading-relaxed text-slate-950">
                        {{ $transfer->item_description ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3.5 text-center">
                        @if ($unknownSource)
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-extrabold text-rose-900 ring-1 ring-inset ring-rose-600/30">
                                غير معروفة
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-100/90 px-3 py-1 text-xs font-extrabold text-amber-950 ring-1 ring-inset ring-amber-600/25">
                                {{ $transfer->from_equipment }}
                            </span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3.5 text-center">
                        @if ($transfer->to_equipment)
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-900 ring-1 ring-inset ring-emerald-600/30">
                                {{ $transfer->to_equipment }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 bg-amber-50/40 px-4 py-3.5 text-center font-extrabold text-slate-950 tabular-nums">
                        {{ $transfer->transfer_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="min-w-44 px-4 py-3.5 text-start">
                        @if ($transfer->notes)
                            <span class="inline-flex items-center rounded-lg bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-900 ring-1 ring-inset ring-sky-600/25">
                                {{ $transfer->notes }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$transfer" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد عمليات نقل قطع غيار مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة عملية نقل جديدة.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

