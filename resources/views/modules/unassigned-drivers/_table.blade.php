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
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">المهنة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الموقع</th>
                <th scope="col" class="min-w-60 border-b-2 border-slate-700 px-4 py-4 text-start">ملاحظات</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $driver)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $licenseDays = $driver->license_days_remaining;
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-start">
                        <p class="text-sm font-black text-slate-950">{{ $driver->driver_name ?: '—' }}</p>
                        @if ($driver->license_type || $driver->is_license_expired || $driver->is_license_expiring_soon)
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                @if ($driver->license_type)
                                    <span class="text-xs font-bold text-slate-600">{{ $driver->license_type }}</span>
                                @endif
                                @if ($driver->is_license_expired)
                                    <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-extrabold text-rose-900 ring-1 ring-inset ring-rose-600/30">
                                        رخصة منتهية ({{ abs((int) $licenseDays) }} يوم)
                                    </span>
                                @elseif ($driver->is_license_expiring_soon)
                                    <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-extrabold text-amber-950 ring-1 ring-inset ring-amber-600/30">
                                        تنتهي خلال {{ $licenseDays }} يوم
                                    </span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center text-sm font-extrabold text-slate-950">
                        {{ $driver->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $driver->employee_id ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        @if ($driver->location)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3.5 py-1 text-xs font-extrabold text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $driver->location }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-60 max-w-md px-4 py-3.5 text-start">
                        @if ($driver->notes)
                            <span class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-extrabold text-rose-950 ring-1 ring-inset ring-rose-600/30 leading-relaxed">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-rose-600"></span>
                                <span>{{ $driver->notes }}</span>
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$driver" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد سجلات سائقين بدون معدات مطابقة للبحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب تغيير الفلاتر أو استيراد ملف جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif
