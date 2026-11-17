<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#0F172A" />
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg" />
    <title>Set Up 4-Digit PIN — BK Packages ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: { navy: { 900: '#0F172A', 800: '#1E293B', 700: '#334155' } }
                }
            }
        }
    </script>
</head>

<body
    class="bg-slate-50 min-h-screen flex flex-col justify-center py-10 sm:px-6 lg:px-8 font-sans antialiased text-slate-900">

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="flex justify-center">
            <div
                class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black text-xl shadow-md">
                <span class="material-symbols-outlined text-[28px]">pin</span>
            </div>
        </div>
        <h2 class="mt-3 text-center text-2xl font-extrabold tracking-tight text-slate-900">
            Set Your 4-Digit PIN
        </h2>
        <p class="mt-1 text-center text-xs text-slate-500 font-medium">
            Quick & Secure Device Access
        </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-7 px-6 shadow-sm border border-slate-200 rounded-3xl sm:px-8">

            <!-- Profile Info Banner -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3 mb-5">
                <div
                    class="w-10 h-10 rounded-full bg-navy-900 text-white font-bold text-sm flex items-center justify-center shrink-0">
                    {{ $user->initials() }}
                </div>
                <div>
                    <div class="font-bold text-slate-900 text-xs">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-500 font-mono">{{ $user->email }}</div>
                </div>
            </div>

            <p class="text-xs text-slate-600 mb-5 leading-relaxed">
                Choose a <strong>4-digit security PIN</strong>.
            </p>

            @if($errors->any())
                <div
                    class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('pin.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Enter 4-Digit PIN <span
                            class="text-red-500">*</span></label>
                    <input type="password" name="pin" id="pin" maxlength="4" pattern="[0-9]{4}" inputmode="numeric"
                        required placeholder="••••"
                        class="w-full text-center tracking-[1em] text-2xl font-mono font-bold rounded-xl border-slate-300 py-3 focus:border-blue-600 focus:ring-blue-600" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm 4-Digit PIN <span
                            class="text-red-500">*</span></label>
                    <input type="password" name="pin_confirmation" id="pin_confirmation" maxlength="4"
                        pattern="[0-9]{4}" inputmode="numeric" required placeholder="••••"
                        class="w-full text-center tracking-[1em] text-2xl font-mono font-bold rounded-xl border-slate-300 py-3 focus:border-blue-600 focus:ring-blue-600" />
                </div>

                <div
                    class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-100 text-[11px] text-emerald-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-emerald-600">security</span>
                    <span>This PIN will be tied to your email profile across all your devices.</span>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-navy-900 text-white text-xs font-bold hover:bg-navy-800 transition-colors shadow-sm mt-3">
                    Activate PIN & Enter ERP
                </button>
            </form>
        </div>
    </div>
</body>

</html>