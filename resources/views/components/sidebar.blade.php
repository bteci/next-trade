<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-30 w-64 flex flex-col transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 glassmorphism"
       :class="[
           sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
           isDark ? 'bg-gray-900/95 border-r border-gray-800/60' : 'bg-white/95 border-r border-gray-200/60'
       ]">

    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-5 border-b flex-shrink-0"
         :class="isDark ? 'border-gray-800/60' : 'border-gray-200/60'">
        <a href="{{ route('trade.index') }}" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <span class="text-base font-bold" :class="isDark ? 'text-white' : 'text-gray-900'">
                Next<span class="text-cyan-400">Trade</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Wallet Mode Switcher -->
    <div class="px-4 py-3 border-b flex-shrink-0"
         :class="isDark ? 'border-gray-800/60' : 'border-gray-200/60'">
        @php $walletMode = session('wallet_mode', 'demo'); @endphp
        <div class="flex rounded-lg p-1 gap-1"
             :class="isDark ? 'bg-gray-800/80' : 'bg-gray-100'">
            @foreach(['demo','live'] as $m)
            <form method="POST" action="{{ route('wallet.mode') }}" class="flex-1">
                @csrf
                <input type="hidden" name="mode" value="{{ $m }}">
                <button type="submit"
                        class="w-full text-xs py-1.5 rounded-md font-medium transition-all duration-200 {{ $walletMode === $m ? 'bg-cyan-500 text-white shadow-sm shadow-cyan-500/30' : 'text-gray-400 hover:text-white' }}">
                    {{ ucfirst($m) }}
                </button>
            </form>
            @endforeach
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

        @php
            $navItems = [
                ['route' => 'trade.index',        'label' => 'Trade',        'icon' => 'trending-up'],
                ['route' => 'bots.index',         'label' => 'Bots',         'icon' => 'cpu'],
                ['route' => 'wallet.index',       'label' => 'Wallet',       'icon' => 'credit-card'],
                ['route' => 'transactions.index', 'label' => 'Transactions', 'icon' => 'list'],
                ['route' => 'profile.edit',       'label' => 'Profile',      'icon' => 'user'],
                ['route' => 'settings.index',     'label' => 'Settings',     'icon' => 'settings'],
                ['route' => 'support.index',      'label' => 'Support',      'icon' => 'help-circle'],
            ];
        @endphp

        @foreach($navItems as $item)
            @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
            <a href="{{ route($item['route']) }}"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group"
               :class="isDark
                   ? '{{ $active ? 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}'
                   : '{{ $active ? 'bg-cyan-50 text-cyan-600 border border-cyan-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}'">
                <svg class="w-4 h-4 flex-shrink-0 {{ $active ? 'text-cyan-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @switch($item['icon'])
                        @case('grid')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm9-9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zm0 9a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>@break
                        @case('trending-up')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>@break
                        @case('cpu')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3H7a2 2 0 00-2 2v2M9 3h6M9 3v2m6-2h2a2 2 0 012 2v2m0 0V7m0 4h2m-2 0v6m0 0h2m-2 0a2 2 0 01-2 2h-2m0 0V19m0 2H9m0 0H7a2 2 0 01-2-2v-2m0 0H3m2 0V9m0 0H3m2 0a2 2 0 012-2h2m0 0V5"/><rect x="9" y="9" width="6" height="6" rx="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>@break
                        @case('credit-card')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>@break
                        @case('list')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>@break
                        @case('user')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>@break
                        @case('settings')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>@break
                        @case('help-circle')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>@break
                    @endswitch
                </svg>
                <span>{{ $item['label'] }}</span>
                @if($active)
                <div class="ml-auto w-1.5 h-1.5 rounded-full bg-cyan-400"></div>
                @endif
            </a>
        @endforeach

        @if(auth()->user()?->is_admin)
        <!-- Admin Section -->
        <div class="pt-4 mt-2 border-t" :class="isDark ? 'border-gray-800/60' : 'border-gray-200/60'">
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest"
               :class="isDark ? 'text-gray-600' : 'text-gray-400'">Admin</p>

            @php
                $adminItems = [
                    ['route' => 'admin.dashboard',      'label' => 'Admin Dashboard', 'icon' => 'shield'],
                    ['route' => 'admin.users',          'label' => 'Users',           'icon' => 'users'],
                    ['route' => 'admin.deposits',       'label' => 'Deposits',        'icon' => 'inbox'],
                    ['route' => 'admin.withdrawals',    'label' => 'Withdrawals',     'icon' => 'arrow-up-circle'],
                    ['route' => 'admin.trading-engine', 'label' => 'Trading Engine',  'icon' => 'activity'],
                    ['route' => 'admin.trades',         'label' => 'Trades',          'icon' => 'trending-up'],
                    ['route' => 'admin.assets',         'label' => 'Assets',          'icon' => 'database'],
                    ['route' => 'admin.bots',           'label' => 'Bot Plans',       'icon' => 'bot'],
                    ['route' => 'admin.roles',          'label' => 'Roles',           'icon' => 'tag'],
                    ['route' => 'admin.permissions',    'label' => 'Permissions',     'icon' => 'lock'],
                    ['route' => 'admin.admins',         'label' => 'Admins',          'icon' => 'users-check'],
                    ['route' => 'admin.audit-logs',     'label' => 'Audit Logs',      'icon' => 'file-text'],
                    ['route' => 'admin.referrals',      'label' => 'Referrals',       'icon' => 'users'],
                    ['route' => 'admin.system-settings','label' => 'System Settings', 'icon' => 'sliders'],
                    ['route' => 'admin.system-health',  'label' => 'System Health',   'icon' => 'activity'],
                ];
            @endphp

            @foreach($adminItems as $item)
                @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 mt-0.5"
                   :class="isDark
                       ? '{{ $active ? 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/20' : 'text-gray-500 hover:bg-gray-800 hover:text-white' }}'
                       : '{{ $active ? 'bg-cyan-50 text-cyan-600 border border-cyan-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}'">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @switch($item['icon'])
                            @case('shield')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>@break
                            @case('users')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>@break
                            @case('activity')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M22 12h-4l-3 9L9 3l-3 9H2"/>@break
                            @case('trending-up')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>@break
                            @case('dollar-sign')<line x1="12" y1="1" x2="12" y2="23" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>@break
                            @case('lock')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>@break
                            @case('database')<ellipse cx="12" cy="5" rx="9" ry="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12c0 1.66-4.03 3-9 3S3 13.66 3 12"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>@break
                            @case('bot')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3H7a2 2 0 00-2 2v2M9 3h6M9 3v2m6-2h2a2 2 0 012 2v2m0 0V7m0 4h2m-2 0v6m0 0h2m-2 0a2 2 0 01-2 2h-2m0 0V19m0 2H9m0 0H7a2 2 0 01-2-2v-2m0 0H3m2 0V9m0 0H3m2 0a2 2 0 012-2h2m0 0V5"/><rect x="9" y="9" width="6" height="6" rx="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>@break
                            @case('inbox')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>@break
                            @case('arrow-up-circle')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z"/>@break
                            @case('sliders')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/><circle cx="17" cy="18" r="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><circle cx="10" cy="12" r="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><circle cx="7" cy="6" r="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>@break
                            @case('tag')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>@break
                            @case('users-check')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 10l-2 2-1-1"/>@break
                            @case('file-text')<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>@break
                        @endswitch
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
        @endif
    </nav>

    <!-- User info at bottom -->
    <div class="p-4 border-t flex-shrink-0" :class="isDark ? 'border-gray-800/60' : 'border-gray-200/60'">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-cyan-700 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold truncate" :class="isDark ? 'text-gray-200' : 'text-gray-800'">{{ auth()->user()?->name }}</p>
                <p class="text-[10px] truncate text-gray-500">{{ auth()->user()?->email }}</p>
            </div>
        </div>
    </div>
</aside>
