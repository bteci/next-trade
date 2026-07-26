<?php

namespace App\Http\Controllers;

use App\Models\TradingAsset;
use App\Services\SimulationConfigService;
use App\Services\TradingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MarketController extends Controller
{
    public function __construct(
        private TradingEngineService    $engine,
        private SimulationConfigService $simConfig
    ) {}

    public function snapshot(): JsonResponse
    {
        $config = $this->simConfig->getActiveConfig();

        $assets = TradingAsset::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (TradingAsset $a) => [
                'id'           => $a->id,
                'symbol'       => $a->symbol,
                'name'         => $a->name,
                'type'         => $a->type,
                'price'        => (float) $a->current_price,
                'base_price'   => (float) $a->base_price,
                'change_pct'   => (float) $a->price_change,
                'color'        => $a->type_color,
            ]);

        return response()->json([
            'assets'       => $assets,
            'candle_speed' => $config?->candle_speed_seconds ?? 5,
            'difficulty'   => $config?->difficulty ?? 'normal',
        ]);
    }

    public function ticks(Request $request, TradingAsset $asset): JsonResponse
    {
        $since = null;
        if ($request->filled('since')) {
            try {
                // Client echoes back the UTC ISO time we emitted; convert to the
                // app timezone because tick_time is stored as local time.
                $since = Carbon::parse($request->query('since'))->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
                $since = null;
            }
        }

        if ($since === null) {
            // Full load only: prune old ticks, then seed history if what's left
            // is empty (600 ticks ≈ 30 min backdated) — pruning first means a
            // user returning after 2+ idle hours still gets a full chart.
            // Incremental polls skip this work.
            $asset->priceTicks()
                ->where('tick_time', '<', now()->subHours(2))
                ->delete();

            if ($asset->priceTicks()->count() < 2) {
                $this->engine->generateTicksForAsset($asset, 600);
                $asset->refresh();
            }
        }

        // Generate a new live tick if the latest is more than 2 s old
        $latest = $asset->priceTicks()->latest('tick_time')->first();
        if (! $latest || $latest->tick_time->lt(now()->subSeconds(2))) {
            $this->engine->generateNextTick($asset);
        }

        $format = fn ($t) => [
            'price'     => (float) $t->price,
            'direction' => $t->direction,
            'time'      => $t->tick_time->toISOString(),
        ];

        // Incremental poll: only ticks newer than `since` — tiny payload
        if ($since !== null) {
            $ticks = $asset->priceTicks()
                ->where('tick_time', '>', $since)
                ->orderBy('tick_time')
                ->limit(500)
                ->get()
                ->map($format);

            return response()->json($ticks);
        }

        // Full load: the most recent ticks in ascending order for the chart
        $limit = min(max((int) $request->query('limit', 2400), 1), 2400);

        $ticks = $asset->priceTicks()
            ->orderByDesc('tick_time')
            ->limit($limit)
            ->get()
            ->sortBy('tick_time')
            ->values()
            ->map($format);

        return response()->json($ticks);
    }
}
