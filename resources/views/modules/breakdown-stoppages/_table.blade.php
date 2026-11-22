<div class="overflow-x-auto max-h-[72vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-bold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b border-e border-slate-700/80 px-2.5 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3.5 py-3.5 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-center">رقم وظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-start">المهنة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3 py-3.5 text-center">آخر تشغيل</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-amber-500/60 bg-amber-500/20 px-3 py-3.5 text-center text-amber-200">عدد أيام التوقف</th>
                <th scope="col" class="min-w-52 border-b border-e border-slate-700/80 px-3.5 py-3.5 text-start">تشخيص العطل</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3.5 py-3.5 text-center">موقف الورش</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-3.5 py-3.5 text-start">الإجراء المتخذ</th>
                <th scope="col" class="whitespace-nowrap border-b border-slate-700/80 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $stoppage)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $idleDays = $stoppage->idle_days;
                    $isResolved = $stoppage->status === \App\Enums\DriverStoppageStatus::Resolved;

                    $idleBadgeClasses = match (true) {
                        $isResolved => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
                        $idleDays > 3 => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/25',
                        $idleDays > 0 => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-600/25',
                        default => 'bg-slate-100 text-slate-700',
                    };

                    $ws = (string) ($stoppage->workshop_status ?? '');
                    $workshopBadgeClasses = match (true) {
                        str_contains($ws, 'تم الاصلاح') || str_contains($ws, 'تم الإصلاح') => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
                        str_contains($ws, 'دعم مالي') || str_contains($ws, 'متوقف تسعير') => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
                        str_contains($ws, 'توريد قطع') => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/25',
                        str_contains($ws, 'نقل') => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20',
                        str_contains($ws, 'قيد') || str_contains($ws, 'جاري') => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
                        default => $stoppage->status->badgeClasses(),
                    };
                @endphp
                <tr class="border-b border-slate-200 even:bg-slate-50/75 hover:bg-amber-50/40 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-200 bg-slate-50/60 px-2.5 py-3 text-center text-xs font-bold text-slate-600 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3.5 py-3 text-start">
                        <p class="text-xs font-bold text-slate-900">{{ $stoppage->driver_name }}</p>
                        @if ($stoppage->phone)
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $stoppage->phone }}</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-md bg-slate-50 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200/80">
                            {{ $stoppage->employee_id }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-start text-xs font-medium text-slate-700">
                        {{ $stoppage->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                        @if ($stoppage->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-600/20">
                                {{ $stoppage->branch }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200">
                            {{ $stoppage->equipment_code }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center text-xs font-semibold text-slate-800">
                        {{ $stoppage->equipment_type ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center text-xs font-medium text-slate-700 tabular-nums">
                        {{ $stoppage->stoppage_start_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 bg-amber-50/35 px-3 py-3 text-center">
                        <span class="inline-flex items-center justify-center rounded-lg px-2.5 py-1 text-xs font-bold tabular-nums {{ $idleBadgeClasses }}">
                            {{ $idleDays }} يوم
                        </span>
                    </td>
                    <td class="min-w-52 max-w-xs border-e border-slate-200 px-3.5 py-3 text-start text-xs leading-relaxed text-slate-700">
                        {{ $stoppage->fault_diagnosis ?: ($stoppage->notes ?: '—') }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-3.5 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $workshopBadgeClasses }}">
                            {{ $stoppage->workshop_status ?: $stoppage->status->label() }}
                        </span>
                    </td>
                    <td class="min-w-36 max-w-xs border-e border-slate-200 px-3.5 py-3 text-start text-xs text-slate-600">
                        {{ $stoppage->action_taken ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$stoppage" :label="$stoppage->driver_name" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد سجلات توقف سائقين مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب إعادة ضبط الفلاتر أو إضافة سجل توقف جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
