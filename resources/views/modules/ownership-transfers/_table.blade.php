@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-start">البيان</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">رقم الهيكل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الحالة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-4 py-4 text-start">ملاحظات</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $transfer)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $noteText = $transfer->notes ?: ($transfer->blocker_reason ?: '—');
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $transfer->equipment_code ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-start text-sm font-extrabold text-slate-950">
                        {{ $transfer->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($transfer->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3.5 py-1 text-xs font-extrabold text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $transfer->branch }}
                            </span>
                        @else
                            <span class="text-xs font-bold text-slate-500">{{ $transfer->current_owner ?: '—' }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($transfer->chassis_number)
                            <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-3 py-1 font-mono text-xs font-extrabold tracking-wide text-slate-950 ring-1 ring-inset ring-slate-400 select-all">
                                {{ $transfer->chassis_number }}
                            </code>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $transfer->status->badgeClasses() }}">
                            {{ $transfer->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-start">
                        @if (str_contains($noteText, 'جمركية'))
                            <span class="inline-flex items-center gap-2 rounded-lg bg-amber-100 px-3 py-1 text-xs font-extrabold text-amber-950 ring-1 ring-inset ring-amber-600/35">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-amber-600"></span>
                                <span>{{ $noteText }}</span>
                            </span>
                        @else
                            <span class="text-sm font-bold text-slate-900">{{ $noteText }}</span>
                        @endif
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$transfer" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 7 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد سجلات نقل ملكية مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب تغيير الفلاتر أو استيراد ملف جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif
