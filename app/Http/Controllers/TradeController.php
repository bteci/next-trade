<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradingAsset;
use App\Services\TradingEngineService;
use App\Services\UserActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradeController extends Controller
{
    public function __construct(
        private TradingEngineService   $engine,
        private UserActivityLogService $activityLog
    ) {}

    public function index(): View
    {
        // Auto-seed assets if none exist (handles first-deploy or failed seeder)
        if (TradingAsset::count() === 0) {
            app(\Database\Seeders\TradingAssetsSeeder::class)->run($this->engine);
        }

        $assets     = TradingAsset::where('is_active', true)->orderBy('sort_order')->get();
        $walletMode = session('wallet_mode', 'demo');
        $wallet     = auth()->user()->activeWallet($walletMode);

        $activeTrades = auth()->user()
            ->trades()
            ->where('status', 'open')
            ->with('tradingAsset')
            ->orderByDesc('opened_at')
            ->get();

        $recentTrades = auth()->user()
            ->trades()
            ->whereIn('status', ['won', 'lost', 'draw', 'cancelled'])
            ->with('tradingAsset')
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get();

        return view('trading.index', compact('assets', 'walletMode', 'wallet', 'activeTrades', 'recentTrades'));
    }

    public function place(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id'       => 'required|integer|exists:trading_assets,id',
            'direction'      => 'required|in:buy,sell',
            'amount'         => 'required|numeric|min:1',
            'expiry_seconds' => 'required|integer|in:30,60,120,300',
        ]);

        $user       = auth()->user();
        $walletMode = session('wallet_mode', 'demo');
        $asset      = TradingAsset::findOrFail($validated['asset_id']);
        $wallet     = $user->activeWallet($walletMode);

        if (! $wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found.'], 422);
        }

        try {
            $trade = $this->engine->placeTrade(
                $user,
                $asset,
                $validated['direction'],
                $validated['amount'],
                $validated['expiry_seconds'],
                $walletMode
            );

            $wallet->refresh();

            $this->activityLog->log($user, 'trade_placed',
                ucfirst($validated['direction']) . ' $' . number_format($trade->stake_amount, 2) . ' on ' . $asset->symbol,
                ['trade_id' => $trade->id, 'wallet' => $walletMode]);

            return response()->json([
                'success'        => true,
                'message'        => 'Trade placed successfully.',
                'trade'          => $this->formatTrade($trade->load('tradingAsset')),
                'wallet_balance' => (float) $wallet->available_balance,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function active(Request $request): JsonResponse
    {
        $user       = auth()->user();
        $walletMode = session('wallet_mode', 'demo');

        // Auto-settle expired trades for this user
        $expired = $user->trades()
            ->where('status', 'open')
            ->where('expires_at', '<=', now())
            ->with(['wallet', 'tradingAsset'])
            ->get();

        foreach ($expired as $trade) {
            try {
                $this->engine->settleTrade($trade);
            } catch (\Throwable) {
                // continue — never break the polling loop
            }
        }

        $trades = $user->trades()
            ->where('status', 'open')
            ->with('tradingAsset')
            ->orderByDesc('opened_at')
            ->get()
            ->map(fn (Trade $t) => $this->formatTrade($t));

        $wallet = $user->activeWallet($walletMode);

        return response()->json([
            'trades'          => $trades,
            'wallet_balance'  => $wallet ? (float) $wallet->available_balance : 0,
            'locked_balance'  => $wallet ? (float) $wallet->locked_balance : 0,
        ]);
    }

    public function recent(): JsonResponse
    {
        $trades = auth()->user()
            ->trades()
            ->whereIn('status', ['won', 'lost', 'draw', 'cancelled'])
            ->with('tradingAsset')
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get()
            ->map(fn (Trade $t) => [
                'id'           => $t->id,
                'asset'        => ['id' => $t->tradingAsset->id, 'symbol' => $t->tradingAsset->symbol, 'type' => $t->tradingAsset->type],
                'direction'    => $t->direction,
                'stake_amount' => (float) $t->stake_amount,
                'entry_price'  => (float) $t->entry_price,
                'exit_price'   => $t->exit_price ? (float) $t->exit_price : null,
                'profit_loss'  => $t->profit_loss ? (float) $t->profit_loss : null,
                'payout'       => $t->payout ? (float) $t->payout : null,
                'status'       => $t->status,
                'wallet_type'  => $t->wallet_type,
                'closed_at'    => $t->closed_at?->toISOString(),
            ]);

        return response()->json($trades);
    }

    private function formatTrade(Trade $t): array
    {
        return [
            'id'             => $t->id,
            'asset'          => [
                'id'     => $t->tradingAsset->id,
                'symbol' => $t->tradingAsset->symbol,
                'type'   => $t->tradingAsset->type,
                'name'   => $t->tradingAsset->name,
            ],
            'direction'      => $t->direction,
            'stake_amount'   => (float) $t->stake_amount,
            'entry_price'    => (float) $t->entry_price,
            'expiry_seconds' => $t->expiry_seconds,
            'time_remaining' => $t->time_remaining,
            'wallet_type'    => $t->wallet_type,
            'opened_at'      => $t->opened_at->toISOString(),
            'expires_at'     => $t->expires_at->toISOString(),
            'status'         => $t->status,
        ];
    }
}
