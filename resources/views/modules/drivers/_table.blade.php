@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table
        :records="$records"
        :headers="$exactHeaders"
        empty-message="لا توجد بيانات سائقين مطابقة لمعايير البحث"
        footer-total-label="إجمالي الرواتب ({{ number_format($records->total()) }} سائق)"
        footer-total-value="{{ number_format((float) ($filteredSalaryTotal ?? 0), 0) }} ر.س"
    />
@else
<div class="overflow-x-auto max-h-[76vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-950 text-[13px] font-black text-white shadow-sm">
            <tr>
                <th scope="col" class="w-12 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الموقع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">المهنة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الجنسية</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-emerald-500/80 bg-emerald-900/60 px-3.5 py-4 text-center text-emerald-100">الراتب</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3.5 py-4 text-center">الكفيل</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $driver)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-2.5 py-1 font-mono text-xs font-black text-white">
                            {{ $driver->equipment_code ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        @if ($driver->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $driver->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $driver->site_location ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $driver->driver_name ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center font-mono text-sm font-black text-slate-950 tabular-nums">
                        <span dir="ltr">{{ $driver->employee_id ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-extrabold text-slate-950">
                        {{ $driver->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-bold text-slate-900">
                        {{ $driver->nationality ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/50 px-3.5 py-3.5 text-center text-sm font-black text-emerald-900 tabular-nums">
                        {{ (float) $driver->salary > 0 ? number_format((float) $driver->salary, 0) : '—' }}
                    </td>
                    <td class="whitespace-nowrap px-3.5 py-3.5 text-center text-sm font-bold text-slate-900">
                        {{ $driver->sponsor ?: '—' }}
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$driver" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 10 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد بيانات سائقين مسجلة حالياً</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV وسيتم ربط الفرع والموقع تلقائياً من توزيعة المعدات.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($records->total() > 0)
            <tfoot class="sticky bottom-0 z-10 bg-slate-950 text-sm font-black text-white">
                <tr class="border-t-2 border-slate-700">
                    <td colspan="8" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">
                        الإجمالي ({{ number_format($records->total()) }} سائق)
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-700 bg-emerald-700 px-4 py-3.5 text-center text-base font-black text-white tabular-nums">
                        {{ number_format((float) ($filteredSalaryTotal ?? 0), 0) }}
                    </td>
                    <td colspan="{{ 1 + count($extraHeaders) }}" class="px-4 py-3.5 text-center text-xs text-slate-300">
                        ريال سعودي
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

