<?php

namespace App\Http\Controllers;

use App\Models\PaymentDeposit;
use App\Services\CurrencyService;
use App\Services\DepositService;
use App\Services\UserActivityLogService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DepositController extends Controller
{
    public function __construct(
        private DepositService         $depositService,
        private CurrencyService        $currency,
        private WalletService          $walletService,
        private UserActivityLogService $activityLog
    ) {}

    public function show(Request $request, PaymentDeposit $deposit): View|RedirectResponse
    {
        if ($deposit->user_id !== auth()->id()) {
            abort(403);
        }

        $deposit->load(['wallet', 'reviewer']);

        return view('deposits.show', [
            'deposit'    => $deposit,
            'walletMode' => $deposit->wallet_type,
        ]);
    }

    public function initiateMpesa(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isKenya()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'M-Pesa deposits are currently available for Kenya accounts only.'], 422);
            }
            return back()->with('error', 'M-Pesa deposits are currently available for Kenya accounts only.');
        }

        $validated = $request->validate([
            'kes_amount'  => 'required|numeric|min:10|max:250000',
            'phone'       => ['required', 'string', 'regex:/^(\+?254|0)(7|1)\d{8}$/'],
            'wallet_type' => 'required|in:demo,live',
        ], [
            'phone.regex'       => 'Please enter a valid Kenyan phone number (e.g. 0712345678).',
            'kes_amount.min'    => 'Minimum deposit amount is KES 10.',
            'kes_amount.max'    => 'Maximum deposit amount is KES 250,000.',
        ]);

        try {
            $deposit = $this->depositService->initiateMpesaDeposit(
                $user,
                (float) $validated['kes_amount'],
                $validated['phone'],
                $validated['wallet_type']
            );

            $this->activityLog->log($user, 'deposit_initiated',
                'M-Pesa deposit of KES ' . number_format((float) $validated['kes_amount'], 2),
                ['deposit_id' => $deposit->id, 'wallet' => $validated['wallet_type']]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'STK push sent. Complete payment on your phone.',
                    'redirect' => route('deposits.show', $deposit),
                ]);
            }

            return redirect()
                ->route('deposits.show', $deposit)
                ->with('success', 'STK push sent. Complete payment on your phone.');
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function storeUsdtDeposit(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1|max:100000',
            'txid'        => ['required', 'string', 'min:10', 'max:255', 'unique:payment_deposits,txid'],
            'proof'       => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'wallet_type' => 'required|in:demo,live',
        ], [
            'txid.unique'        => 'This TXID has already been submitted. If you believe this is an error, contact support.',
            'proof.image'        => 'Proof must be an image file.',
            'proof.mimes'        => 'Accepted formats: JPG, JPEG, PNG, WEBP.',
            'proof.max'          => 'Screenshot must be under 4MB.',
            'amount.min'         => 'Minimum deposit is 1 USDT.',
        ]);

        try {
            $deposit = $this->depositService->createUsdtManualDeposit(
                $user,
                (float) $validated['amount'],
                $validated['txid'],
                $request->file('proof'),
                $validated['wallet_type']
            );

            $this->activityLog->log($user, 'deposit_submitted',
                'USDT deposit of $' . number_format((float) $validated['amount'], 2) . ' submitted for review',
                ['deposit_id' => $deposit->id, 'wallet' => $validated['wallet_type']]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'USDT deposit submitted for admin review. You will be notified once it is approved.',
                    'redirect' => route('deposits.show', $deposit),
                ]);
            }

            return redirect()
                ->route('deposits.show', $deposit)
                ->with('success', 'USDT deposit submitted for admin review. You will be notified once it is approved.');
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function refreshStatus(Request $request, PaymentDeposit $deposit): RedirectResponse
    {
        if ($deposit->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $deposit = $this->depositService->refreshMpesaDepositStatus(auth()->user(), $deposit);

            $message = match ($deposit->status) {
                'successful' => 'Payment confirmed! Your wallet has been credited.',
                'failed'     => 'Payment failed: ' . ($deposit->result_description ?? 'Transaction was not completed.'),
                'cancelled'  => 'Payment was cancelled.',
                default      => 'Payment is still pending. Please wait and try again.',
            };

            $type = $deposit->isSuccessful() ? 'success' : ($deposit->status === 'pending' ? 'info' : 'error');

            return redirect()->route('deposits.show', $deposit)->with($type, $message);
        } catch (RuntimeException $e) {
            return redirect()->route('deposits.show', $deposit)->with('error', $e->getMessage());
        }
    }
}
