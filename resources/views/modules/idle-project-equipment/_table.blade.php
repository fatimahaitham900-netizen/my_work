<div class="overflow-x-auto max-h-[74vh]">
    <table class="min-w-full border-collapse text-[13px]">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b-2 border-e border-slate-700 px-2.5 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">الموقع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">حالة المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-rose-500/70 bg-rose-500/20 px-3.5 py-3.5 text-center text-rose-200">إجمالي أيام التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $days = (int) $item->total_idle_days;
                    $daysBadge = match (true) {
                        $days >= 7 => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                        $days >= 4 => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        default => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/25',
                    };
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-2.5 py-3 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300">
                            {{ $item->equipment_code ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center font-extrabold text-slate-900">
                        {{ $item->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold text-slate-950">
                        {{ $item->driver_name ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-extrabold text-slate-900 tabular-nums">
                        <span dir="ltr">{{ $item->employee_id ?: '—' }}</span>
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
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold text-slate-900">
                        {{ $item->site_location ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-extrabold text-amber-950 ring-1 ring-inset ring-amber-600/25">
                            {{ $item->equipment_status ?: 'بدون عمل فعلي' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 bg-rose-50/40 px-3.5 py-3 text-center">
                        <span class="inline-flex min-w-10 items-center justify-center rounded-lg px-2.5 py-1 text-sm font-extrabold tabular-nums {{ $daysBadge }}">
                            {{ $days }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$item" :label="$item->equipment_code ?: ($item->driver_name ?: 'معدة بدون عمل')" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد معدات بدون عمل فعلي مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة سجل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-900 text-xs font-extrabold text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="8" class="border-e border-slate-700 px-4 py-3.5 text-center text-sm font-extrabold">
                        إجمالي السجلات المعروضة ({{ $records->total() }} معدة)
                    </td>
                    <td class="whitespace-nowrap border-e border-rose-500/70 bg-rose-600/90 px-4 py-3.5 text-center text-sm font-extrabold text-white tabular-nums">
                        {{ number_format((int) $filteredIdleDaysTotal) }} يوم
                    </td>
                    <td class="px-3 py-3.5 text-center text-xs text-slate-300">—</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />

