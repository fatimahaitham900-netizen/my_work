@extends('layouts.app')

@section('title', 'المكتب التنفيذي')
@section('page_subtitle', 'نظرة شاملة ومحدثة على انحرافات المعدات، السائقين، والأوراق النظامية')

@section('content')
    @php
        $executiveCards = $summary['executive_cards'] ?? [];
        $financialSupport = $summary['financial_support_summary'] ?? null;

        $spanClasses = [
            'lg:col-span-2' => 'lg:col-span-2',
            'lg:col-span-3' => 'lg:col-span-3',
            'lg:col-span-4' => 'lg:col-span-4',
            'sm:col-span-2 lg:col-span-4' => 'sm:col-span-2 lg:col-span-4',
            'sm:col-span-2 lg:col-span-6' => 'sm:col-span-2 lg:col-span-6',
            'sm:col-span-2 lg:col-span-8' => 'sm:col-span-2 lg:col-span-8',
            'sm:col-span-2 lg:col-span-12' => 'sm:col-span-2 lg:col-span-12',
        ];

        $colorClasses = [
            'text-slate-950' => 'text-slate-950',
            'text-blue-600' => 'text-blue-600',
            'text-emerald-600' => 'text-emerald-600',
            'text-rose-600' => 'text-rose-600',
            'text-purple-700' => 'text-purple-700',
            'text-amber-600' => 'text-amber-600',
        ];
    @endphp

    <div class="space-y-6">
        {{-- Executive Centered Title & Automatic Date Banner (Optimized for Large Display Screens) --}}
        <section
            class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 sm:py-9 text-white shadow-xl shadow-slate-900/10"
            x-data="{
                formattedDate: @js(now()->locale('ar')->translatedFormat('l').' — '.now()->format('d/m/Y')),
                updateDate() {
                    const now = new Date();
                    const weekday = new Intl.DateTimeFormat('ar-SA', { weekday: 'long' }).format(now);
                    const dd = String(now.getDate()).padStart(2, '0');
                    const mm = String(now.getMonth() + 1).padStart(2, '0');
                    const yyyy = now.getFullYear();
                    this.formattedDate = `${weekday} — ${dd}/${mm}/${yyyy}`;
                },
                init() {
                    this.updateDate();
                    setInterval(() => this.updateDate(), 60000);
                }
            }"
        >
            <div class="pointer-events-none absolute -top-24 end-10 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 start-16 h-64 w-64 rounded-full bg-rose-500/10 blur-3xl"></div>

            <div class="relative flex flex-col items-center justify-center text-center gap-3.5">
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-white">
                    مؤشرات إدارة المعدات
                </h2>

                <div class="inline-flex items-center gap-2.5 rounded-full bg-white/10 px-6 py-2 text-base sm:text-xl font-black text-emerald-300 ring-1 ring-inset ring-white/20 tabular-nums">
                    <svg class="h-6 w-6 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span x-text="formattedDate">{{ now()->locale('ar')->translatedFormat('l').' — '.now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </section>

        {{-- Executive Ruled Summary Boxes (4 Columns Per Row — High-Contrast TV Display) --}}
        <section aria-label="ملخص إجمالي الأعداد لجميع المحاور">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-12">
                @foreach ($executiveCards as $card)
                    <div class="{{ $spanClasses[$card['span'] ?? 'lg:col-span-3'] ?? 'lg:col-span-3' }} group flex flex-col overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-lg">
                        {{-- Dark Navy Header Bar --}}
                        <a
                            href="{{ route($card['route'], $card['route_params'] ?? []) }}"
                            class="flex min-h-[68px] items-center justify-center bg-slate-900 px-3.5 py-3.5 text-center text-xl sm:text-2xl font-black leading-snug tracking-tight text-white group-hover:bg-black transition-colors"
                        >
                            <span>{{ $card['title'] }}</span>
                        </a>

                        {{-- Ruled Metrics Table --}}
                        <table class="w-full flex-1 table-fixed border-collapse">
                            <thead>
                                <tr class="border-b-2 border-black bg-slate-100/80">
                                    @foreach ($card['cells'] as $cell)
                                        <th scope="col" class="border-e-2 border-black last:border-e-0 px-3 py-3 text-center text-lg sm:text-xl font-black text-black">
                                            {{ $cell['label'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    @foreach ($card['cells'] as $cell)
                                        <td class="border-e-2 border-black last:border-e-0 p-0 text-center {{ ! empty($cell['tinted']) ? 'bg-sky-50/60' : 'bg-white' }}">
                                            @if (! empty($card['editable']))
                                                <div
                                                    x-data="{
                                                        editing: false,
                                                        saving: false,
                                                        rawValue: @js($card['raw_value'] ?? $cell['value']),
                                                        displayValue: @js($cell['value']),
                                                        canEdit: @js((bool) auth()->user()?->canManageDeviations()),
                                                        async saveMetric() {
                                                            if (this.saving) return;
                                                            this.saving = true;
                                                            try {
                                                                const response = await fetch(@js(route('dashboard.metrics.update', $card['metric_key'])), {
                                                                    method: 'PATCH',
                                                                    headers: {
                                                                        'Content-Type': 'application/json',
                                                                        'Accept': 'application/json',
                                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                                    },
                                                                    body: JSON.stringify({ value: String(this.rawValue ?? '') }),
                                                                });
                                                                if (response.ok) {
                                                                    const data = await response.json();
                                                                    this.rawValue = data.raw_value;
                                                                    this.displayValue = data.display_value;
                                                                    this.editing = false;
                                                                    if (window.Alpine && Alpine.store('toast')) {
                                                                        Alpine.store('toast').push(data.message || 'تم الحفظ بنجاح');
                                                                    }
                                                                }
                                                            } finally {
                                                                this.saving = false;
                                                            }
                                                        }
                                                    }"
                                                    class="relative flex h-full min-h-[96px] w-full items-center justify-center px-3 py-4"
                                                >
                                                    <div x-show="!editing" class="flex items-center justify-center gap-2 w-full">
                                                        <button
                                                            type="button"
                                                            @click="if (canEdit) { editing = true; $nextTick(() => $refs.metricInput.focus()); }"
                                                            :class="canEdit ? 'cursor-pointer hover:opacity-80' : 'cursor-default'"
                                                            class="text-4xl sm:text-5xl font-black tracking-tight tabular-nums {{ $colorClasses[$cell['color']] ?? 'text-black' }}"
                                                            title="اضغط لتعديل القيمة مباشرة"
                                                        >
                                                            <span x-text="displayValue">{{ $cell['value'] }}</span>
                                                        </button>

                                                        @if (auth()->user()?->canManageDeviations())
                                                            <button
                                                                type="button"
                                                                @click="editing = true; $nextTick(() => $refs.metricInput.focus())"
                                                                class="absolute top-2 end-2 inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-600 hover:border-slate-900 hover:bg-slate-900 hover:text-white transition cursor-pointer"
                                                                title="تعديل الرقم من المربع"
                                                            >
                                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                                </svg>
                                                                <span>تعديل</span>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <form
                                                        x-show="editing"
                                                        x-cloak
                                                        @submit.prevent="saveMetric()"
                                                        class="flex w-full max-w-xs items-center justify-center gap-2"
                                                    >
                                                        <input
                                                            x-ref="metricInput"
                                                            type="text"
                                                            x-model="rawValue"
                                                            @keydown.escape.prevent="editing = false"
                                                            class="w-full rounded-xl border-2 border-slate-900 bg-white px-3 py-2 text-center text-xl font-black text-slate-950 focus:outline-none"
                                                        >
                                                        <button
                                                            type="submit"
                                                            :disabled="saving"
                                                            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700 transition cursor-pointer shrink-0"
                                                        >
                                                            حفظ
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="editing = false"
                                                            class="rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer shrink-0"
                                                        >
                                                            إلغاء
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <a
                                                    href="{{ route($card['route'], $cell['params'] ?? []) }}"
                                                    class="flex h-full min-h-[96px] w-full items-center justify-center px-3 py-4 {{ ! empty($card['wide']) ? 'text-3xl sm:text-4xl xl:text-[42px]' : 'text-4xl sm:text-5xl' }} font-black tracking-tight tabular-nums {{ $colorClasses[$cell['color']] ?? 'text-black' }} hover:bg-slate-50/90 transition-colors"
                                                >
                                                    {{ $cell['value'] }}
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Executive Financial Support Summary Table (موقف الدعم المالي للمعدات 2026) --}}
        @if ($financialSupport && count($financialSupport['rows'] ?? []) > 0)
            <section aria-label="موقف الدعم المالي للمعدات 2026">
                <div class="overflow-hidden rounded-2xl border-2 border-black bg-white shadow-sm">
                    <a
                        href="{{ route('financial-support.index') }}"
                        class="block bg-slate-900 px-6 py-4 text-center text-2xl sm:text-3xl font-black tracking-tight text-white hover:bg-black transition-colors"
                    >
                        {{ $financialSupport['title'] }}
                    </a>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-center">
                            <thead>
                                <tr class="border-b-2 border-black bg-sky-100/70">
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">الشهر</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">دعم إصلاحات معدات</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">عهد</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">الإجمالي</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-amber-800">لم يتم التحويل</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">إجمالي المعدات</th>
                                    <th scope="col" class="border-e-2 border-black px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-black">تم الإصلاح</th>
                                    <th scope="col" class="px-2.5 py-3.5 text-base sm:text-lg xl:text-xl font-black text-rose-600">متبقي تحت الإصلاح</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($financialSupport['rows'] as $row)
                                    <tr class="border-b-2 border-black even:bg-slate-50/70 hover:bg-sky-50/60 transition-colors">
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3.5">
                                            <a
                                                href="{{ route('financial-support.index', ['month' => $row['month']]) }}"
                                                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3.5 py-1.5 text-base sm:text-xl font-black text-white hover:bg-emerald-700 transition-colors"
                                            >
                                                {{ $row['title'] }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-blue-600 tabular-nums">
                                            {{ number_format((float) $row['repair_support_amount'], 0) }}
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-slate-950 tabular-nums">
                                            {{ number_format((float) $row['custody_amount'], 0) }}
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black bg-sky-50/60 px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-emerald-600 tabular-nums">
                                            {{ number_format((float) $row['total_amount'], 0) }}
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-amber-600 tabular-nums">
                                            @if ($row['not_transferred_amount'] !== null && (float) $row['not_transferred_amount'] > 0)
                                                <a
                                                    href="{{ route('financial-support.index', ['month' => $row['month'], 'transfer_stage' => 'لم يتم التحويل']) }}"
                                                    title="عرض البنود التي لم يتم تحويلها في {{ $row['title'] }}"
                                                    class="inline-flex items-center justify-center rounded-xl bg-amber-50 px-3 py-1 text-lg sm:text-xl xl:text-2xl font-black text-amber-700 ring-2 ring-inset ring-amber-600/35 hover:bg-amber-600 hover:text-white transition-all tabular-nums shadow-2xs"
                                                >
                                                    {{ number_format((float) $row['not_transferred_amount'], 0) }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-slate-950 tabular-nums">
                                            <a
                                                href="{{ route('financial-support.index', ['month' => $row['month']]) }}"
                                                class="hover:underline"
                                            >
                                                {{ number_format((int) $row['total_equipment_count']) }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-emerald-600 tabular-nums">
                                            <a
                                                href="{{ route('financial-support.index', ['month' => $row['month'], 'repair_status' => 'تم الاصلاح']) }}"
                                                class="hover:underline"
                                                title="عرض المعدات التي تم إصلاحها في {{ $row['title'] }}"
                                            >
                                                {{ number_format((int) $row['repaired_count']) }}
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-2.5 py-3">
                                            @if ((int) $row['under_repair_count'] > 0)
                                                <a
                                                    href="{{ route('financial-support.index', ['month' => $row['month'], 'repair_status' => 'لم يتم الاصلاح']) }}"
                                                    title="عرض المعدات المتبقية تحت الإصلاح في {{ $row['title'] }}"
                                                    class="inline-flex min-w-11 items-center justify-center rounded-xl bg-rose-50 px-3 py-1 text-lg sm:text-xl xl:text-2xl font-black text-rose-600 ring-2 ring-inset ring-rose-600/35 hover:bg-rose-600 hover:text-white transition-all tabular-nums shadow-2xs"
                                                >
                                                    {{ number_format((int) $row['under_repair_count']) }}
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-sky-100/90 border-t-2 border-black">
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl font-black text-black">
                                        الإجمالي
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-blue-700 tabular-nums">
                                        {{ number_format((float) $financialSupport['totals']['repair_support_amount'], 0) }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-black tabular-nums">
                                        {{ number_format((float) $financialSupport['totals']['custody_amount'], 0) }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-emerald-700 tabular-nums">
                                        {{ number_format((float) $financialSupport['totals']['total_amount'], 0) }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-amber-700 tabular-nums">
                                        @if ((float) $financialSupport['totals']['not_transferred_amount'] > 0)
                                            <a
                                                href="{{ route('financial-support.index', ['month' => 'all', 'transfer_stage' => 'لم يتم التحويل']) }}"
                                                title="عرض جميع البنود التي لم يتم تحويلها"
                                                class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-3 py-1 text-lg sm:text-xl xl:text-2xl font-black text-white hover:bg-amber-700 transition-colors tabular-nums"
                                            >
                                                {{ number_format((float) $financialSupport['totals']['not_transferred_amount'], 0) }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-black tabular-nums">
                                        {{ number_format((int) $financialSupport['totals']['total_equipment_count']) }}
                                    </td>
                                    <td class="whitespace-nowrap border-e-2 border-black px-2.5 py-3 text-lg sm:text-xl xl:text-2xl font-black text-emerald-700 tabular-nums">
                                        {{ number_format((int) $financialSupport['totals']['repaired_count']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-2.5 py-3">
                                        <a
                                            href="{{ route('financial-support.index', ['month' => 'all', 'repair_status' => 'لم يتم الاصلاح']) }}"
                                            title="عرض جميع المعدات المتبقية تحت الإصلاح"
                                            class="inline-flex min-w-11 items-center justify-center rounded-xl bg-rose-600 px-3 py-1 text-lg sm:text-xl xl:text-2xl font-black text-white hover:bg-rose-700 transition-colors tabular-nums"
                                        >
                                            {{ number_format((int) $financialSupport['totals']['under_repair_count']) }}
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
