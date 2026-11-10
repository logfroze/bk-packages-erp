<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="theme-color" content="#0F172A" />
    <link rel="manifest" href="/manifest.json" />
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg" />
    <title>@yield('title', 'BK Packages ERP') — Factory Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        navy: { 900: '#0F172A', 800: '#1E293B', 700: '#334155' },
                        canvas: '#F8FAFC',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .num-tabular { font-variant-numeric: tabular-nums; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
            font-size: 20px;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }
        @media (min-width: 1024px) {
            html.ai-dock-open #workspace-shell {
                margin-right: 360px;
            }
            html.ai-dock-open #ai-toggle-btn {
                background-color: #0F172A;
            }
            html.ai-dock-open #ai-header-date {
                display: none;
            }
        }
        @media (min-width: 1366px) {
            html.ai-dock-open #workspace-shell {
                margin-right: 400px;
            }
        }
        @media (min-width: 1024px) and (max-width: 1500px) {
            html.ai-dock-open #workspace-shell .lg\:grid-cols-4,
            html.ai-dock-open #workspace-shell .xl\:grid-cols-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            html.ai-dock-open #workspace-shell .lg\:grid-cols-3,
            html.ai-dock-open #workspace-shell .xl\:grid-cols-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            html.ai-dock-open #workspace-shell .lg\:grid-cols-2,
            html.ai-dock-open #workspace-shell .xl\:grid-cols-2 {
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            }
            html.ai-dock-open #workspace-shell .xl\:flex-row {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
        }
        html.ai-dock-open #ai-drawer {
            transform: translateX(0);
        }
        @media (max-width: 1023px) {
            html.ai-dock-open #ai-backdrop {
                display: block;
            }
        }
        @media print {
            .no-print { display: none !important; }
            main { margin-left: 0 !important; padding: 0 !important; }
            #workspace-shell { margin-left: 0 !important; margin-right: 0 !important; }
            body { background: #ffffff !important; }
        }
    </style>
    <script>
        try {
            if (localStorage.getItem('bk_ai_drawer_open') === '1') {
                document.documentElement.classList.add('ai-dock-open');
            }
        } catch (e) {}
    </script>
</head>
<body class="bg-canvas text-slate-900 antialiased min-h-screen">

    <!-- Mobile Backdrop -->
    <div id="mobile-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden no-print"></div>

    <!-- Fixed Dark Navy Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-navy-900 text-slate-300 z-50 flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200 no-print">
        <div>
            <!-- Brand Header -->
            <div class="px-5 py-5 border-b border-slate-800 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        BK
                    </div>
                    <div>
                        <div class="text-white font-semibold text-sm tracking-tight flex items-center gap-2">
                            BK PACKAGES
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        </div>
                        <div class="text-xs text-slate-400">Okara Plant ERP</div>
                    </div>
                </a>
                <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="px-3 py-5 space-y-1">
                <p class="px-3 pb-2 text-[11px] uppercase font-semibold tracking-wider text-slate-500">Operations</p>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('dashboard') ? 'text-blue-400' : '' }}">dashboard</span>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()?->isFinancialAuthorized())
                    <a href="{{ route('production.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('production.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                        <span class="material-symbols-outlined {{ request()->routeIs('production.*') ? 'text-blue-400' : '' }}">precision_manufacturing</span>
                        <span>Production</span>
                    </a>

                    <a href="{{ route('inventory.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('inventory.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                        <span class="material-symbols-outlined {{ request()->routeIs('inventory.*') ? 'text-blue-400' : '' }}">inventory_2</span>
                        <span>Stock & Paper Reels</span>
                    </a>
                @endif

                <a href="{{ route('gate.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('gate.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                    <span class="material-symbols-outlined {{ request()->routeIs('gate.*') ? 'text-blue-400' : '' }}">local_shipping</span>
                    <span>Gate Register</span>
                </a>

                @if(auth()->user()?->isFinancialAuthorized())
                    <p class="px-3 pt-5 pb-2 text-[11px] uppercase font-semibold tracking-wider text-slate-500">Finance & Workforce</p>

                    <a href="{{ route('khata.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('khata.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                        <span class="material-symbols-outlined {{ request()->routeIs('khata.*') ? 'text-blue-400' : '' }}">account_balance_wallet</span>
                        <span>Party Khata</span>
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('attendance.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                        <span class="material-symbols-outlined {{ request()->routeIs('attendance.*') ? 'text-blue-400' : '' }}">badge</span>
                        <span>Worker Attendance</span>
                    </a>

                    <a href="{{ route('reports.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-navy-800 text-white border-l-4 border-blue-500' : 'text-slate-300 hover:bg-navy-800/60 hover:text-white' }}">
                        <span class="material-symbols-outlined {{ request()->routeIs('reports.*') ? 'text-blue-400' : '' }}">summarize</span>
                        <span>Day-End Reports</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- User Profile Footer & Sign Out -->
        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-navy-800/70">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-xs shrink-0">
                        {{ auth()->user()?->initials() ?? 'BK' }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ auth()->user()?->name ?? 'Mirza Sohail' }}</div>
                        <div class="text-[11px] text-slate-400 truncate">{{ ucfirst(strtolower(str_replace('_', ' ', auth()->user()?->role ?? 'Accountant'))) }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" data-no-offline>
                    @csrf
                    <button type="submit" title="Sign Out" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Wrapper -->
    <div id="workspace-shell" class="lg:ml-64 flex flex-col min-h-screen transition-[margin] duration-200">
        <!-- Top Bar -->
        <header class="sticky top-0 z-30 min-h-16 bg-white border-b border-slate-200 px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2 no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="text-sm text-slate-500 flex items-center gap-2">
                    <span class="font-medium text-slate-800">BK Packages</span>
                    <span>/</span>
                    <span class="font-semibold text-blue-600">@yield('breadcrumb', 'Overview')</span>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                <button id="ai-toggle-btn" onclick="window.BKAiCopilot?.toggle()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 shadow-xs transition-colors cursor-pointer"
                        title="Toggle AI Factory Assistant side panel">
                    <span class="material-symbols-outlined text-[16px]">auto_awesome</span>
                    <span>AI Assistant</span>
                </button>
                <button id="network-status-pill" onclick="openModal('modal-sync-queue')"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 cursor-pointer"
                        title="Click to inspect offline sync queue">
                    <span id="network-status-dot" class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span id="network-status-text">Online · Synced</span>
                </button>
                <span id="ai-header-date" class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                    {{ now()->format('l, d M Y') }}
                </span>
                @yield('header_actions')
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm no-print">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm no-print">
                    <div class="font-semibold mb-1 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        Please check the entered information:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('modals')

    <!-- Offline Sync Queue Inspector Modal -->
    <div id="modal-sync-queue" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Device Offline Queue (IndexedDB)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pending transactions saved locally when offline</p>
                </div>
                <button onclick="closeModal('modal-sync-queue')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="offline-queue-list" class="mt-4 space-y-2 max-h-64 overflow-y-auto">
                <div class="py-8 text-center text-sm text-slate-500">Checking local queue...</div>
            </div>
            <div class="pt-4 mt-4 flex items-center justify-between border-t border-slate-100">
                <button type="button" onclick="window.BKOffline?.clearErrors()" class="text-xs text-slate-500 hover:text-red-600 font-medium">
                    Clear Failed
                </button>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('modal-sync-queue')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-medium text-slate-700">Close</button>
                    <button type="button" onclick="window.BKOffline?.syncNow()" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">
                        Sync Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side AI Copilot Docked Panel (Side-by-Side on Desktop) -->
    <div id="ai-backdrop" onclick="window.BKAiCopilot?.toggle(false)" class="fixed inset-0 bg-slate-900/40 z-40 hidden lg:hidden no-print"></div>
    <aside id="ai-drawer"
           data-user-id="{{ auth()->id() }}"
           class="fixed top-0 right-0 h-screen w-full sm:w-[360px] min-[1366px]:w-[400px] bg-slate-50 border-l border-slate-200 z-40 flex flex-col transform translate-x-full transition-transform duration-200 shadow-lg no-print">
        <!-- Drawer Header -->
        <div class="px-4 py-3.5 bg-navy-900 text-white flex items-center justify-between gap-2 shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-semibold truncate">BK Packages AI</div>
                    <div id="ai-header-subtitle" class="text-[11px] text-slate-400 truncate">Side-by-Side · Live Data</div>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <button id="ai-history-btn" type="button" onclick="window.BKAiCopilot?.toggleHistory()"
                        title="Conversation History"
                        class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[17px]">history</span>
                    <span>History</span>
                </button>
                <button type="button" onclick="window.BKAiCopilot?.startNewChat()"
                        title="Start New Chat"
                        class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[17px]">edit_square</span>
                    <span>New</span>
                </button>
                <button type="button" onclick="window.BKAiCopilot?.toggle(false)"
                        title="Close AI Assistant"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>

        <!-- History View (Hidden by Default) -->
        <div id="ai-history-panel" class="hidden flex-1 flex flex-col overflow-hidden bg-slate-50">
            <div class="p-3 bg-white border-b border-slate-200 space-y-2 shrink-0">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Saved Conversations</span>
                    <button type="button" onclick="window.BKAiCopilot?.toggleHistory(false)"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">arrow_back</span>
                        <span>Back to Chat</span>
                    </button>
                </div>
                <input id="ai-history-search" type="text" placeholder="Search past chats..."
                       oninput="window.BKAiCopilot?.filterHistory(this.value)"
                       class="w-full rounded-lg border-slate-300 text-xs py-2 px-3 focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div id="ai-history-list" class="flex-1 overflow-y-auto p-3 space-y-2">
                <div class="py-10 text-center text-xs text-slate-400">Loading conversation history...</div>
            </div>
        </div>

        <!-- Active Chat View -->
        <div id="ai-chat-view" class="flex-1 flex flex-col overflow-hidden">
            <!-- Messages Container -->
            <div id="ai-messages" class="flex-1 overflow-y-auto p-4 space-y-3">
                <div id="ai-welcome-box" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
                    <div class="text-sm font-semibold text-slate-900">Assalam-o-Alaikum, {{ auth()->user()?->name ?? 'Mirza Sohail' }}!</div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Use this assistant <strong>side by side</strong> while working on any ERP page. Ask in <strong>English</strong> or <strong>Roman Urdu</strong> — all chats are saved automatically in <strong>History</strong>.
                    </p>
                    <div class="pt-2 space-y-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Try asking:</div>
                        @if(auth()->user()?->isFinancialAuthorized())
                            <button type="button" onclick="window.BKAiCopilot.askQuick('Ahmed Oil & Ghee ka kitna baqaya hai?')"
                                    class="w-full text-left px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs text-slate-700 transition-colors">
                                "Ahmed Oil & Ghee ka kitna baqaya hai?"
                            </button>
                            <button type="button" onclick="window.BKAiCopilot.askQuick('Show me today\'s cash, paper reels & carton stock summary')"
                                    class="w-full text-left px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs text-slate-700 transition-colors">
                                "Today's cash, paper reels & carton stock summary"
                            </button>
                            <button type="button" onclick="window.BKAiCopilot.askQuick('Record Rs. 50,000 cash received from Irfan Pharma Okara')"
                                    class="w-full text-left px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs text-slate-700 transition-colors">
                                "Record Rs. 50,000 cash received from Irfan Pharma"
                            </button>
                        @else
                            <button type="button" onclick="window.BKAiCopilot.askQuick('Show all trucks currently in the factory yard')"
                                    class="w-full text-left px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs text-slate-700 transition-colors">
                                "Show trucks currently in the factory yard"
                            </button>
                            <button type="button" onclick="window.BKAiCopilot.askQuick('How do I weigh a truck and print a gate pass?')"
                                    class="w-full text-left px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 text-xs text-slate-700 transition-colors">
                                "How do I weigh a truck and print a gate pass?"
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Chat Input Bar -->
            <form id="ai-chat-form" data-no-offline class="p-3 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0">
                <input id="ai-input" type="text" autocomplete="off"
                       placeholder="Ask or tell AI what to add..."
                       class="flex-1 rounded-xl border-slate-300 text-sm py-2.5 focus:border-blue-600 focus:ring-blue-600" />
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </form>
        </div>
    </aside>

    <script src="https://unpkg.com/dexie@4/dist/dexie.js"></script>
    <script src="/js/offline-sync.js"></script>
    <script src="/js/ai-copilot.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobile-backdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>

