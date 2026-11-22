<div class="overflow-x-auto max-h-[74vh]">
    <table class="min-w-full border-collapse text-[13px]">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b-2 border-e border-slate-700 px-2.5 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">كود السيارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">نوع السيارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">اللوحة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">موديل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">المستلم السابق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الإدارة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">الوظيفة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">نوع الوقود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">الفرع</th>
                <th scope="col" class="min-w-52 border-b-2 border-e border-rose-500/70 bg-rose-500/20 px-3.5 py-3.5 text-start text-rose-200">الحالة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $car)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $condition = (string) ($car->condition_status ?? '');
                    $conditionBadge = match (true) {
                        str_contains($condition, 'ماكينة') || str_contains($condition, 'شاسية') || str_contains($condition, 'بخاخات') || str_contains($condition, 'تربو') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        str_contains($condition, 'بخرة') || str_contains($condition, 'عفشة') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        str_contains($condition, 'فحص') => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/25',
                        default => 'bg-slate-100 text-slate-800',
                    };

                    $fuel = (string) ($car->fuel_type ?? '');
                    $fuelBadge = $fuel === 'ديزل'
                        ? 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30'
                        : 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/25';
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-2.5 py-3 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300">
                            {{ $car->car_code ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-extrabold text-slate-950">
                        {{ $car->car_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center font-extrabold text-slate-900">
                        {{ $car->plate_number ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-extrabold text-slate-900 tabular-nums">
                        {{ $car->model_year ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold {{ $car->previous_receiver === 'بدون' ? 'text-slate-400' : 'text-slate-900' }}">
                        {{ $car->previous_receiver ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-extrabold tabular-nums {{ $car->employee_id === 'بدون' ? 'text-slate-400' : 'text-slate-900' }}">
                        <span dir="ltr">{{ $car->employee_id ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        @if ($car->department && $car->department !== 'بدون')
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-300">
                                {{ $car->department }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">{{ $car->department ?: '—' }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold {{ $car->job_title === 'بدون' ? 'text-slate-400' : 'text-slate-800' }}">
                        {{ $car->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        @if ($fuel !== '')
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold {{ $fuelBadge }}">
                                {{ $fuel }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        @if ($car->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 ring-1 ring-inset ring-sky-600/25">
                                {{ $car->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-52 border-e border-slate-300 bg-rose-50/30 px-3.5 py-3 text-start">
                        @if ($condition !== '')
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold leading-snug {{ $conditionBadge }}">
                                {{ $condition }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$car" :label="$car->car_code ?: ($car->plate_number ?: 'عطل سيارة')" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد أعطال سيارات مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة سجل عطل سيارة جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />

