@php
    $canEditRows = (bool) auth()->user()?->canManageDeviations();
    $exactHeaders = \App\Support\ExtraColumns::exactHeaders($records);
    $extraHeaders = \App\Support\ExtraColumns::headers($records);
@endphp

@if ($exactHeaders !== [])
    <x-module.exact-table :records="$records" :headers="$exactHeaders" />
@else
<div class="overflow-x-auto">
    <table class="min-w-full border-collapse text-center text-base sm:text-lg">
        <thead>
            <tr class="border-b-2 border-black bg-sky-200/85 text-black">
                <th scope="col" class="border-e-2 border-black px-3 py-3.5 text-center font-black">التاريخ</th>
                <th scope="col" class="border-e-2 border-black px-4 py-3.5 text-center font-black">نسبة الصيانة اليومية</th>
                <th scope="col" class="border-e-2 border-black px-4 py-3.5 text-center font-black">نسبة الصيانة الوقائية</th>
                <th scope="col" class="border-e-2 border-black px-4 py-3.5 text-center font-black">نسبة انحراف الصيانة اليومية</th>
                <th scope="col" class="px-4 py-3.5 text-center font-black">نسبة انحراف الصيانة الوقائية</th>
                <x-module.extra-headers :headers="$extraHeaders" />
            </tr>
        </thead>
        <tbody class="bg-white">
            @forelse ($records as $item)
                @php
                    $isOnLeave = $item->row_type === 'on_leave';
                    $isDayOff = $item->row_type === 'day_off';
                    $isEmptyActive = $item->row_type === 'active'
                        && $item->daily_maintenance_rate === null
                        && $item->preventive_maintenance_rate === null;

                    $rowBgClass = match (true) {
                        $isOnLeave => 'bg-slate-300/80 hover:bg-slate-300',
                        $isDayOff => 'bg-sky-200/75 hover:bg-sky-200',
                        default => 'bg-white even:bg-slate-50/50 hover:bg-sky-50/50',
                    };

                    $initialDaily = $item->daily_maintenance_rate !== null ? (string) ((int) round((float) $item->daily_maintenance_rate)) : '';
                    $initialPreventive = $item->preventive_maintenance_rate !== null ? (string) ((int) round((float) $item->preventive_maintenance_rate)) : '';
                    $initialDailyDev = $item->daily_deviation_rate !== null ? (string) ((int) round((float) $item->daily_deviation_rate)) : '';
                    $initialPreventiveDev = $item->preventive_deviation_rate !== null ? (string) ((int) round((float) $item->preventive_deviation_rate)) : '';
                @endphp
                <tr
                    x-data="{
                        inlineEditing: false,
                        savingInline: false,
                        rowType: @js($item->row_type ?: 'active'),
                        dailyRate: @js($initialDaily),
                        preventiveRate: @js($initialPreventive),
                        dailyDev: @js($initialDailyDev),
                        preventiveDev: @js($initialPreventiveDev),
                        statusNote: @js($item->status_note ?? ''),
                        autoCalc() {
                            const d = parseFloat(this.dailyRate);
                            if (!isNaN(d)) {
                                this.dailyDev = String(Math.max(0, 100 - d));
                            }
                            const p = parseFloat(this.preventiveRate);
                            if (!isNaN(p)) {
                                this.preventiveDev = String(Math.max(0, 100 - p));
                            }
                        },
                        async saveInline() {
                            if (this.savingInline) return;
                            this.savingInline = true;
                            try {
                                const response = await fetch(@js(route('maintenance-deviations.update', $item)), {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({
                                        day_number: @js($item->day_number),
                                        row_type: this.rowType,
                                        daily_maintenance_rate: this.rowType === 'active' ? this.dailyRate : null,
                                        preventive_maintenance_rate: this.rowType === 'active' ? this.preventiveRate : null,
                                        daily_deviation_rate: this.rowType === 'active' ? this.dailyDev : null,
                                        preventive_deviation_rate: this.rowType === 'active' ? this.preventiveDev : null,
                                        status_note: this.rowType !== 'active' ? (this.statusNote || (this.rowType === 'on_leave' ? 'مسؤول الصيانة مجاز' : '')) : null,
                                    }),
                                });
                                if (response.ok) {
                                    this.inlineEditing = false;
                                    if (window.Alpine && Alpine.store('toast')) {
                                        Alpine.store('toast').push('تم حفظ بيانات اليوم بنجاح');
                                    }
                                    await applyFilters();
                                }
                            } finally {
                                this.savingInline = false;
                            }
                        }
                    }"
                    class="border-b-2 border-black {{ $rowBgClass }} transition-colors"
                >
                    {{-- التاريخ --}}
                    <td class="whitespace-nowrap border-e-2 border-black px-3 py-3 font-black text-black tabular-nums">
                        {{ $item->day_number ?? '—' }}
                    </td>

                    {{-- نسبة الصيانة اليومية --}}
                    <td
                        @if ($canEditRows)
                            @click="if (!inlineEditing) { inlineEditing = true; }"
                        @endif
                        class="whitespace-nowrap border-e-2 border-black px-3 py-2.5 font-black text-black tabular-nums {{ $canEditRows ? 'cursor-pointer' : '' }}"
                    >
                        <template x-if="!inlineEditing">
                            <span>
                                @if (! $isOnLeave && ! $isDayOff && $item->daily_maintenance_rate !== null)
                                    {{ (int) round((float) $item->daily_maintenance_rate) }}%
                                @elseif ($isEmptyActive && $canEditRows)
                                    <span class="text-xs font-bold text-slate-400 hover:text-slate-700">+ اضغط للإدخال</span>
                                @endif
                            </span>
                        </template>
                        <template x-if="inlineEditing">
                            <div class="flex items-center justify-center gap-1.5" @click.stop>
                                <select
                                    x-model="rowType"
                                    class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs font-bold text-slate-800 focus:border-slate-900 focus:outline-none"
                                >
                                    <option value="active">نسبة %</option>
                                    <option value="on_leave">مسؤول مجاز</option>
                                    <option value="day_off">إجازة (أزرق)</option>
                                </select>
                                <input
                                    x-show="rowType === 'active'"
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="99"
                                    x-model="dailyRate"
                                    @input="autoCalc()"
                                    @keydown.enter.prevent="saveInline()"
                                    class="w-20 rounded-lg border-2 border-slate-900 bg-white px-2 py-1 text-center text-sm font-black text-black focus:outline-none"
                                >
                            </div>
                        </template>
                    </td>

                    {{-- نسبة الصيانة الوقائية --}}
                    <td
                        @if ($canEditRows)
                            @click="if (!inlineEditing) { inlineEditing = true; }"
                        @endif
                        class="whitespace-nowrap border-e-2 border-black px-3 py-2.5 font-black text-black tabular-nums {{ $canEditRows ? 'cursor-pointer' : '' }}"
                    >
                        <template x-if="!inlineEditing">
                            <span>
                                @if (! $isOnLeave && ! $isDayOff && $item->preventive_maintenance_rate !== null)
                                    {{ (int) round((float) $item->preventive_maintenance_rate) }}%
                                @endif
                            </span>
                        </template>
                        <template x-if="inlineEditing">
                            <div @click.stop>
                                <input
                                    x-show="rowType === 'active'"
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="74"
                                    x-model="preventiveRate"
                                    @input="autoCalc()"
                                    @keydown.enter.prevent="saveInline()"
                                    class="w-20 rounded-lg border-2 border-slate-900 bg-white px-2 py-1 text-center text-sm font-black text-black focus:outline-none"
                                >
                            </div>
                        </template>
                    </td>

                    {{-- نسبة انحراف الصيانة اليومية --}}
                    <td
                        @if ($canEditRows)
                            @click="if (!inlineEditing) { inlineEditing = true; }"
                        @endif
                        class="whitespace-nowrap border-e-2 border-black px-3 py-2.5 font-black text-blue-700 tabular-nums {{ $canEditRows ? 'cursor-pointer' : '' }}"
                    >
                        <template x-if="!inlineEditing">
                            <span>
                                @if (! $isOnLeave && ! $isDayOff && $item->daily_deviation_rate !== null)
                                    {{ (int) round((float) $item->daily_deviation_rate) }}%
                                @endif
                            </span>
                        </template>
                        <template x-if="inlineEditing">
                            <div @click.stop>
                                <input
                                    x-show="rowType === 'active'"
                                    type="number"
                                    min="0"
                                    max="100"
                                    x-model="dailyDev"
                                    @keydown.enter.prevent="saveInline()"
                                    class="w-20 rounded-lg border border-blue-400 bg-blue-50/60 px-2 py-1 text-center text-sm font-black text-blue-800 focus:outline-none"
                                >
                            </div>
                        </template>
                    </td>

                    {{-- نسبة انحراف الصيانة الوقائية --}}
                    <td
                        @if ($canEditRows)
                            @click="if (!inlineEditing) { inlineEditing = true; }"
                        @endif
                        class="whitespace-nowrap px-3 py-2.5 font-black {{ $isOnLeave ? 'text-black' : 'text-rose-700' }} tabular-nums {{ $canEditRows ? 'cursor-pointer' : '' }}"
                    >
                        <template x-if="!inlineEditing">
                            <span>
                                @if ($isOnLeave)
                                    {{ $item->status_note ?: 'مسؤول الصيانة مجاز' }}
                                @elseif ($isDayOff)
                                    {{ $item->status_note ?: '' }}
                                @elseif ($item->preventive_deviation_rate !== null)
                                    {{ (int) round((float) $item->preventive_deviation_rate) }}%
                                @endif
                            </span>
                        </template>
                        <template x-if="inlineEditing">
                            <div class="inline-flex items-center justify-center gap-1.5" @click.stop>
                                <input
                                    x-show="rowType === 'active'"
                                    type="number"
                                    min="0"
                                    max="100"
                                    x-model="preventiveDev"
                                    @keydown.enter.prevent="saveInline()"
                                    class="w-20 rounded-lg border border-rose-400 bg-rose-50/60 px-2 py-1 text-center text-sm font-black text-rose-700 focus:outline-none"
                                >
                                <input
                                    x-show="rowType !== 'active'"
                                    type="text"
                                    placeholder="مسؤول الصيانة مجاز"
                                    x-model="statusNote"
                                    @keydown.enter.prevent="saveInline()"
                                    class="w-44 rounded-lg border border-slate-400 bg-white px-2.5 py-1 text-center text-xs font-bold text-black focus:outline-none"
                                >
                                <button
                                    type="button"
                                    @click="saveInline()"
                                    :disabled="savingInline"
                                    class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-black text-white hover:bg-emerald-700 transition cursor-pointer"
                                >
                                    حفظ
                                </button>
                                <button
                                    type="button"
                                    @click="inlineEditing = false"
                                    class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </template>
                    </td>
                    <x-module.extra-cells :headers="$extraHeaders" :record="$item" />
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + count($extraHeaders) }}" class="px-6 py-12 text-center">
                        <p class="text-sm font-bold text-slate-800">لا توجد سجلات مطابقة</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (($summary['active_days'] ?? 0) > 0)
            <tfoot>
                <tr class="border-t-2 border-black bg-sky-200/90 text-lg sm:text-xl font-black text-black">
                    <td class="whitespace-nowrap border-e-2 border-black px-3 py-3.5">الإجمالي</td>
                    <td class="whitespace-nowrap border-e-2 border-black px-4 py-3.5 tabular-nums">{{ $summary['daily_maintenance_rate'] }}%</td>
                    <td class="whitespace-nowrap border-e-2 border-black px-4 py-3.5 tabular-nums">{{ $summary['preventive_maintenance_rate'] }}%</td>
                    <td class="whitespace-nowrap border-e-2 border-black px-4 py-3.5 text-blue-800 tabular-nums">{{ $summary['daily_deviation_rate'] }}%</td>
                    <td class="whitespace-nowrap px-4 py-3.5 text-rose-700 tabular-nums">{{ $summary['preventive_deviation_rate'] }}%</td>
                    @foreach ($extraHeaders as $_)
                        <td class="border-s-2 border-black px-3 py-3.5">—</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<x-module.pagination :paginator="$records" />
@endif
