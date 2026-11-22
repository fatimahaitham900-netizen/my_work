<div class="overflow-x-auto max-h-[70vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-bold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b border-e border-slate-700/80 px-3 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">المهنة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">الرقم الوظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">الموقع</th>
                <th scope="col" class="min-w-52 border-b border-e border-slate-700/80 px-4 py-3.5 text-start">ملاحظات</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">تاريخ التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-amber-500/60 bg-amber-500/20 px-4 py-3.5 text-center text-amber-200">إجمالي أيام التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b border-slate-700/80 px-4 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $driver)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $idleDays = $driver->idle_duration_days;
                    $idleBadge = match (true) {
                        $idleDays >= 14 => 'bg-rose-100 text-rose-800 ring-1 ring-inset ring-rose-600/25',
                        $idleDays >= 7 => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-600/25',
                        default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
                    };
                    $licenseDays = $driver->license_days_remaining;
                @endphp
                <tr class="border-b border-slate-200 even:bg-slate-50/75 hover:bg-amber-50/40 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-200 bg-slate-50/60 px-3 py-3.5 text-center text-xs font-bold text-slate-600 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3.5 text-start">
                        <p class="text-xs font-bold text-slate-900">{{ $driver->driver_name }}</p>
                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] text-slate-400">{{ $driver->license_type }}</span>
                            @if ($driver->is_license_expired)
                                <span class="inline-flex items-center rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800">
                                    رخصة منتهية ({{ abs((int) $licenseDays) }} يوم)
                                </span>
                            @elseif ($driver->is_license_expiring_soon)
                                <span class="inline-flex items-center rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">
                                    تنتهي خلال {{ $licenseDays }} يوم
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3.5 text-center text-xs font-semibold text-slate-800">
                        {{ $driver->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3.5 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200">
                            {{ $driver->employee_id }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3.5 text-center">
                        @if ($driver->location)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-600/20">
                                {{ $driver->location }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="min-w-52 max-w-sm border-e border-slate-200 px-4 py-3.5 text-start">
                        @if ($driver->notes)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-800 ring-1 ring-inset ring-rose-600/20 leading-relaxed">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                                <span>{{ $driver->notes }}</span>
                            </span>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3.5 text-center text-xs font-semibold text-slate-800 tabular-nums">
                        {{ $driver->unassigned_since?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 bg-amber-50/35 px-4 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-bold tabular-nums {{ $idleBadge }}">
                            {{ $idleDays }} يوم
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3.5 text-center">
                        <x-module.row-actions :record="$driver" :label="$driver->driver_name" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد سجلات سائقين بدون معدات مطابقة للبحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة سجل سائق جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
