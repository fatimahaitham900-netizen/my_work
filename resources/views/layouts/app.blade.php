<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'لوحة المؤشرات الرئيسية') — {{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-x-hidden bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    @php
        $user = auth()->user();
        $alerts = $navAlerts ?? [
            'total_active_deviations' => 0,
            'total_critical_alerts' => 0,
            'module_counts' => [],
        ];

        $isDashboardActive = request()->routeIs('dashboard');

        $reportItems = [
            [
                'key' => 'ownership_transfers',
                'label' => 'نقل ملكيات المعدات',
                'route' => 'ownership-transfers.index',
                'active' => request()->routeIs('ownership-transfers.*'),
                'icon' => 'transfer',
            ],
            [
                'key' => 'breakdown_driver_stoppages',
                'label' => 'سائقين متوقفين على أعطال',
                'route' => 'breakdown-stoppages.index',
                'active' => request()->routeIs('breakdown-stoppages.*'),
                'icon' => 'stoppage',
            ],
            [
                'key' => 'unassigned_drivers',
                'label' => 'سائقين بدون معدات',
                'route' => 'unassigned-drivers.index',
                'active' => request()->routeIs('unassigned-drivers.*'),
                'icon' => 'unassigned',
            ],
            [
                'key' => 'equipment_breakdowns',
                'label' => 'معدات متعطلة بالورش',
                'route' => 'equipment-breakdowns.index',
                'active' => request()->routeIs('equipment-breakdowns.*'),
                'icon' => 'breakdown',
            ],
            [
                'key' => 'regulatory_documents',
                'label' => 'الأوراق النظامية',
                'route' => 'regulatory-documents.index',
                'active' => request()->routeIs('regulatory-documents.*'),
                'icon' => 'document',
            ],
            [
                'key' => 'transport_violations',
                'label' => 'مخالفات وزارة النقل',
                'route' => 'transport-violations.index',
                'active' => request()->routeIs('transport-violations.*'),
                'icon' => 'violation',
            ],
            [
                'key' => 'spare_part_transfers',
                'label' => 'نقل قطع غيار بدون تعميد الإدارة',
                'route' => 'spare-part-transfers.index',
                'active' => request()->routeIs('spare-part-transfers.*'),
                'icon' => 'spare-parts',
            ],
            [
                'key' => 'car_breakdowns',
                'label' => 'السيارات المتعطلة بالورش',
                'route' => 'car-breakdowns.index',
                'active' => request()->routeIs('car-breakdowns.*'),
                'icon' => 'car-breakdown',
            ],
            [
                'key' => 'idle_project_equipment',
                'label' => 'معدات بدون عمل فعلي بالمشاريع',
                'route' => 'idle-project-equipment.index',
                'active' => request()->routeIs('idle-project-equipment.*'),
                'icon' => 'idle-equipment',
            ],
            [
                'key' => 'financial_support',
                'label' => 'الدعم المالي',
                'route' => 'financial-support.index',
                'active' => request()->routeIs('financial-support.*'),
                'icon' => 'financial-support',
            ],
            [
                'key' => 'maintenance_deviations',
                'label' => 'انحراف الصيانات',
                'route' => 'maintenance-deviations.index',
                'active' => request()->routeIs('maintenance-deviations.*'),
                'icon' => 'maintenance-deviation',
            ],
        ];

        $isAnyReportActive = collect($reportItems)->contains('active', true);
    @endphp

    {{-- Mobile Backdrop --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Executive RTL Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 start-0 z-50 flex w-64 flex-col bg-slate-950 text-slate-100 border-e border-slate-800/80 transition-transform duration-200 ease-out"
        aria-label="القائمة الرئيسية"
    >
        {{-- Brand Header --}}
        <div class="flex h-20 items-center justify-between px-5 border-b border-slate-800/80">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-950/50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-white leading-tight">متابعة انحرافات المعدات</p>
                    <p class="truncate text-[11px] text-slate-400 mt-0.5">مؤشرات المكتب التنفيذي</p>
                </div>
            </a>

            <button
                type="button"
                @click="sidebarOpen = false"
                class="lg:hidden rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
                aria-label="إغلاق القائمة"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Navigation Links --}}
        <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-2">
            <p class="px-3 pb-1 text-[11px] font-semibold tracking-wide text-slate-500">القائمة الرئيسية</p>

            {{-- Primary Dashboard Link --}}
            <a
                href="{{ route('dashboard') }}"
                class="group flex items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 text-sm font-bold transition-all {{ $isDashboardActive ? 'bg-slate-800/90 text-white shadow-xs ring-1 ring-white/10' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <span class="flex items-center gap-3 min-w-0">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $isDashboardActive ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-900 text-slate-400 group-hover:text-slate-200' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                    </span>
                    <span class="truncate">لوحة المؤشرات الرئيسية</span>
                </span>
            </a>

            {{-- Collapsible "التقارير" Parent Menu Containing All Report Pages --}}
            <div x-data="{ reportsOpen: @js($isAnyReportActive) }" class="space-y-1.5">
                <button
                    type="button"
                    @click="reportsOpen = !reportsOpen"
                    :aria-expanded="reportsOpen.toString()"
                    class="group flex w-full items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 text-sm font-bold transition-all cursor-pointer {{ $isAnyReportActive ? 'bg-slate-800/90 text-white shadow-xs ring-1 ring-white/10' : 'text-slate-200 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span class="flex items-center gap-3 min-w-0">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $isAnyReportActive ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-900 text-slate-400 group-hover:text-slate-200' }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                            </svg>
                        </span>
                        <span class="truncate">التقارير</span>
                    </span>

                    <span class="flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex items-center justify-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-bold text-emerald-300 ring-1 ring-emerald-500/25 tabular-nums">
                            {{ count($reportItems) }}
                        </span>
                        <svg
                            :class="reportsOpen ? 'rotate-180 text-emerald-400' : 'text-slate-400'"
                            class="h-4 w-4 transition-transform duration-200"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </span>
                </button>

                <div
                    x-show="reportsOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="space-y-1 border-s-2 border-slate-800 ms-4 ps-2.5 py-1"
                >
                    @foreach ($reportItems as $item)
                        @php
                            $moduleStat = $alerts['module_counts'][$item['key']] ?? null;
                            $badgeCount = $moduleStat ? $moduleStat['active'] : null;
                            $hasCritical = $moduleStat && $moduleStat['critical'] > 0;
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            class="group flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-xs font-semibold transition-all {{ $item['active'] ? 'bg-slate-800/95 text-white shadow-xs ring-1 ring-white/10' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                        >
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-900 text-slate-400 group-hover:text-slate-200' }}">
                                    @switch($item['icon'])
                                        @case('transfer')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                            </svg>
                                            @break
                                        @case('stoppage')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            @break
                                        @case('unassigned')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                            </svg>
                                            @break
                                        @case('breakdown')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                                            </svg>
                                            @break
                                        @case('document')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            @break
                                        @case('violation')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                                            </svg>
                                            @break
                                        @case('spare-parts')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                            </svg>
                                            @break
                                        @case('car-breakdown')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                            </svg>
                                            @break
                                        @case('idle-equipment')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            @break
                                        @case('financial-support')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                            </svg>
                                            @break
                                        @case('maintenance-deviation')
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                            </svg>
                                            @break
                                    @endswitch
                                </span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </span>

                            @if ($badgeCount !== null)
                                <span class="inline-flex shrink-0 items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $hasCritical ? 'bg-rose-500/20 text-rose-300 ring-1 ring-rose-500/30' : 'bg-slate-800 text-slate-300' }}">
                                    {{ $badgeCount }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        {{-- Sidebar Footer: User Profile & Logout --}}
        <div class="p-3.5 border-t border-slate-800/80 space-y-2.5">
            @if ($user)
                <div class="flex items-center gap-2.5 rounded-xl bg-slate-900/90 px-3 py-2.5 ring-1 ring-slate-800">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20 text-xs font-extrabold text-emerald-300 ring-1 ring-emerald-500/30">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-white leading-tight">{{ $user->name }}</p>
                        <p class="truncate text-[11px] font-medium text-slate-400 mt-0.5">{{ $user->role->label() }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2.5 text-xs font-bold text-rose-300 hover:bg-rose-500/20 hover:text-rose-200 transition cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            @endif
        </div>
    </aside>

    {{-- Main Shell --}}
    <div class="flex min-h-screen min-w-0 flex-col lg:ps-64">
        {{-- Sticky Top Header (Matching Sidebar Dark Slate-950) --}}
        <header class="sticky top-0 z-30 grid h-20 grid-cols-3 items-center gap-3 border-b border-slate-800/80 bg-slate-950 px-4 sm:px-6 lg:px-8 text-white shadow-sm">
            {{-- Start (Right in RTL): Mobile menu toggle + Page Title on sub-pages --}}
            <div class="flex items-center gap-3 min-w-0 justify-self-start">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="lg:hidden inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-700 text-slate-200 hover:bg-slate-800"
                    aria-label="فتح القائمة"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                @if (! request()->routeIs('dashboard'))
                    <div class="min-w-0">
                        <h1 class="truncate text-base sm:text-lg font-extrabold text-white">@yield('page_title')</h1>
                        <p class="hidden md:block truncate text-xs text-slate-400">@yield('page_subtitle')</p>
                    </div>
                @endif
            </div>

            {{-- Center: المكتب التنفيذي --}}
            <div class="justify-self-center text-center min-w-0">
                <h1 class="truncate text-2xl sm:text-3xl font-black tracking-tight text-white">المكتب التنفيذي</h1>
            </div>

            {{-- End (Far Left in RTL): هيثم نبيل --}}
            <div class="justify-self-end shrink-0">
                <span class="inline-flex items-center rounded-xl border border-slate-700/80 bg-slate-900/90 px-4 py-1.5 text-lg sm:text-2xl font-black text-white shadow-xs">
                    هيثم نبيل
                </span>
            </div>
        </header>

        {{-- Page Body --}}
        <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Global Instant Toast Notifications --}}
    <div
        x-data
        class="pointer-events-none fixed bottom-5 end-5 z-50 flex w-full max-w-sm flex-col gap-2.5 px-4 sm:px-0"
        aria-live="polite"
    >
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                :class="toast.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-slate-900 text-white'"
                class="pointer-events-auto flex items-center justify-between gap-3 rounded-2xl border px-4 py-3.5 text-sm font-medium shadow-xl shadow-slate-900/15"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span
                        :class="toast.type === 'error' ? 'bg-rose-500' : 'bg-emerald-400'"
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                    ></span>
                    <span class="truncate" x-text="toast.message"></span>
                </div>
                <button
                    type="button"
                    @click="$store.toast.remove(toast.id)"
                    class="shrink-0 rounded-lg p-1 opacity-70 hover:opacity-100 cursor-pointer"
                    aria-label="إغلاق التنبيه"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
</body>
</html>

