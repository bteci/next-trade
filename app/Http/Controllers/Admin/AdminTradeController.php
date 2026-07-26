<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use App\Models\TradingAsset;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTradeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Trade::with(['user', 'tradingAsset'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('wallet_type')) {
            $query->where('wallet_type', $request->wallet_type);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('asset')) {
            $query->where('trading_asset_id', $request->asset);
        }

        if ($request->filled('email')) {
            $query->whereHas('user', fn($q) => $q->where('email', 'like', '%' . $request->email . '%'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $trades = $query->paginate(20)->withQueryString();

        $openCount        = Trade::where('status', 'open')->count();
        $tradesToday      = Trade::whereDate('opened_at', today())->count();
        $volumeToday      = Trade::whereDate('opened_at', today())->sum('stake_amount');
        $wonToday         = Trade::where('status', 'won')->whereDate('closed_at', today())->count();
        $lostToday        = Trade::where('status', 'lost')->whereDate('closed_at', today())->count();
        $platformPnlToday = Trade::whereIn('status', ['won', 'lost', 'draw'])
            ->whereDate('closed_at', today())
            ->selectRaw('COALESCE(SUM(stake_amount - payout), 0) as pnl')
            ->value('pnl');

        $assets = TradingAsset::orderBy('symbol')->get(['id', 'symbol']);

        return view('admin.trades.index', compact(
            'trades', 'assets', 'openCount', 'tradesToday',
            'volumeToday', 'wonToday', 'lostToday', 'platformPnlToday'
        ));
    }
}
