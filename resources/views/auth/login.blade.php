<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>تسجيل الدخول — {{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-100 antialiased">
    <div class="relative flex min-h-screen items-center justify-center px-4 py-12">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 inset-x-0 mx-auto h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
            <div class="absolute bottom-0 end-10 h-80 w-80 rounded-full bg-sky-500/10 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-4xl">
            {{-- Header Title --}}
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-xl shadow-emerald-950/60">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white">نظام متابعة انحرافات إدارة المعدات</h1>
                <p class="mt-2 text-sm text-slate-400">بوابة المكتب التنفيذي — الرقابة التشغيلية ومؤشرات الأداء</p>
            </div>

            {{-- Main Container Card --}}
            <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/90 shadow-2xl shadow-black/50 backdrop-blur lg:grid lg:grid-cols-12">
                {{-- System Illustrative Showcase Panel --}}
                <div class="relative hidden lg:flex lg:col-span-6 flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-8 border-e border-slate-800/80">
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3.5 py-1 text-xs font-bold text-emerald-400 ring-1 ring-inset ring-emerald-500/20 mb-4">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>الرقابة والتشغيل الذكي</span>
                        </div>
                        <h2 class="text-2xl font-black text-white leading-tight">
                            متابعة شاملة لأسطول ومعدات المشاريع
                        </h2>
                        <p class="mt-2.5 text-xs text-slate-300 leading-relaxed">
                            مؤشرات لحظية للأعطال، الصيانات الوقائية، جاهزية السائقين، ومستهدفات التشغيل والورش الميدانية.
                        </p>
                    </div>

                    {{-- Image illustration --}}
                    <div class="relative my-5 overflow-hidden rounded-2xl border border-slate-800/90 bg-slate-950 shadow-2xl shadow-black/60 group">
                        <img
                            src="{{ asset('images/fleet-system-hero.jpg') }}"
                            alt="نظام متابعة انحرافات إدارة المعدات"
                            class="h-64 w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                            loading="eager"
                        >
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
                    </div>

                    <div class="relative z-10 flex items-center justify-between text-xs text-slate-400 border-t border-slate-800/80 pt-4">
                        <span class="font-semibold text-slate-300">إدارة المعدات — المكتب التنفيذي</span>
                        <span class="text-emerald-400 font-bold">2026/2027</span>
                    </div>
                </div>

                {{-- Login Form Panel --}}
                <div class="lg:col-span-6 p-7 sm:p-9 flex flex-col justify-center">
                    {{-- Mobile/Tablet Image Header --}}
                    <div class="block lg:hidden mb-6 overflow-hidden rounded-2xl border border-slate-800 shadow-lg">
                        <img
                            src="{{ asset('images/fleet-system-hero.jpg') }}"
                            alt="نظام متابعة انحرافات إدارة المعدات"
                            class="h-48 w-full object-cover object-center"
                        >
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                            <p class="font-semibold">{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-200 mb-2">اسم المستخدم أو البريد الإلكتروني</label>
                            <input
                                id="email"
                                name="email"
                                type="text"
                                value="{{ old('email', 'هيثم') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="هيثم"
                                class="block w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-200 mb-2">كلمة المرور</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="block w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 focus:outline-none"
                            >
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2.5 text-sm text-slate-300 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    @checked(old('remember'))
                                    class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/40"
                                >
                                <span>تذكر الجلسة على هذا الجهاز</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-950/50 hover:bg-emerald-500 focus:ring-2 focus:ring-emerald-400 focus:outline-none transition cursor-pointer"
                        >
                            تسجيل الدخول إلى لوحة المؤشرات
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
