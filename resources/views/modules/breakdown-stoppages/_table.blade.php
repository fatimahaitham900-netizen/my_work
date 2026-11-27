@php
    $breakdownsMap = $breakdownsByCode ?? [];
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
                <th scope="col" class="w-12 border-b-2 border-e-2 border-slate-700 px-3 py-4 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-start">اسم السائق</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">رقم وظيفي</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-start">المهنة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">نوع المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-3.5 py-4 text-center">آخر تشغيل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-amber-500/80 bg-amber-500/25 px-3.5 py-4 text-center text-amber-200">عدد أيام التوقف</th>
                <th scope="col" class="min-w-52 border-b-2 border-e-2 border-slate-700 px-4 py-4 text-start">تشخيص العطل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e-2 border-slate-700 px-4 py-4 text-center">الحالة</th>
                <th scope="col" class="min-w-64 border-b-2 border-emerald-500/80 bg-emerald-600/25 px-4 py-4 text-start text-emerald-200">موقف الورش (من كشف الأعطال)</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $stoppage)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $idleDays = $stoppage->idle_days;
                    $isResolved = $stoppage->status === \App\Enums\DriverStoppageStatus::Resolved;

                    $normCode = \App\Services\BreakdownDriverStoppageService::normalizeEquipmentCode($stoppage->equipment_code);
                    /** @var \App\Models\EquipmentBreakdown|null $matchedBreakdown */
                    $matchedBreakdown = $normCode !== '' ? ($breakdownsMap[$normCode] ?? null) : null;

                    $idleBadgeClasses = match (true) {
                        $isResolved => 'bg-emerald-100 text-emerald-900 ring-2 ring-inset ring-emerald-600/35',
                        $idleDays > 3 => 'bg-rose-100 text-rose-800 ring-2 ring-inset ring-rose-600/40',
                        $idleDays > 0 => 'bg-amber-100 text-amber-950 ring-2 ring-inset ring-amber-600/40',
                        default => 'bg-slate-100 text-slate-900 ring-1 ring-inset ring-slate-400',
                    };

                    $ws = (string) ($matchedBreakdown?->workshop_status ?: ($stoppage->workshop_status ?? ''));
                    $workshopBadgeClasses = match (true) {
                        str_contains($ws, 'تم الاصلاح') || str_contains($ws, 'تم الإصلاح') => 'bg-emerald-100 text-emerald-900 ring-2 ring-inset ring-emerald-600/35',
                        str_contains($ws, 'دعم مالي') || str_contains($ws, 'متوقف تسعير') => 'bg-rose-100 text-rose-900 ring-2 ring-inset ring-rose-600/35',
                        str_contains($ws, 'توريد قطع') => 'bg-amber-100 text-amber-950 ring-2 ring-inset ring-amber-600/35',
                        str_contains($ws, 'نقل') => 'bg-indigo-100 text-indigo-900 ring-2 ring-inset ring-indigo-600/35',
                        str_contains($ws, 'قيد') || str_contains($ws, 'جاري') => 'bg-sky-100 text-sky-900 ring-2 ring-inset ring-sky-600/35',
                        default => $stoppage->status->badgeClasses(),
                    };
                @endphp
                <tr class="border-b-2 border-slate-800 even:bg-slate-100/75 hover:bg-amber-50/70 transition-colors">
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-slate-200/60 px-3 py-3.5 text-center text-sm font-black text-slate-950 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-start">
                        <p class="text-sm font-black text-slate-950">{{ $stoppage->driver_name ?: '—' }}</p>
                        @if ($stoppage->phone)
                            <p class="mt-0.5 font-mono text-xs font-bold text-slate-600">{{ $stoppage->phone }}</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-400">
                            {{ $stoppage->employee_id ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-start text-sm font-bold text-slate-900">
                        {{ $stoppage->job_title ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        @if ($stoppage->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-extrabold text-sky-950 ring-1 ring-inset ring-sky-600/30">
                                {{ $stoppage->branch }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-900 px-2.5 py-1 font-mono text-xs font-black text-white shadow-xs">
                            {{ $stoppage->equipment_code ?: '—' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-extrabold text-slate-950">
                        {{ $stoppage->equipment_type ?: ($matchedBreakdown?->equipment_name ?: '—') }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-3.5 py-3.5 text-center text-sm font-extrabold text-slate-950 tabular-nums">
                        {{ $stoppage->stoppage_start_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 bg-amber-50/50 px-3.5 py-3.5 text-center">
                        <span class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-black tabular-nums {{ $idleBadgeClasses }}">
                            {{ $idleDays }} يوم
                        </span>
                    </td>
                    <td class="min-w-52 max-w-xs border-e-2 border-slate-400 px-4 py-3.5 text-start text-sm font-bold leading-relaxed text-slate-950">
                        {{ $stoppage->fault_diagnosis ?: ($matchedBreakdown?->fault_description ?: ($stoppage->notes ?: '—')) }}
                    </td>
                    <td class="whitespace-nowrap border-e-2 border-slate-400 px-4 py-3.5 text-center">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $workshopBadgeClasses }}">
                            {{ $ws !== '' ? $ws : $stoppage->status->label() }}
                        </span>
                    </td>
                    <td class="min-w-64 max-w-md bg-emerald-50/35 px-4 py-3.5 text-start text-sm font-bold leading-relaxed text-slate-950">
                        @php
                            $workshopNote = trim((string) ($matchedBreakdown?->notes ?? ''));
                        @endphp
                        @if ($workshopNote !== '' && $workshopNote !== '—' && $workshopNote !== '-')
                            <p class="rounded-lg bg-emerald-100/80 px-3 py-1.5 text-xs font-extrabold text-emerald-950 ring-1 ring-inset ring-emerald-600/30">
                                {{ $workshopNote }}
                            </p>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$stoppage" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 12 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-base font-black text-slate-900">لا توجد سجلات توقف سائقين مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">جرب إعادة ضبط الفلاتر أو استيراد ملف جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif
