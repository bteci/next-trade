import { CandleBuilder } from './candle-builder';
import type {
    IEngineAdapter,
    OHLCCandle,
    PlaceTradeParams,
    PriceTick,
    TickCallback,
    Timeframe,
    Trade,
    TradeCallback,
} from './chart-types';

/**
 * Adapter between the React chart layer and the existing PHP trading engine.
 *
 * - Polls /trade/assets/{id}/ticks every 3 s (matching the tick generator)
 * - Polls /trade/active every 5 s to detect settlements
 * - Exposes subscribeToTicks / subscribeToTrades for component-level subscriptions
 * - placeTrade calls the existing POST /trade/place endpoint
 */
class NextTradeEngineAdapter implements IEngineAdapter {
    private readonly tickSubs  = new Set<TickCallback>();
    private readonly tradeSubs = new Set<TradeCallback>();

    private active:        Trade[] = [];
    private completed:     Trade[] = [];
    private walletBalance: number  = 0;

    private assetId:       number | null = null;
    private lastTickTime:  string | null = null;

    private tickTimer:  ReturnType<typeof setInterval> | null = null;
    private tradeTimer: ReturnType<typeof setInterval> | null = null;

    // ─── Bootstrap ───────────────────────────────────────────────────────────

    setInitialTrades(active: Trade[], completed: Trade[], walletBalance: number): void {
        this.active        = active;
        this.completed     = completed;
        this.walletBalance = walletBalance;
    }

    // ─── Asset selection ─────────────────────────────────────────────────────

    setAsset(assetId: number): void {
        this.assetId      = assetId;
        this.lastTickTime = null;
        this.startTickPolling();
    }

    // ─── Historical data ──────────────────────────────────────────────────────

    /**
     * JSON GET that always identifies itself as an API call (so the server never
     * mistakes it for browser navigation) and sends the user to login when the
     * session has expired instead of silently polling forever.
     */
    private async getJson<T>(url: string): Promise<T | null> {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        if (res.status === 401 || res.status === 419) {
            this.destroy();
            window.location.href = '/login';
            return null;
        }
        if (!res.ok) return null;
        return res.json() as Promise<T>;
    }

    async getHistoricalTicks(assetId: number): Promise<PriceTick[]> {
        try {
            const raw = await this.getJson<Array<{ price: number | string; time: string; direction?: string }>>(
                `/trade/assets/${assetId}/ticks`
            );
            if (!Array.isArray(raw)) return [];
            return raw.map(t => ({
                price:     Number(t.price),
                time:      t.time,
                direction: (t.direction as PriceTick['direction']) ?? 'flat',
            }));
        } catch {
            return [];
        }
    }

    async getHistoricalCandles(assetId: number, timeframe: Timeframe): Promise<OHLCCandle[]> {
        const ticks = await this.getHistoricalTicks(assetId);
        const builder = new CandleBuilder(timeframe);
        return builder.loadHistoricalTicks(ticks);
    }

    // ─── Subscriptions ────────────────────────────────────────────────────────

    subscribeToTicks(cb: TickCallback): () => void {
        this.tickSubs.add(cb);
        return () => this.tickSubs.delete(cb);
    }

    subscribeToTrades(cb: TradeCallback): () => void {
        this.tradeSubs.add(cb);
        return () => this.tradeSubs.delete(cb);
    }

    getActiveTrades():    Trade[] { return [...this.active]; }
    getCompletedTrades(): Trade[] { return [...this.completed]; }

    // ─── Trade placement ──────────────────────────────────────────────────────

    async placeTrade(params: PlaceTradeParams): Promise<{ trade: Trade; walletBalance: number }> {
        const res = await fetch('/trade/place', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  this.csrfToken(),
                'Accept':        'application/json',
            },
            body: JSON.stringify({
                asset_id:       params.assetId,
                direction:      params.direction,
                amount:         params.stake,
                expiry_seconds: params.expirySeconds,
            }),
        });

        const data = await res.json();
        if (!data.success) throw new Error(data.message ?? 'Trade placement failed');

        // Optimistically update local state and balance
        this.active        = [...this.active, data.trade as Trade];
        this.walletBalance = data.wallet_balance as number;
        this.notifyTrades();

        return { trade: data.trade as Trade, walletBalance: this.walletBalance };
    }

    // ─── Trade polling ────────────────────────────────────────────────────────

    startTradePolling(): void {
        if (this.tradeTimer) clearInterval(this.tradeTimer);
        const poll = () => this.pollTrades();
        poll();
        this.tradeTimer = setInterval(poll, 5000);
    }

    stopTradePolling(): void {
        if (this.tradeTimer) { clearInterval(this.tradeTimer); this.tradeTimer = null; }
    }

    private async pollTrades(): Promise<void> {
        try {
            const data = await this.getJson<{ trades?: Trade[]; wallet_balance?: number }>('/trade/active');
            if (!data) return;

            const prevIds  = new Set(this.active.map(t => t.id));
            this.active    = (data.trades ?? []) as Trade[];
            const newIds   = new Set(this.active.map(t => t.id));
            const settled  = [...prevIds].filter(id => !newIds.has(id));

            // Always capture the latest balance from the server
            if (typeof data.wallet_balance === 'number') {
                this.walletBalance = data.wallet_balance;
            }

            if (settled.length > 0) {
                try {
                    const recent   = await this.getJson<Trade[]>('/trade/recent');
                    this.completed = Array.isArray(recent) ? recent : [];
                } catch { /* ignore */ }
            }

            this.notifyTrades();
        } catch { /* ignore */ }
    }

    // ─── Tick polling ─────────────────────────────────────────────────────────

    private startTickPolling(): void {
        if (this.tickTimer) clearInterval(this.tickTimer);
        // Fire immediately so the chart reflects the latest price on asset switch
        setTimeout(() => this.pollTicks(), 0);
        this.tickTimer = setInterval(() => this.pollTicks(), 3000);
    }

    private async pollTicks(): Promise<void> {
        if (this.assetId === null) return;
        try {
            // Incremental fetch: only ticks newer than the last one we processed.
            // First poll after an asset switch just needs the latest tick.
            const url = this.lastTickTime
                ? `/trade/assets/${this.assetId}/ticks?since=${encodeURIComponent(this.lastTickTime)}`
                : `/trade/assets/${this.assetId}/ticks?limit=1`;
            const raw = await this.getJson<Array<{ price: number | string; time: string; direction?: string }>>(url);
            if (!Array.isArray(raw) || raw.length === 0) return;

            // Server already filters by `since`; keep the client filter as a guard
            const newTicks = this.lastTickTime
                ? raw.filter(t => t.time > this.lastTickTime!)
                : raw.slice(-1);  // first poll: emit only the most recent tick

            for (const t of newTicks) {
                this.lastTickTime = t.time;
                const tick: PriceTick = {
                    price:     Number(t.price),
                    time:      t.time,
                    direction: (t.direction as PriceTick['direction']) ?? 'flat',
                };
                this.tickSubs.forEach(fn => fn(tick));
            }
        } catch { /* ignore */ }
    }

    // ─── Cleanup ──────────────────────────────────────────────────────────────

    destroy(): void {
        if (this.tickTimer)  { clearInterval(this.tickTimer);  this.tickTimer  = null; }
        if (this.tradeTimer) { clearInterval(this.tradeTimer); this.tradeTimer = null; }
        this.tickSubs.clear();
        this.tradeSubs.clear();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private csrfToken(): string {
        return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    }

    private notifyTrades(): void {
        this.tradeSubs.forEach(fn => fn([...this.active], [...this.completed], this.walletBalance));
        // Keep static Blade-rendered balance elements in sync
        window.dispatchEvent(new CustomEvent('nt:balance', { detail: { balance: this.walletBalance } }));
    }
}

// Singleton — one adapter per page
export const adapter = new NextTradeEngineAdapter();
