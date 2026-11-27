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
                <th scope="col" class="w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الكود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">التصنيف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">نوع السيارة / الباص</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">موديل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">المستلم السابق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">الإدارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">الوظيفة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">نوع الوقود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">الفرع</th>
                <th scope="col" class="min-w-52 border-b-2 border-rose-500/80 bg-rose-900/60 px-3.5 py-4 text-start text-rose-100">الحالة</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $car)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $condition = (string) ($car->condition_status ?? '');
                    $conditionBadge = match (true) {
                        str_contains($condition, 'ماكينة') || str_contains($condition, 'شاسية') || str_contains($condition, 'بخاخات') || str_contains($condition, 'تربو') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/35',
                        str_contains($condition, 'بخرة') || str_contains($condition, 'عفشة') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35',
                        str_contains($condition, 'فحص') => 'bg-sky-100 text-sky-950 ring-1 ring-inset ring-sky-600/30',
                        default => 'bg-slate-100 text-slate-900',
                    };

                    $fuel = (string) ($car->fuel_type ?? '');
                    $fuelBadge = $fuel === 'ديزل'
                        ? 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/35'
                        : 'bg-emerald-100 text-emerald-950 ring-1 ring-inset ring-emerald-600/30';
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $car->car_code ?: '—' }}
                        </span>
                    </td>
                    @php
                        $categoryLabel = $car->vehicle_category ?: \App\Models\CarBreakdown::CATEGORY_CAR;
                        $isBus = $categoryLabel === \App\Models\CarBreakdown::CATEGORY_BUS;
                    @endphp
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $isBus ? 'bg-purple-100 text-purple-950 ring-1 ring-inset ring-purple-600/35' : 'bg-blue-100 text-blue-950 ring-1 ring-inset ring-blue-600/30' }}">
                            {{ $categoryLabel }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-black text-slate-950">
                        {{ $car->car_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-black text-slate-950">
                        {{ $car->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $car->model_year ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-extrabold {{ $car->previous_receiver === 'بدون' ? 'text-slate-400' : 'text-slate-950' }}">
                        {{ $car->previous_receiver ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center font-mono text-sm font-black tabular-nums {{ $car->employee_id === 'بدون' ? 'text-slate-400' : 'text-slate-950' }}">
                        <span dir="ltr">{{ $car->employee_id ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center">
                        @if ($car->department && $car->department !== 'بدون')
                            <span class="inline-flex items-center rounded-full bg-slate-200/80 px-3 py-1 text-xs font-black text-slate-950 ring-1 ring-inset ring-slate-400/50">
                                {{ $car->department }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">{{ $car->department ?: '—' }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-extrabold {{ $car->job_title === 'بدون' ? 'text-slate-400' : 'text-slate-900' }}">
                        {{ $car->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center">
                        @if ($fuel !== '')
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $fuelBadge }}">
                                {{ $fuel }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3 py-3.5 text-center">
                        @if ($car->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $car->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-52 bg-rose-50/40 px-3.5 py-3.5 text-start">
                        @if ($condition !== '')
                            <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-black leading-snug {{ $conditionBadge }}">
                                {{ $condition }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$car" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 13 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد أعطال سيارات مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب تغيير الفلاتر أو إضافة سجل عطل سيارة جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif

