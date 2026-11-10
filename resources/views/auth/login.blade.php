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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { navy: { 900: '#0F172A', 800: '#1E293B' } }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans antialiased text-slate-900">
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="flex justify-center">
            <div class="w-12 h-12 rounded-xl bg-navy-900 flex items-center justify-center text-white font-bold text-lg shadow-sm border border-slate-700">
                BK
            </div>
        </div>
        <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-slate-900">
            BK PACKAGES ERP
        </h2>
        <p class="mt-1 text-center text-sm text-slate-500">
            Okara Corrugated Carton Factory · Operations & Accounts
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-xs border border-slate-200 rounded-2xl sm:px-10">
            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                           placeholder="sohail@bkpackages.com"
                           class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:border-blue-600 focus:ring-blue-600" />
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           placeholder="••••••••"
                           class="w-full rounded-xl border-slate-300 text-sm py-2.5 focus:border-blue-600 focus:ring-blue-600" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remember" value="1" checked class="rounded border-slate-300 text-blue-600" />
                        Keep me signed in on this device
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-navy-900 text-white text-sm font-semibold hover:bg-navy-800 transition-colors shadow-sm">
                    Sign in to Factory ERP
                </button>
            </form>

            <!-- Quick Login Helpers for UAT / Demo Testing -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2.5 text-center">Staff Quick-Fill (Testing)</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="fillLogin('sohail@bkpackages.com', 'password')"
                            class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-left">
                        <div class="text-xs font-semibold text-slate-800">Mirza Sohail</div>
                        <div class="text-[10px] text-slate-500">Accountant (Full)</div>
                    </button>
                    <button type="button" onclick="fillLogin('gate@bkpackages.com', 'password')"
                            class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-left">
                        <div class="text-xs font-semibold text-slate-800">Subedar Nazir</div>
                        <div class="text-[10px] text-slate-500">Gate Security Only</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>

