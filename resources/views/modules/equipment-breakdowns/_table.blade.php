<div class="overflow-x-auto max-h-[74vh]">
    <table class="min-w-full border-collapse text-[13px]">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-extrabold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-12 border-b-2 border-e border-slate-700 px-2.5 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">النوع</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">الكود</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">الورشة</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">تاريخ التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-amber-500/70 bg-amber-500/20 px-3 py-3.5 text-center text-amber-200">أيام التوقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">حجم العطل</th>
                <th scope="col" class="min-w-52 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">العطل</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">الموقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-center">المبلغ</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-e border-slate-700 px-3 py-3.5 text-center">تاريخ الإصلاح</th>
                <th scope="col" class="min-w-64 border-b-2 border-e border-slate-700 px-3.5 py-3.5 text-start">ملاحظات الموقف</th>
                <th scope="col" class="whitespace-nowrap border-b-2 border-slate-700 px-3 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $breakdown)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;

                    $sizeLabel = match ($breakdown->severity) {
                        \App\Enums\BreakdownSeverity::Critical => 'ثقيل',
                        \App\Enums\BreakdownSeverity::Medium => 'متوسط',
                        \App\Enums\BreakdownSeverity::Minor => 'خفيف',
                        default => '—',
                    };

                    $sizeBadge = match ($breakdown->severity) {
                        \App\Enums\BreakdownSeverity::Critical => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        \App\Enums\BreakdownSeverity::Medium => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-600/30',
                        \App\Enums\BreakdownSeverity::Minor => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/25',
                        default => 'bg-slate-100 text-slate-700',
                    };

                    $ws = (string) ($breakdown->workshop_status ?: ($breakdown->repair_status?->label() ?? '—'));
                    $statusBadge = match (true) {
                        $breakdown->repair_status === \App\Enums\RepairStatus::Repaired => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-600/30',
                        str_contains($ws, 'دعم مالي') => 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30',
                        str_contains($ws, 'متوقف تسعير') => 'bg-orange-100 text-orange-900 ring-1 ring-inset ring-orange-600/30',
                        str_contains($ws, 'توريد قطع') => 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/30',
                        str_contains($ws, 'قيد') => 'bg-sky-100 text-sky-900 ring-1 ring-inset ring-sky-600/30',
                        str_contains($ws, 'كشف') => 'bg-indigo-100 text-indigo-900 ring-1 ring-inset ring-indigo-600/30',
                        str_contains($ws, 'نقل') => 'bg-purple-100 text-purple-900 ring-1 ring-inset ring-purple-600/30',
                        str_contains($ws, 'مؤجل') => 'bg-slate-200 text-slate-800 ring-1 ring-inset ring-slate-500/25',
                        default => 'bg-slate-100 text-slate-800',
                    };
                @endphp
                <tr class="border-b border-slate-300 even:bg-slate-50/90 hover:bg-amber-50/60 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-300 bg-slate-100/70 px-2.5 py-3 text-center text-xs font-extrabold text-slate-800 tabular-nums">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-start font-bold text-slate-900">
                        {{ $breakdown->equipment_name ?: '—' }}
                        @if (($breakdown->waiting_stoppages_count ?? 0) > 0)
                            <span class="mt-1 block text-[11px] font-bold text-rose-700">
                                ({{ $breakdown->waiting_stoppages_count }} سائق متوقف)
                            </span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-800">
                        <span dir="ltr">{{ $breakdown->brand ?: '—' }}</span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <code dir="ltr" class="inline-block rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-extrabold text-slate-950 ring-1 ring-inset ring-slate-300">
                            {{ $breakdown->equipment_code ?: '—' }}
                        </code>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        @if ($breakdown->workshop_name)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 ring-1 ring-inset ring-sky-600/25">
                                {{ $breakdown->workshop_name }}
                            </span>
                        @else
                            <span class="font-bold text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900 tabular-nums">
                        {{ $breakdown->breakdown_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 bg-amber-50/40 px-3 py-3 text-center">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums {{ $breakdown->downtime_days >= 30 ? 'bg-rose-100 text-rose-900 ring-1 ring-inset ring-rose-600/30' : 'bg-amber-100 text-amber-950 ring-1 ring-inset ring-amber-600/25' }}">
                            {{ $breakdown->downtime_days }} يوم
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-extrabold {{ $sizeBadge }}">
                            {{ $sizeLabel }}
                        </span>
                    </td>
                    <td class="min-w-52 max-w-xs border-e border-slate-300 px-3.5 py-3 text-start font-bold leading-relaxed text-slate-900">
                        {{ $breakdown->fault_description ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold {{ $statusBadge }}">
                                {{ $ws }}
                            </span>
                            @if ($breakdown->spare_parts_delay)
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-extrabold text-amber-950 ring-1 ring-inset ring-amber-600/30">
                                    تأخر قطع غيار
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3.5 py-3 text-center font-extrabold text-slate-950 tabular-nums">
                        @if ($breakdown->cost_note)
                            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-900 ring-1 ring-inset ring-indigo-600/25">
                                {{ $breakdown->cost_note }}
                            </span>
                        @elseif ((float) $breakdown->estimated_cost > 0)
                            {{ number_format((float) $breakdown->estimated_cost, 0) }}
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-300 px-3 py-3 text-center font-bold text-slate-900 tabular-nums">
                        {{ $breakdown->expected_repair_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="min-w-64 max-w-md border-e border-slate-300 px-3.5 py-3 text-start text-xs font-bold leading-relaxed text-slate-800">
                        {{ $breakdown->notes ?: '—' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-center">
                        <x-module.row-actions :record="$breakdown" :label="$breakdown->equipment_code ?: ($breakdown->equipment_name ?: 'سجل عطل')">
                            @if ($breakdown->repair_status !== \App\Enums\RepairStatus::Repaired)
                                @can('update', $breakdown)
                                    <button
                                        type="button"
                                        data-action="mark-repaired"
                                        data-id="{{ $breakdown->id }}"
                                        title="تسجيل إتمام الإصلاح"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </button>
                                @endcan
                            @endif
                        </x-module.row-actions>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد أعطال معدات مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو تسجيل عطل جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
