<div class="overflow-x-auto max-h-[70vh]">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-slate-900 text-xs font-bold text-white shadow-xs">
            <tr>
                <th scope="col" class="w-14 border-b border-e border-slate-700/80 px-3 py-3.5 text-center">م</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">كود المعدة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-start">البيان</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">الفرع</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">رقم الهيكل</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-center">الحالة</th>
                <th scope="col" class="whitespace-nowrap border-b border-e border-slate-700/80 px-4 py-3.5 text-start">ملاحظات</th>
                <th scope="col" class="whitespace-nowrap border-b border-slate-700/80 px-4 py-3.5 text-center">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $transfer)
                @php
                    $rowNumber = ($records->firstItem() ?? 1) + $loop->index;
                    $noteText = $transfer->notes ?: ($transfer->blocker_reason ?: '—');
                @endphp
                <tr class="border-b border-slate-200 even:bg-slate-50/75 hover:bg-amber-50/50 transition-colors">
                    <td class="whitespace-nowrap border-e border-slate-200 px-3 py-3 text-center text-xs font-bold text-slate-600 tabular-nums bg-slate-50/50">
                        {{ $rowNumber }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3 text-center">
                        <span dir="ltr" class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-900 ring-1 ring-inset ring-slate-200">
                            {{ $transfer->equipment_code }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3 text-start text-xs font-bold text-slate-800">
                        {{ $transfer->equipment_type }}
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3 text-center">
                        @if ($transfer->branch)
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 ring-1 ring-inset ring-sky-600/20">
                                {{ $transfer->branch }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">{{ $transfer->current_owner }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3 text-center">
                        @if ($transfer->chassis_number)
                            <code dir="ltr" class="inline-block rounded-md bg-slate-50 px-2.5 py-1 font-mono text-xs font-semibold tracking-wide text-slate-800 ring-1 ring-inset ring-slate-200/80 select-all">
                                {{ $transfer->chassis_number }}
                            </code>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap border-e border-slate-200 px-4 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $transfer->status->badgeClasses() }}">
                            {{ $transfer->status->label() }}
                        </span>
                    </td>
                    <td class="border-e border-slate-200 px-4 py-3 text-start">
                        @if (str_contains($noteText, 'جمركية'))
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-600/20">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                <span>{{ $noteText }}</span>
                            </span>
                        @else
                            <span class="text-xs text-slate-700">{{ $noteText }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-center">
                        <x-module.row-actions :record="$transfer" :label="$transfer->equipment_code" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد سجلات نقل ملكية مطابقة لمعايير البحث</p>
                        <p class="mt-1 text-xs text-slate-500">جرب تغيير الفلاتر أو إضافة سجل نقل ملكية جديد.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-module.pagination :paginator="$records" />
