<?php

namespace App\Services;

use App\Models\PriceTick;
use App\Models\Trade;
use App\Models\TradingAsset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TradingEngineService
{
    public const ALLOWED_EXPIRY       = [30, 60, 120, 300];
    public const DEFAULT_MIN_PROFIT   = 0.70;
    public const DEFAULT_MAX_PROFIT   = 0.95;

    public function __construct(
        private WalletService           $walletService,
        private SimulationConfigService $simConfig,
        private NotificationService     $notifier
    ) {}

    // ─── Price Generation ─────────────────────────────────────────────────────

    public function generateNextTick(TradingAsset $asset, ?\Carbon\Carbon $tickTime = null, ?string $biasDirection = null): PriceTick
    {
        $currentPrice = (float) $asset->current_price;
        $config       = $this->simConfig->getActiveConfig();

        // Apply volatility multiplier from active simulation config
        $baseVolatility = (float) $asset->volatility;
        $multiplier     = $config ? (float) $config->volatility_multiplier : 1.0;
        $volatility     = $baseVolatility * $multiplier;

        // Cap extreme draws so a single tick can never print a monster wick
        $z = max(-3.0, min(3.0, $this->randomNormal()));

        if ($biasDirection !== null) {
            // Settlement tick: force direction, keep realistic magnitude
            $magnitude     = abs($z * $volatility);
            $changePercent = $biasDirection === 'up' ? $magnitude : -$magnitude;
        } else {
            // Normal tick: apply asset trend bias scaled by trend_strength
            $trendStrength = $config ? (float) $config->trend_strength : 0.5;
            $biasFactor    = match ($asset->trend_bias) {
                'bullish' => 0.0002 * $trendStrength,
                'bearish' => -0.0002 * $trendStrength,
                default   => 0.0,
            };

            // Mean reversion: gently pull the walk back toward base price so it
            // never drifts into the hard clamp rails (10%–500% of base)
            $basePrice = (float) $asset->base_price;
            $reversion = $basePrice > 0
                ? (($basePrice - $currentPrice) / $basePrice) * 0.004
                : 0.0;

            $changePercent = ($z * $volatility) + $biasFactor + $reversion;
        }

        $newPrice = $currentPrice * (1.0 + $changePercent);

        // Clamp to sane range (10%–500% of base)
        $base     = (float) $asset->base_price;
        $newPrice = max($base * 0.10, min($base * 5.0, $newPrice));
        $newPrice = max(0.00000001, $newPrice);

        $precision = $this->pricePrecision($asset);
        $newPrice  = round($newPrice, $precision);

        $direction = match (true) {
            $newPrice > $currentPrice => 'up',
            $newPrice < $currentPrice => 'down',
            default                   => 'flat',
        };

        $tick = PriceTick::create([
            'trading_asset_id' => $asset->id,
            'price'            => $newPrice,
            'previous_price'   => $currentPrice,
            'direction'        => $direction,
            'tick_time'        => $tickTime ?? now(),
        ]);

        $asset->update(['current_price' => $newPrice]);

        return $tick;
    }

    public function generateTicksForAsset(TradingAsset $asset, int $count = 1): array
    {
        $ticks = [];
        if ($count === 1) {
            $ticks[] = $this->generateNextTick($asset);
            return $ticks;
        }

        // Backfill: space ticks 3 seconds apart ending at now
        $startOffset = ($count - 1) * 3;
        for ($i = 0; $i < $count; $i++) {
            $tickTime    = now()->subSeconds($startOffset - ($i * 3));
            $ticks[]     = $this->generateNextTick($asset, $tickTime);
        }
        return $ticks;
    }

    // ─── Trade Placement ──────────────────────────────────────────────────────

    public function placeTrade(
        User         $user,
        TradingAsset $asset,
        string       $direction,
        float|string $amount,
        int          $expirySeconds,
        string       $walletType
    ): Trade {
        if (! in_array($expirySeconds, self::ALLOWED_EXPIRY, true)) {
            throw new RuntimeException('Invalid expiry. Allowed: 30, 60, 120, 300 seconds.');
        }
        if (! in_array($direction, ['buy', 'sell'], true)) {
            throw new RuntimeException('Direction must be buy or sell.');
        }
        if (! $asset->is_active) {
            throw new RuntimeException('This asset is not currently available for trading.');
        }

        return DB::transaction(function () use ($user, $asset, $direction, $amount, $expirySeconds, $walletType) {
            $wallet = $user->wallets()->where('type', $walletType)->firstOrFail();

            $this->walletService->debit(
                $wallet,
                $amount,
                'trade_loss',
                "Trade opened: {$asset->symbol} {$direction} @ {$asset->formatPrice()}"
            );

            $now = now();

            return Trade::create([
                'user_id'          => $user->id,
                'wallet_id'        => $wallet->id,
                'trading_asset_id' => $asset->id,
                'wallet_type'      => $walletType,
                'direction'        => $direction,
                'stake_amount'     => $amount,
                'entry_price'      => $asset->current_price,
                'expiry_seconds'   => $expirySeconds,
                'opened_at'        => $now,
                'expires_at'       => $now->copy()->addSeconds($expirySeconds),
                'status'           => 'open',
            ]);
        });
    }

    // ─── Trade Settlement ─────────────────────────────────────────────────────

    public function settleTrade(Trade $trade): Trade
    {
        return DB::transaction(function () use ($trade) {
            $locked = Trade::where('id', $trade->id)->lockForUpdate()->first();

            if ($locked->status !== 'open') {
                return $locked;
            }

            $asset  = $locked->tradingAsset;
            $config = $this->simConfig->getActiveConfig();

            // 1. Calculate market sentiment across all open trades for this asset.
            //    Returns: [sentiment 0–1, buyVolume, sellVolume]
            //    sentiment > 0.5 = majority buying, < 0.5 = majority selling, 0.5 = balanced/no data
            [$sentiment, , ] = $this->calculateSentiment($asset, $locked->id);

            // 2. Compute sentiment-adjusted win probability.
            //    trend_strength from SimulationSetting controls how aggressively
            //    the engine moves against the majority position.
            $baseWinProb = $config ? (float) $config->win_probability / 100 : 0.5;
            $sensitivity = $config ? (float) $config->trend_strength : 0.5;
            $winProb     = $this->adjustedWinProbability($baseWinProb, $sensitivity, $sentiment, $locked->direction);
            $isWin       = (mt_rand() / mt_getrandmax()) < $winProb;

            // 3. Determine tick direction that is consistent with the outcome,
            //    then generate the settlement tick (saves to price_ticks — all users see it).
            $shouldPriceGoUp = ($isWin && $locked->direction === 'buy')
                || (! $isWin && $locked->direction === 'sell');

            $this->generateNextTick($asset, null, $shouldPriceGoUp ? 'up' : 'down');
            $asset->refresh();

            // 4. Derive a settlement price consistent with the outcome
            $entryPrice   = (float) $locked->entry_price;
            $currentPrice = (float) $asset->current_price;
            $baseVol      = (float) $asset->volatility;
            $volMult      = $config ? (float) $config->volatility_multiplier : 1.0;

            // Small realistic pip movement in the required direction
            $pip = max(
                $currentPrice * 0.000001,
                $currentPrice * ($baseVol * $volMult) * 0.1
            );

            $exitPrice = $shouldPriceGoUp
                ? $entryPrice + $pip
                : $entryPrice - $pip;

            $exitPrice = max(0.00000001, round($exitPrice, $this->pricePrecision($asset)));

            // 5. Calculate displacement and profit
            $stake        = (float) $locked->stake_amount;
            $displacement = $this->calculateDisplacement($entryPrice, $exitPrice);

            $minRate = $config ? (float) $config->min_profit_multiplier : self::DEFAULT_MIN_PROFIT;
            $maxRate = $config ? (float) $config->max_profit_multiplier : self::DEFAULT_MAX_PROFIT;

            $baseProfit = $stake * $displacement * 100;
            $profit     = max($stake * $minRate, min($baseProfit, $stake * $maxRate));

            $status = $isWin ? 'won' : 'lost';

            [$profitLoss, $payout] = match ($status) {
                'won'  => [$profit, $stake + $profit],
                'lost' => [-$stake, 0.0],
            };

            // 6. Persist trade result
            $locked->exit_price   = $exitPrice;
            $locked->displacement = $displacement;
            $locked->profit_loss  = $profitLoss;
            $locked->payout       = $payout;
            $locked->status       = $status;
            $locked->closed_at    = now();
            $locked->save();

            // 7. Update wallet
            $wallet = $locked->wallet;

            if ($status === 'won') {
                $this->walletService->credit(
                    $wallet,
                    (string) $payout,
                    'trade_profit',
                    "Trade won: {$asset->symbol} {$locked->direction} +\$" . number_format($profit, 2),
                    ['trade_id' => $locked->id]
                );
            }
            // Loss: stake already debited on placement — nothing more to do

            // 8. Notify user of trade outcome
            $user = $locked->wallet->user;
            if ($status === 'won') {
                $this->notifier->send($user, 'trade_won', 'Trade Won! 🎉',
                    "Your {$asset->symbol} {$locked->direction} trade won +\$" . number_format($profit, 2) . '.',
                    ['trade_id' => $locked->id, 'profit' => $profit]);
            } else {
                $this->notifier->send($user, 'trade_lost', 'Trade Closed',
                    "Your {$asset->symbol} {$locked->direction} trade closed at a loss of \$" . number_format($stake, 2) . '.',
                    ['trade_id' => $locked->id, 'loss' => $stake]);
            }

            return $locked->fresh();
        });
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    public function getActiveTrades(User $user): Collection
    {
        return $user->trades()
            ->where('status', 'open')
            ->with('tradingAsset')
            ->orderByDesc('opened_at')
            ->get();
    }

    public function getMarketSnapshot(): Collection
    {
        return TradingAsset::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function calculateDisplacement(float $entryPrice, float $exitPrice): float
    {
        if ($entryPrice == 0) {
            return 0.0;
        }
        return abs($exitPrice - $entryPrice) / $entryPrice;
    }

    public function calculateProfitLoss(Trade $trade, float $exitPrice): array
    {
        $entry        = (float) $trade->entry_price;
        $stake        = (float) $trade->stake_amount;
        $displacement = $this->calculateDisplacement($entry, $exitPrice);
        $config       = $this->simConfig->getActiveConfig();

        $priceDiff = $exitPrice - $entry;
        $threshold = $entry * 0.000001;

        if (abs($priceDiff) < $threshold) {
            $status = 'draw';
        } elseif ($trade->direction === 'buy') {
            $status = $priceDiff > 0 ? 'won' : 'lost';
        } else {
            $status = $priceDiff < 0 ? 'won' : 'lost';
        }

        $minRate = $config ? (float) $config->min_profit_multiplier : self::DEFAULT_MIN_PROFIT;
        $maxRate = $config ? (float) $config->max_profit_multiplier : self::DEFAULT_MAX_PROFIT;

        $baseProfit = $stake * $displacement * 100;
        $profit     = max($stake * $minRate, min($baseProfit, $stake * $maxRate));

        return match ($status) {
            'won'  => ['status' => 'won',  'profit_loss' => $profit,  'payout' => $stake + $profit, 'displacement' => $displacement],
            'lost' => ['status' => 'lost', 'profit_loss' => -$stake,  'payout' => 0.0,              'displacement' => $displacement],
            'draw' => ['status' => 'draw', 'profit_loss' => 0.0,      'payout' => $stake,            'displacement' => $displacement],
        };
    }

    // ─── Sentiment ────────────────────────────────────────────────────────────

    /**
     * Returns [sentiment, buyVolume, sellVolume] for all open trades on the asset.
     * sentiment: 0.0 = all sell, 0.5 = balanced / no data, 1.0 = all buy.
     * The settling trade is excluded so it does not count itself.
     */
    private function calculateSentiment(TradingAsset $asset, int $excludeTradeId): array
    {
        $rows = Trade::where('trading_asset_id', $asset->id)
            ->where('status', 'open')
            ->where('id', '!=', $excludeTradeId)
            ->selectRaw('direction, SUM(stake_amount) as volume')
            ->groupBy('direction')
            ->get()
            ->keyBy('direction');

        $buyVolume  = (float) ($rows->get('buy')?->volume  ?? 0);
        $sellVolume = (float) ($rows->get('sell')?->volume ?? 0);
        $total      = $buyVolume + $sellVolume;

        if ($total <= 0) {
            return [0.5, 0.0, 0.0];
        }

        return [$buyVolume / $total, $buyVolume, $sellVolume];
    }

    /**
     * Adjusts the base win probability based on platform sentiment.
     *
     * If this trade is WITH the majority position → reduce win probability.
     * If this trade is AGAINST the majority     → increase win probability.
     *
     * $sensitivity (trend_strength) controls how aggressively sentiment shifts
     * the probability: 0.0 = no effect, 1.0 = maximum house edge.
     *
     * At full sensitivity + full imbalance the adjustment is ±50% of base.
     */
    private function adjustedWinProbability(float $baseProb, float $sensitivity, float $sentiment, string $direction): float
    {
        $imbalance = abs($sentiment - 0.5) * 2; // 0.0 (balanced) → 1.0 (all one side)

        $majorityBuying      = $sentiment > 0.5;
        $tradingWithMajority = ($direction === 'buy' &&   $majorityBuying)
                            || ($direction === 'sell' && ! $majorityBuying);

        $adjustment = $sensitivity * $imbalance * 0.5;

        $winProb = $tradingWithMajority
            ? $baseProb - $adjustment
            : $baseProb + $adjustment;

        return max(0.05, min(0.95, $winProb));
    }

    private function pricePrecision(TradingAsset $asset): int
    {
        return match ($asset->type) {
            'forex'  => 5,
            'crypto' => (float) $asset->base_price >= 1000 ? 2 : 6,
            default  => 2,
        };
    }

    private function randomNormal(): float
    {
        do {
            $u1 = mt_rand(1, PHP_INT_MAX) / PHP_INT_MAX;
            $u2 = mt_rand(1, PHP_INT_MAX) / PHP_INT_MAX;
        } while ($u1 <= PHP_FLOAT_EPSILON);

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }
}
