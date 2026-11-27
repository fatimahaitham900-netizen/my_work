@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table
        :records="$records"
        :headers="$exactHeaders"
        footer-total-label="إجمالي السجلات المعروضة ({{ $records->total() }} معدة)"
        footer-total-value="{{ number_format((int) $filteredIdleDaysTotal) }} يوم"
    />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">الموقع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">حالة المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-rose-500/80 bg-rose-900/60 px-3.5 py-4 text-center text-rose-100">إجمالي أيام التوقف</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = (int) $item->total_idle_days;
                    $daysBadge = match (true) {
                        $days >= 7 => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/40',
                        $days >= 4 => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        default => 'bg-sky-100 text-sky-950 ring-1 ring-inset ring-sky-600/35',
                    };
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $item->equipment_code ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $item->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-black text-slate-950">
                        {{ $item->driver_name ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-black text-slate-950 tabular-nums">
                        <span dir="ltr">{{ $item->employee_id ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        @if ($item->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $item->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-extrabold text-slate-950">
                        {{ $item->site_location ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-950 ring-1 ring-inset ring-amber-600/35">
                            {{ $item->equipment_status ?: 'بدون عمل فعلي' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap bg-rose-50/50 px-3.5 py-3.5 text-center">
                        <span class="inline-flex min-w-11 items-center justify-center rounded-lg px-3 py-1 text-sm font-black tabular-nums {{ $daysBadge }}">
                            {{ $days }}
                        </span>
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$item" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 9 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد معدات بدون عمل فعلي مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب تغيير الفلاتر أو إضافة سجل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-950 text-sm font-black text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="8" class="border-e-2 border-slate-700 px-4 py-3.5 text-center text-sm font-black">
                        إجمالي السجلات المعروضة ({{ $records->total() }} معدة)
                    </td>
                    <td class="whitespace-nowrap bg-rose-700 px-4 py-3.5 text-center text-sm font-black text-white tabular-nums">
                        {{ number_format((int) $filteredIdleDaysTotal) }} يوم
                    </td>
                    @foreach ($extraHeaders as $_)
                        <td class="border-s-2 border-slate-700 px-3 py-3.5 text-center text-slate-400">—</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

