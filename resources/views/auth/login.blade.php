<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#0F172A" />
    <link rel="manifest" href="/manifest.json" />
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg" />
    <title>Sign In — BK Packages ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
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
    <style>
        .pin-dot {
            width: 16px;
            height: 16px;
            border-radius: 9999px;
            border: 2px solid #cbd5e1;
            transition: all 0.15s ease-in-out;
        }
        .pin-dot.filled {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: scale(1.15);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.4);
        }
        .key-btn {
            touch-action: manipulation;
            transition: all 0.1s ease;
        }
        .key-btn:active {
            transform: scale(0.92);
            background-color: #e2e8f0;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center py-10 sm:px-6 lg:px-8 font-sans antialiased text-slate-900">

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <!-- Logo & Branding -->
        <div class="flex justify-center">
            <div class="w-12 h-12 rounded-2xl bg-navy-900 flex items-center justify-center text-white font-black text-xl shadow-md border border-slate-700">
                BK
            </div>
        </div>
        <h2 class="mt-3 text-center text-2xl font-extrabold tracking-tight text-slate-900">
            BK PACKAGES ERP
        </h2>
        <p class="mt-0.5 text-center text-xs text-slate-500 font-medium">
            Okara Carton Factory · Operations & Management
        </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-7 px-6 shadow-sm border border-slate-200 rounded-3xl sm:px-8">

            <!-- Flash Error Messages -->
            @if($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-5 p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($savedUser && !$forcePassword)
                <!-- ========================================== -->
                <!-- MODE A: QUICK 4-DIGIT PIN CODE UNLOCK SCREEN -->
                <!-- ========================================== -->
                <div class="text-center">
                    <!-- Remembered Profile Card -->
                    <div class="relative inline-block mb-3">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-navy-900 to-blue-600 text-white font-bold text-xl flex items-center justify-center mx-auto shadow-md border-2 border-white">
                            {{ $savedUser->initials() }}
                        </div>
                        <span class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></span>
                    </div>

                    <h3 class="text-base font-bold text-slate-900">{{ $savedUser->name }}</h3>
                    <p class="text-xs text-slate-500 font-mono">{{ $savedUser->email }}</p>

                    <div class="mt-2 mb-6">
                        @if($savedUser->role === 'ADMIN')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                Mirza Sohail · Full Access
                            </span>
                        @elseif($savedUser->role === 'MANAGER')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                Manager · Progress & Expenses
                            </span>
                        @elseif($savedUser->role === 'OWNER')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Company Owner · Overview (View Only)
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $savedUser->role }}
                            </span>
                        @endif
                    </div>

                    <p class="text-xs font-semibold text-slate-700 mb-3">Enter your 4-Digit Security PIN</p>

                    <!-- PIN Indicator Dots -->
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <div id="dot-0" class="pin-dot"></div>
                        <div id="dot-1" class="pin-dot"></div>
                        <div id="dot-2" class="pin-dot"></div>
                        <div id="dot-3" class="pin-dot"></div>
                    </div>

                    <!-- Hidden PIN Form -->
                    <form id="pin-login-form" action="{{ route('login.pin') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ $savedUser->email }}" />
                        <input type="hidden" name="pin" id="pin-input" />
                    </form>

                    <!-- Tactile Numeric Keypad -->
                    <div class="grid grid-cols-3 gap-3 max-w-[260px] mx-auto mb-6">
                        @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9] as $num)
                            <button type="button" onclick="pressPin('{{ $num }}')" class="key-btn h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-lg flex items-center justify-center shadow-xs">
                                {{ $num }}
                            </button>
                        @endforeach
                        <button type="button" onclick="clearPin()" class="key-btn h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-500 font-semibold text-xs flex items-center justify-center">
                            CLEAR
                        </button>
                        <button type="button" onclick="pressPin('0')" class="key-btn h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-lg flex items-center justify-center shadow-xs">
                            0
                        </button>
                        <button type="button" onclick="backspacePin()" class="key-btn h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-500 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">backspace</span>
                        </button>
                    </div>

                    <!-- Switch Account / Use Password Options -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <a href="{{ route('login', ['mode' => 'password']) }}" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">key</span>
                            Use Password
                        </a>
                        <form action="{{ route('account.switch') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-slate-500 hover:text-slate-800 font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">swap_horiz</span>
                                Switch User
                            </button>
                        </form>
                    </div>
                </div>

            @else
                <!-- ========================================== -->
                <!-- MODE B: STANDARD EMAIL & PASSWORD LOGIN -->
                <!-- ========================================== -->
                <div>
                    <div class="mb-5 pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Sign in with Credentials</h3>
                        <!-- <p class="text-xs text-slate-500 mt-0.5">Log in once with your secure 20-digit password. You will then set a 4-digit PIN for instant access on this device.</p> -->
                    </div>

                    <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                                   placeholder="mz.sohail764245@gmail.com"
                                   class="w-full rounded-xl border-slate-300 text-xs py-2.5 focus:border-blue-600 focus:ring-blue-600 font-medium" />
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">20-Character Secure Password</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   placeholder="Enter 20-character password"
                                   class="w-full rounded-xl border-slate-300 text-xs py-2.5 focus:border-blue-600 focus:ring-blue-600 font-mono" />
                        </div>

                        <div class="p-2.5 rounded-lg bg-blue-50/70 border border-blue-100 text-[11px] text-blue-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-blue-600">verified_user</span>
                            <span>Continuous device persistence enabled (keeps you logged in until you log out).</span>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl bg-navy-900 text-white text-xs font-bold hover:bg-navy-800 transition-colors shadow-sm mt-2">
                            Sign in to BK Packages ERP
                        </button>
                    </form>

                    @if($savedUser)
                        <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                            <a href="{{ route('login') }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">lock</span>
                                Back to Quick PIN Unlock ({{ $savedUser->name }})
                            </a>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <script>
        // Interactive 4-Digit PIN Script
        let currentPin = '';

        function updateDots() {
            for (let i = 0; i < 4; i++) {
                const dot = document.getElementById('dot-' + i);
                if (dot) {
                    if (i < currentPin.length) {
                        dot.classList.add('filled');
                    } else {
                        dot.classList.remove('filled');
                    }
                }
            }
        }

        function pressPin(digit) {
            if (currentPin.length < 4) {
                currentPin += digit;
                updateDots();

                if (currentPin.length === 4) {
                    // Auto submit
                    document.getElementById('pin-input').value = currentPin;
                    setTimeout(() => {
                        document.getElementById('pin-login-form').submit();
                    }, 120);
                }
            }
        }

        function backspacePin() {
            if (currentPin.length > 0) {
                currentPin = currentPin.slice(0, -1);
                updateDots();
            }
        }

        function clearPin() {
            currentPin = '';
            updateDots();
        }

        // Support physical keyboard 0-9, Backspace, Escape
        document.addEventListener('keydown', (e) => {
            const form = document.getElementById('pin-login-form');
            if (!form) return;

            if (e.key >= '0' && e.key <= '9') {
                pressPin(e.key);
            } else if (e.key === 'Backspace') {
                backspacePin();
            } else if (e.key === 'Escape') {
                clearPin();
            }
        });

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
