<?php

namespace App\Services;

use App\Models\SimulationSetting;
use App\Models\TradingAsset;
use Illuminate\Support\Facades\DB;

class SimulationConfigService
{
    private ?SimulationSetting $cached = null;

    // ─── Active Config ────────────────────────────────────────────────────────

    public function getActiveConfig(): ?SimulationSetting
    {
        if ($this->cached === null) {
            $this->cached = SimulationSetting::where('is_active', true)->first();
        }
        return $this->cached;
    }

    public function setActiveConfig(SimulationSetting $setting): void
    {
        DB::transaction(function () use ($setting) {
            SimulationSetting::query()->update(['is_active' => false]);
            $setting->update(['is_active' => true]);
        });
        $this->cached = $setting->fresh();
    }

    public function invalidateCache(): void
    {
        $this->cached = null;
    }

    // ─── Defaults ─────────────────────────────────────────────────────────────

    /**
     * $overwriteExisting = true restores factory values on existing rows (admin
     * "Reset Defaults"). Pass false when seeding so deploys never clobber
     * admin-tuned values or the currently active difficulty.
     */
    public function createDefaultSettings(bool $overwriteExisting = true): void
    {
        $defaults = self::defaults();
        foreach ($defaults as $data) {
            if ($overwriteExisting) {
                SimulationSetting::updateOrCreate(['difficulty' => $data['difficulty']], $data);
            } else {
                SimulationSetting::firstOrCreate(['difficulty' => $data['difficulty']], $data);
            }
        }
    }

    public static function defaults(): array
    {
        return [
            [
                'name'                  => 'Easy Mode',
                'difficulty'            => 'easy',
                'win_probability'       => 70.00,
                'volatility_multiplier' => 0.8000,
                'trend_strength'        => 0.3000,
                'min_profit_multiplier' => 0.7000,
                'max_profit_multiplier' => 0.9500,
                'max_loss_multiplier'   => 1.0000,
                'candle_speed_seconds'  => 3,
                'is_active'             => false,
            ],
            [
                'name'                  => 'Normal Mode',
                'difficulty'            => 'normal',
                'win_probability'       => 40.00,
                'volatility_multiplier' => 1.0000,
                'trend_strength'        => 0.5000,
                'min_profit_multiplier' => 0.6000,
                'max_profit_multiplier' => 0.8500,
                'max_loss_multiplier'   => 1.0000,
                'candle_speed_seconds'  => 3,
                'is_active'             => true,
            ],
            [
                'name'                  => 'Hard Mode',
                'difficulty'            => 'hard',
                'win_probability'       => 30.00,
                'volatility_multiplier' => 1.3000,
                'trend_strength'        => 0.7000,
                'min_profit_multiplier' => 0.5000,
                'max_profit_multiplier' => 0.7500,
                'max_loss_multiplier'   => 1.0000,
                'candle_speed_seconds'  => 2,
                'is_active'             => false,
            ],
            [
                'name'                  => 'Extreme Mode',
                'difficulty'            => 'extreme',
                'win_probability'       => 5.00,
                'volatility_multiplier' => 1.8000,
                'trend_strength'        => 1.0000,
                'min_profit_multiplier' => 0.4000,
                'max_profit_multiplier' => 0.7000,
                'max_loss_multiplier'   => 1.0000,
                'candle_speed_seconds'  => 1,
                'is_active'             => false,
            ],
        ];
    }

    // ─── Asset Helpers ────────────────────────────────────────────────────────

    public function applyDifficultyToAsset(TradingAsset $asset): float
    {
        return $this->calculateAdjustedVolatility($asset);
    }

    public function calculateAdjustedVolatility(TradingAsset $asset): float
    {
        $config = $this->getActiveConfig();
        return $config
            ? (float) $asset->volatility * (float) $config->volatility_multiplier
            : (float) $asset->volatility;
    }

    // ─── Config Accessors ─────────────────────────────────────────────────────

    public function getWinProbability(): float
    {
        $config = $this->getActiveConfig();
        return $config ? (float) $config->win_probability / 100 : 0.5;
    }

    public function getPayoutLimits(): array
    {
        $config = $this->getActiveConfig();
        return [
            'min' => $config ? (float) $config->min_profit_multiplier : 0.70,
            'max' => $config ? (float) $config->max_profit_multiplier : 0.95,
        ];
    }

    public function getCandleSpeed(): int
    {
        $config = $this->getActiveConfig();
        return $config ? (int) $config->candle_speed_seconds : 3;
    }

    public function getTrendStrength(): float
    {
        $config = $this->getActiveConfig();
        return $config ? (float) $config->trend_strength : 0.5;
    }
}
