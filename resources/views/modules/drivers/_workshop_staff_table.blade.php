@php
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $totalSalaryDisplay = number_format((float) ($filteredSalaryTotal ?? 0), 0).' ر.س';
@endphp

<div class="space-y-4 p-4 sm:p-5">
    {{-- KPI Summary Strip for كادر الورش --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border-2 border-slate-900 bg-white p-3.5 text-center shadow-xs">
            <p class="text-xs font-black text-slate-600">إجمالي كادر الورش</p>
            <p class="mt-1 text-2xl sm:text-3xl font-black text-slate-950 tabular-nums">{{ number_format((int) ($kpis['workshop_staff_total'] ?? $records->total())) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-emerald-700 bg-emerald-50/70 p-3.5 text-center shadow-xs">
            <p class="text-xs font-black text-emerald-900">النشطون</p>
            <p class="mt-1 text-2xl sm:text-3xl font-black text-emerald-700 tabular-nums">{{ number_format((int) ($kpis['workshop_staff_active'] ?? 0)) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-amber-600 bg-amber-50/70 p-3.5 text-center shadow-xs">
            <p class="text-xs font-black text-amber-900">عدد الورش / الفروع</p>
            <p class="mt-1 text-2xl sm:text-3xl font-black text-amber-700 tabular-nums">{{ number_format((int) ($kpis['workshop_branches_count'] ?? 0)) }}</p>
        </div>
        <div class="rounded-2xl border-2 border-sky-700 bg-sky-50/70 p-3.5 text-center shadow-xs">
            <p class="text-xs font-black text-sky-900">إجمالي الرواتب (ر.س)</p>
            <p class="mt-1 text-2xl sm:text-3xl font-black text-sky-800 tabular-nums">{{ number_format((float) ($filteredSalaryTotal ?? 0), 0) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
        <div class="bg-slate-950 px-5 py-3 text-center text-base sm:text-lg font-black text-white">
            جدول كادر الورش (الإجمالي: {{ number_format($records->total()) }})
        </div>

        @if ($exactHeaders !== [])
            <x-module.exact-table
                :records="$records"
                :headers="$exactHeaders"
                empty-message="لا يوجد موظفون مسجلون في كادر الورش حالياً"
                footer-total-label="إجمالي الرواتب"
                :footer-total-value="$totalSalaryDisplay"
            />
        @else
            <div class="max-h-[74vh] overflow-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="sticky top-0 z-20 bg-slate-900 text-xs sm:text-[13px] font-black text-white shadow-sm">
                        <tr>
                            <th scope="col" class="sticky top-0 z-20 w-14 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-3 py-3.5 text-center">م</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الاسم</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الرقم الوظيفي</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الجنسية</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">المهنه</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-emerald-700 bg-emerald-900 px-4 py-3.5 text-center text-emerald-100">الراتب الإجمالي</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-e-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الفرع</th>
                            <th scope="col" class="sticky top-0 z-20 whitespace-nowrap border-b-2 border-slate-700 bg-slate-900 px-4 py-3.5 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($records as $item)
                            @php
                                $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                                $statusText = trim((string) ($item->status ?? '')) ?: 'نشط';
                                $isActive = str_contains($statusText, 'نشط');
                            @endphp
                            <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3 text-center text-sm font-black text-slate-950 tabular-nums">
                                    {{ $rowNumber }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-950">
                                    {{ $item->driver_name ?: '—' }}
                                </td>
                                <td dir="ltr" class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-900 tabular-nums">
                                    {{ $item->employee_id ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-bold text-slate-800">
                                    {{ $item->nationality ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-bold text-slate-900">
                                    {{ $item->job_title ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 bg-emerald-50/50 px-4 py-3 text-center text-sm font-black text-emerald-900 tabular-nums">
                                    {{ number_format((float) $item->salary, 0) }}
                                </td>
                                <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3 text-center text-sm font-black text-slate-900">
                                    {{ $item->branch ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $isActive ? 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30' : 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30' }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <p class="text-base font-black text-slate-900">لا يوجد موظفون مسجلون في كادر الورش حالياً</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">يمكنك استيراد ملف Excel/CSV لكادر الورش أو إضافة موظف جديد من الأعلى.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($records->total() > 0)
                        <tfoot class="sticky bottom-0 z-10 bg-slate-950 text-sm font-black text-white">
                            <tr class="border-t-2 border-slate-700">
                                <td colspan="5" class="border-e-2 border-slate-700 px-4 py-3.5 text-center">إجمالي رواتب كادر الورش</td>
                                <td class="whitespace-nowrap border-e-2 border-slate-700 bg-emerald-800 px-4 py-3.5 text-center text-base tabular-nums">
                                    {{ number_format((float) ($filteredSalaryTotal ?? 0), 0) }} ر.س
                                </td>
                                <td colspan="2" class="px-4 py-3.5 text-center">
                                    {{ number_format($records->total()) }} موظف
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <x-module.pagination :paginator="$records" />
        @endif
    </div>
</div>

