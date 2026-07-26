@extends('layouts.app')
@section('title', 'Trades | Admin')
@section('page-title', 'Trades')
@section('page-subtitle', 'Monitor all ongoing and completed trades')

@section('content')

{{-- Summary Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Open Now</p>
        <p class="text-2xl font-bold text-amber-400">{{ $openCount }}</p>
        <a href="{{ route('admin.trades', ['status' => 'open']) }}" class="text-[10px] text-amber-400/60 hover:text-amber-400 transition-colors">View →</a>
    </div>
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Trades Today</p>
        <p class="text-2xl font-bold text-cyan-400">{{ $tradesToday }}</p>
    </div>
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Volume Today</p>
        <p class="text-xl font-bold" :class="isDark ? 'text-white' : 'text-gray-900'">${{ number_format($volumeToday, 2) }}</p>
    </div>
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Won Today</p>
        <p class="text-2xl font-bold text-emerald-400">{{ $wonToday }}</p>
    </div>
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Lost Today</p>
        <p class="text-2xl font-bold text-red-400">{{ $lostToday }}</p>
    </div>
    <div class="rounded-xl border p-4" :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">
        <p class="text-[10px] text-gray-500 uppercase tracking-wide mb-1">Platform P/L Today</p>
        <p class="text-xl font-bold {{ $platformPnlToday >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
            {{ $platformPnlToday < 0 ? '-' : '' }}${{ number_format(abs($platformPnlToday), 2) }}
        </p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.trades') }}"
      class="rounded-xl border p-4 mb-5 space-y-3"
      :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        <select name="status"
                class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
                :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <option value="">All Statuses</option>
            @foreach(['open','won','lost','draw','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="wallet_type"
                class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
                :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <option value="">All Wallets</option>
            <option value="live" {{ request('wallet_type') === 'live' ? 'selected' : '' }}>Live</option>
            <option value="demo" {{ request('wallet_type') === 'demo' ? 'selected' : '' }}>Demo</option>
        </select>

        <select name="direction"
                class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
                :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <option value="">All Directions</option>
            <option value="buy" {{ request('direction') === 'buy' ? 'selected' : '' }}>Buy (Up)</option>
            <option value="sell" {{ request('direction') === 'sell' ? 'selected' : '' }}>Sell (Down)</option>
        </select>

        <select name="asset"
                class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
                :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <option value="">All Assets</option>
            @foreach($assets as $a)
            <option value="{{ $a->id }}" {{ request('asset') == $a->id ? 'selected' : '' }}>{{ $a->symbol }}</option>
            @endforeach
        </select>

        <input type="text" name="email" value="{{ request('email') }}" placeholder="Filter by email"
               class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
               :class="isDark ? 'border-gray-700 text-gray-300 placeholder-gray-600' : 'border-gray-300 text-gray-700'">

        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
               :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">

        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-3 py-2 text-xs rounded-lg border bg-transparent focus:outline-none focus:ring-1 focus:ring-cyan-500/50"
               :class="isDark ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
    </div>

    <div class="flex gap-2">
        <button type="submit"
                class="px-4 py-2 text-xs font-semibold rounded-lg text-white"
                style="background: linear-gradient(135deg,#06b6d4,#0891b2)">
            Apply Filters
        </button>
        @if(request()->hasAny(['status','wallet_type','direction','asset','email','date_from','date_to']))
        <a href="{{ route('admin.trades') }}"
           class="px-4 py-2 text-xs font-medium rounded-lg border transition-colors"
           :class="isDark ? 'border-gray-700 text-gray-400 hover:border-gray-600' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
            Clear
        </a>
        @endif
        <a href="{{ route('admin.export.trades') }}"
           class="ml-auto px-4 py-2 text-xs font-medium rounded-lg border transition-colors"
           :class="isDark ? 'border-gray-700 text-gray-400 hover:border-gray-600' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
            Export CSV
        </a>
    </div>
</form>

{{-- Trades Table --}}
<div class="rounded-2xl border overflow-hidden"
     :class="isDark ? 'bg-gray-900/60 border-gray-800/60' : 'bg-white border-gray-200 shadow-sm'">

    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b" :class="isDark ? 'border-gray-800/60' : 'border-gray-100'">
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">User</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Asset</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Direction</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Stake</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Entry</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Exit</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Payout</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Wallet</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Opened</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 uppercase tracking-wide text-[10px]">Closes / Closed</th>
                </tr>
            </thead>
            <tbody class="divide-y" :class="isDark ? 'divide-gray-800/40' : 'divide-gray-50'">
                @forelse($trades as $trade)
                <tr class="transition-colors {{ $trade->isOpen() ? 'ring-1 ring-inset ring-amber-500/10' : '' }}"
                    :class="isDark ? 'hover:bg-gray-800/30' : 'hover:bg-gray-50'">
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium" :class="isDark ? 'text-gray-300' : 'text-gray-700'">{{ $trade->user?->name ?? '—' }}</p>
                            <p class="text-[10px] text-gray-500">{{ $trade->user?->email ?? '—' }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-semibold" :class="isDark ? 'text-gray-300' : 'text-gray-700'">
                        {{ $trade->tradingAsset?->symbol ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($trade->direction === 'buy')
                        <span class="inline-flex items-center gap-1 text-emerald-400 font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            Buy
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-red-400 font-medium">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            Sell
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium" :class="isDark ? 'text-gray-300' : 'text-gray-700'">
                        ${{ number_format($trade->stake_amount, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[10px]" :class="isDark ? 'text-gray-400' : 'text-gray-600'">
                        {{ rtrim(rtrim(number_format($trade->entry_price, 5), '0'), '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[10px]" :class="isDark ? 'text-gray-400' : 'text-gray-600'">
                        {{ $trade->exit_price !== null ? rtrim(rtrim(number_format($trade->exit_price, 5), '0'), '.') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold
                        {{ $trade->status === 'won' ? 'text-emerald-400' : ($trade->status === 'lost' ? 'text-red-400' : '') }}"
                        :class="{{ in_array($trade->status, ['won','lost']) ? 'false' : 'true' }} ? (isDark ? 'text-gray-300' : 'text-gray-700') : ''">
                        {{ $trade->payout !== null ? '$' . number_format($trade->payout, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="capitalize text-[10px] px-2 py-0.5 rounded-full
                            {{ $trade->wallet_type === 'live' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                            {{ $trade->wallet_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-[10px] px-2.5 py-1 rounded-full font-semibold
                            @if($trade->status === 'won') bg-emerald-500/10 border border-emerald-500/20 text-emerald-400
                            @elseif($trade->status === 'lost') bg-red-500/10 border border-red-500/20 text-red-400
                            @elseif($trade->status === 'open') bg-amber-500/10 border border-amber-500/20 text-amber-400
                            @elseif($trade->status === 'draw') bg-cyan-500/10 border border-cyan-500/20 text-cyan-400
                            @else bg-gray-500/10 border border-gray-700 text-gray-400
                            @endif">
                            {{ ucfirst($trade->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-[10px] text-gray-500 whitespace-nowrap">
                        {{ $trade->opened_at?->format('d M, H:i:s') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-[10px] text-gray-500 whitespace-nowrap">
                        @if($trade->isOpen())
                            {{ $trade->expires_at?->format('d M, H:i:s') ?? '—' }}
                            @if($trade->isExpired())
                            <br><span class="text-[9px] text-amber-400 font-semibold">Awaiting settlement</span>
                            @endif
                        @else
                            {{ $trade->closed_at?->format('d M, H:i:s') ?? '—' }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="px-4 py-12 text-center text-gray-500 text-sm">
                        No trades found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trades->hasPages())
    <div class="px-4 py-3 border-t" :class="isDark ? 'border-gray-800/60' : 'border-gray-100'">
        {{ $trades->links() }}
    </div>
    @endif
</div>

@endsection
