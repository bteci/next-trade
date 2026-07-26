@extends('layouts.app')
@section('title', $user->name.' | Admin')
@section('page-title', $user->name)
@section('page-subtitle', $user->email)

@section('content')
<div x-data="{ showBan: false, showMakeAdmin: false, showFreeze: false }" class="max-w-5xl mx-auto space-y-6">

@if(session('success'))
<div class="px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ session('error') }}</div>
@endif

{{-- Action Bar --}}
<div class="flex flex-wrap gap-3">
    @if(!$user->is_banned)
    <button @click="showBan = true" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 hover:bg-red-500/25 text-sm font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        Ban User
    </button>
    @else
    <form method="POST" action="{{ route('admin.users.unban', $user) }}">@csrf
        <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/25 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Unban User
        </button>
    </form>
    @endif

    @if($user->wallets->where('status','frozen')->count() > 0)
    <form method="POST" action="{{ route('admin.users.unfreeze-wallets', $user) }}">@csrf
        <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 hover:bg-amber-500/25 text-sm font-medium transition-colors">
            Unfreeze Wallets
        </button>
    </form>
    @else
    <button @click="showFreeze = true" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 hover:bg-amber-500/25 text-sm font-medium transition-colors">
        Freeze Wallets
    </button>
    @endif

    @if(!$user->is_admin)
    <button @click="showMakeAdmin = true" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-500/15 border border-violet-500/30 text-violet-400 hover:bg-violet-500/25 text-sm font-medium transition-colors">
        Make Admin
    </button>
    @else
    <form method="POST" action="{{ route('admin.users.remove-admin', $user) }}">@csrf
        <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-500/15 border border-gray-500/30 text-gray-400 hover:bg-gray-500/25 text-sm font-medium transition-colors"
                onclick="return confirm('Remove admin access from {{ $user->name }}?')">
            Remove Admin
        </button>
    </form>
    @endif

    <a href="{{ route('admin.users') }}" class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition-colors"
       :class="isDark ? 'border-gray-700 text-gray-400 hover:text-white' : 'border-gray-300 text-gray-600'">
        ← Back
    </a>
</div>

{{-- Profile + Wallet Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Profile --}}
    <div class="rounded-2xl border divide-y" :class="isDark ? 'bg-gray-900/60 border-gray-800/60 divide-gray-800/60' : 'bg-white border-gray-200 shadow-sm divide-gray-100'">
        <div class="px-5 py-3"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Profile</p></div>
        @foreach([
            ['Name',       $user->name],
            ['Email',      $user->email],
            ['Username',   '@'.($user->username ?? '—')],
            ['Phone',      $user->phone ?? '—'],
            ['Country',    $user->country ?? '—'],
            ['Joined',     $user->created_at->format('M j, Y H:i')],
            ['Last Login',  $user->last_login_at?->format('M j, Y H:i') ?? 'Never'],
        ] as [$label, $value])
        <div class="px-5 py-3 flex justify-between gap-4">
            <span class="text-xs text-gray-500">{{ $label }}</span>
            <span class="text-xs font-medium text-right" :class="isDark ? 'text-gray-300' : 'text-gray-700'">{{ $value }}</span>
        </div>
        @endforeach
        <div class="px-5 py-3 flex justify-between gap-4">
            <span class="text-xs text-gray-500">Status</span>
            <div class="flex gap-2 flex-wrap justify-end">
                @if($user->is_banned)<span class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20">Banned</span>@endif
                @if($user->is_admin)<span class="text-xs px-2 py-0.5 rounded-full bg-violet-500/15 text-violet-400 border border-violet-500/20">Admin</span>@endif
                @if(!$user->is_banned && !$user->is_admin)<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">Active</span>@endif
            </div>
        </div>
    </div>

    {{-- Wallets --}}
    <div class="rounded-2xl border divide-y" :class="isDark ? 'bg-gray-900/60 border-gray-800/60 divide-gray-800/60' : 'bg-white border-gray-200 shadow-sm divide-gray-100'">
        <div class="px-5 py-3"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Wallets</p></div>
        @foreach($user->wallets as $wallet)
        <div class="px-5 py-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold capitalize" :class="isDark ? 'text-white' : 'text-gray-900'">
                    {{ ucfirst($wallet->type) }} Wallet
                    @if($wallet->isFrozen())<span class="ml-2 text-xs text-red-400 font-normal">Frozen</span>@endif
                </p>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $wallet->isFrozen() ? 'bg-red-500/15 text-red-400 border border-red-500/20' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20' }}">
                    {{ ucfirst($wallet->status) }}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-gray-500">Balance</span><p class="font-mono font-semibold" :class="isDark ? 'text-white' : 'text-gray-900'">${{ number_format($wallet->balance, 2) }}</p></div>
                <div><span class="text-gray-500">Available</span><p class="font-mono" :class="isDark ? 'text-gray-300' : 'text-gray-700'">${{ number_format($wallet->available_balance, 2) }}</p></div>
                <div><span class="text-gray-500">Locked</span><p class="font-mono text-amber-400">${{ number_format($wallet->locked_balance, 2) }}</p></div>
                <div><span class="text-gray-500">Deposited</span><p class="font-mono text-cyan-400">${{ number_format($wallet->total_deposited, 2) }}</p></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Admin Roles (if admin) --}}
@if($user->is_admin)
<div class="rounded-2xl border divide-y" :class="isDark ? 'bg-gray-900/60 border-gray-800/60 divide-gray-800/60' : 'bg-white border-gray-200 shadow-sm divide-gray-100'">
    <div class="px-5 py-3 flex items-center justify-between">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin Roles</p>
        <a href="{{ route('admin.admins') }}" class="text-xs text-cyan-400 hover:text-cyan-300 transition-colors">Manage →</a>
    </div>
    @forelse($user->adminRoles as $role)
    <div class="px-5 py-3 flex items-center justify-between">
        <div>
            <p class="text-xs font-medium" :class="isDark ? 'text-white' : 'text-gray-900'">{{ $role->name }}</p>
            <p class="text-xs text-gray-500">{{ $role->permissions->pluck('name')->implode(', ') ?: 'No permissions' }}</p>
        </div>
        @if($role->is_system)<span class="text-[10px] text-gray-500">system</span>@endif
    </div>
    @empty
    <div class="px-5 py-3 text-xs text-gray-500">No roles assigned.</div>
    @endforelse
</div>
@endif

{{-- Activity tables --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Recent Deposits --}}
    @include('admin.users._activity_table', ['title' => 'Recent Deposits', 'items' => $user->paymentDeposits, 'cols' => [
        ['label' => 'Method',  'value' => fn($d) => $d->method_label],
        ['label' => 'Amount',  'value' => fn($d) => '$'.number_format($d->usd_amount,2)],
        ['label' => 'Status',  'value' => fn($d) => ucfirst($d->status)],
        ['label' => 'Date',    'value' => fn($d) => $d->created_at->format('M j, Y')],
    ]])

    {{-- Recent Withdrawals --}}
    @include('admin.users._activity_table', ['title' => 'Recent Withdrawals', 'items' => $user->withdrawals, 'cols' => [
        ['label' => 'Method',  'value' => fn($w) => $w->method_label],
        ['label' => 'Amount',  'value' => fn($w) => '$'.number_format($w->usd_amount,2)],
        ['label' => 'Status',  'value' => fn($w) => ucfirst($w->status)],
        ['label' => 'Date',    'value' => fn($w) => $w->created_at->format('M j, Y')],
    ]])
</div>

{{-- Recent Trades --}}
<div class="rounded-2xl border overflow-hidden" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
    <div class="p-5 border-b" :class="isDark ? 'border-gray-800/60' : 'border-gray-100'">
        <p class="text-sm font-semibold" :class="isDark ? 'text-white' : 'text-gray-900'">Recent Trades</p>
    </div>
    <div class="divide-y" :class="isDark ? 'divide-gray-800/60' : 'divide-gray-100'">
        @forelse($user->trades as $trade)
        <div class="flex items-center justify-between px-5 py-3">
            <div>
                <p class="text-xs font-medium" :class="isDark ? 'text-white' : 'text-gray-900'">{{ $trade->tradingAsset->symbol ?? '—' }} · {{ ucfirst($trade->direction) }}</p>
                <p class="text-xs text-gray-500">{{ $trade->created_at->format('M j, Y H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-mono">${{ number_format($trade->stake_amount, 2) }}</p>
                <p class="text-xs {{ $trade->status === 'won' ? 'text-emerald-400' : ($trade->status === 'lost' ? 'text-red-400' : 'text-amber-400') }} capitalize">{{ $trade->status }}</p>
            </div>
        </div>
        @empty
        <p class="px-5 py-6 text-xs text-gray-500 text-center">No trades.</p>
        @endforelse
    </div>
</div>

{{-- Ban History --}}
@if($user->bans->isNotEmpty())
<div class="rounded-2xl border divide-y" :class="isDark ? 'bg-gray-900/60 border-gray-800/60 divide-gray-800/60' : 'bg-white border-gray-200 shadow-sm divide-gray-100'">
    <div class="px-5 py-3"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ban History</p></div>
    @foreach($user->bans as $ban)
    <div class="px-5 py-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium" :class="isDark ? 'text-white' : 'text-gray-900'">{{ $ban->reason ?? 'No reason given' }}</p>
                <p class="text-xs text-gray-500">By {{ $ban->bannedBy?->name ?? 'System' }} · {{ $ban->created_at->format('M j, Y H:i') }}</p>
                @if($ban->ends_at)<p class="text-xs text-amber-400">Expires: {{ $ban->ends_at->format('M j, Y H:i') }}</p>@endif
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full {{ $ban->is_active ? 'bg-red-500/15 text-red-400 border border-red-500/20' : 'bg-gray-500/15 text-gray-400 border border-gray-500/20' }}">
                {{ $ban->is_active ? 'Active' : 'Lifted' }}
            </span>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Admin Activity --}}
@if($recentActivity->isNotEmpty())
<div class="rounded-2xl border divide-y" :class="isDark ? 'bg-gray-900/60 border-gray-800/60 divide-gray-800/60' : 'bg-white border-gray-200 shadow-sm divide-gray-100'">
    <div class="px-5 py-3"><p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin Activity on this Account</p></div>
    @foreach($recentActivity as $log)
    <div class="px-5 py-3">
        <p class="text-xs font-medium" :class="isDark ? 'text-gray-300' : 'text-gray-700'">{{ $log->action_label }}</p>
        <p class="text-xs text-gray-500">By {{ $log->admin?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p>
    </div>
    @endforeach
</div>
@endif

{{-- Ban Modal --}}
<div x-show="showBan" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showBan = false"></div>
    <div class="relative w-full max-w-md rounded-2xl border p-6 z-10" :class="isDark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200 shadow-xl'">
        <h3 class="text-base font-bold mb-2" :class="isDark ? 'text-white' : 'text-gray-900'">Ban {{ $user->name }}</h3>
        <form method="POST" action="{{ route('admin.users.ban', $user) }}">@csrf
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Reason (optional)</label>
                <textarea name="reason" rows="3" class="w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-red-500/40"
                          :class="isDark ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900'"
                          placeholder="Reason for ban…"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Ban expires (optional)</label>
                <input type="datetime-local" name="ends_at" class="w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-red-500/40"
                       :class="isDark ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showBan = false" class="flex-1 px-4 py-2.5 rounded-xl border text-sm font-medium" :class="isDark ? 'border-gray-700 text-gray-400' : 'border-gray-300 text-gray-600'">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 hover:bg-red-400 text-white text-sm font-bold transition-colors">Ban User</button>
            </div>
        </form>
    </div>
</div>

{{-- Make Admin Modal --}}
<div x-show="showMakeAdmin" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showMakeAdmin = false"></div>
    <div class="relative w-full max-w-md rounded-2xl border p-6 z-10" :class="isDark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200 shadow-xl'">
        <h3 class="text-base font-bold mb-2" :class="isDark ? 'text-white' : 'text-gray-900'">Promote {{ $user->name }}</h3>
        <p class="text-sm text-gray-500 mb-4">Assign an admin role to grant access to the admin panel.</p>
        <form method="POST" action="{{ route('admin.users.make-admin', $user) }}">@csrf
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Role</label>
                <select name="role" class="w-full px-3 py-2 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/40"
                        :class="isDark ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @foreach($allRoles as $role)
                    <option value="{{ $role->slug }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="showMakeAdmin = false" class="flex-1 px-4 py-2.5 rounded-xl border text-sm font-medium" :class="isDark ? 'border-gray-700 text-gray-400' : 'border-gray-300 text-gray-600'">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-violet-500 hover:bg-violet-400 text-white text-sm font-bold transition-colors">Promote</button>
            </div>
        </form>
    </div>
</div>

{{-- Freeze Modal --}}
<div x-show="showFreeze" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showFreeze = false"></div>
    <div class="relative w-full max-w-md rounded-2xl border p-6 z-10" :class="isDark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200 shadow-xl'">
        <h3 class="text-base font-bold mb-2" :class="isDark ? 'text-white' : 'text-gray-900'">Freeze Wallets</h3>
        <p class="text-sm text-gray-500 mb-4">This will freeze all wallets for {{ $user->name }}. They will be unable to deposit, withdraw, trade, or invest.</p>
        <form method="POST" action="{{ route('admin.users.freeze-wallets', $user) }}">@csrf
            <div class="flex gap-3">
                <button type="button" @click="showFreeze = false" class="flex-1 px-4 py-2.5 rounded-xl border text-sm font-medium" :class="isDark ? 'border-gray-700 text-gray-400' : 'border-gray-300 text-gray-600'">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-sm font-bold transition-colors">Freeze All Wallets</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
