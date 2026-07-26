<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBan;
use App\Services\AdminLogService;
use App\Services\NotificationService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        private AdminLogService     $logger,
        private PermissionService   $permissions,
        private NotificationService $notifier
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()->with(['wallets', 'adminRoles'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name',     'like', "%{$s}%")
                ->orWhere('email',  'like', "%{$s}%")
                ->orWhere('username','like',"%{$s}%")
                ->orWhere('phone',  'like', "%{$s}%")
            );
        }
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'banned' => $query->where('is_banned', true),
                'admin'  => $query->where('is_admin', true),
                default  => $query->where('is_banned', false),
            };
        }

        $users    = $query->paginate(20)->withQueryString();
        $total    = User::count();
        $admins   = User::where('is_admin', true)->count();
        $banned   = User::where('is_banned', true)->count();
        $countries = User::whereNotNull('country')->distinct()->orderBy('country')->pluck('country');

        return view('admin.users.index', compact('users', 'total', 'admins', 'banned', 'countries'));
    }

    public function show(User $user): View
    {
        $user->load([
            'wallets', 'adminRoles.permissions', 'bans.bannedBy',
            'paymentDeposits' => fn($q) => $q->latest()->limit(5),
            'withdrawals'     => fn($q) => $q->latest()->limit(5),
            'trades'          => fn($q) => $q->with('tradingAsset')->latest()->limit(5),
            'botInvestments'  => fn($q) => $q->latest()->limit(5),
        ]);

        $recentActivity = \App\Models\AdminLog::where('target_type', User::class)
            ->where('target_id', $user->id)
            ->with('admin')
            ->latest()
            ->limit(10)
            ->get();

        $allRoles = Role::orderBy('name')->get();

        return view('admin.users.show', compact('user', 'recentActivity', 'allRoles'));
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot ban yourself.');
        }

        $request->validate([
            'reason'   => 'nullable|string|max:1000',
            'ends_at'  => 'nullable|date|after:now',
        ]);

        $user->bans()->where('is_active', true)->update(['is_active' => false]);

        UserBan::create([
            'user_id'   => $user->id,
            'banned_by' => auth()->id(),
            'reason'    => $request->reason,
            'starts_at' => now(),
            'ends_at'   => $request->ends_at,
            'is_active' => true,
        ]);

        $user->update(['is_banned' => true]);

        $this->logger->log(
            auth()->user(), 'user_banned',
            User::class, $user->id,
            ['is_banned' => false],
            ['is_banned' => true, 'reason' => $request->reason]
        );

        $this->notifier->send($user, 'account_banned', 'Account Suspended',
            'Your account has been suspended. ' . ($request->reason ? 'Reason: ' . $request->reason : 'Contact support for more information.'));

        return back()->with('success', "{$user->name} has been banned.");
    }

    public function unban(User $user): RedirectResponse
    {
        $user->bans()->where('is_active', true)->update(['is_active' => false]);
        $user->update(['is_banned' => false]);

        $this->logger->log(
            auth()->user(), 'user_unbanned',
            User::class, $user->id,
            ['is_banned' => true],
            ['is_banned' => false]
        );

        return back()->with('success', "{$user->name} has been unbanned.");
    }

    public function makeAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('error', "{$user->name} is already an admin.");
        }

        $user->update(['is_admin' => true]);

        $role = Role::where('slug', $request->input('role', 'support-admin'))->first();
        if ($role) {
            $this->permissions->assignRole($user, $role, auth()->user());
        }

        $this->logger->log(
            auth()->user(), 'user_promoted_to_admin',
            User::class, $user->id,
            ['is_admin' => false],
            ['is_admin' => true, 'role' => $role?->slug]
        );

        return back()->with('success', "{$user->name} has been promoted to admin.");
    }

    public function removeAdmin(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot demote yourself.');
        }

        if ($user->isSuperAdmin() && User::where('is_admin', true)->whereHas('adminRoles', fn($q) => $q->where('slug','super-admin'))->count() <= 1) {
            return back()->with('error', 'Cannot demote the only Super Admin.');
        }

        $user->adminRoles()->detach();
        $user->update(['is_admin' => false]);

        $this->logger->log(
            auth()->user(), 'user_demoted_from_admin',
            User::class, $user->id,
            ['is_admin' => true],
            ['is_admin' => false]
        );

        return back()->with('success', "{$user->name} has been demoted from admin.");
    }

    public function freezeWallets(User $user): RedirectResponse
    {
        $user->wallets()->update(['status' => 'frozen']);

        $this->logger->log(
            auth()->user(), 'wallets_frozen',
            User::class, $user->id,
            [], ['wallet_status' => 'frozen']
        );

        $this->notifier->send($user, 'wallet_frozen', 'Wallet Frozen',
            'Your wallets have been frozen by an administrator. Trading and withdrawals are suspended until further notice.');

        return back()->with('success', "All wallets for {$user->name} have been frozen.");
    }

    public function unfreezeWallets(User $user): RedirectResponse
    {
        $user->wallets()->update(['status' => 'active']);

        $this->logger->log(
            auth()->user(), 'wallets_unfrozen',
            User::class, $user->id,
            [], ['wallet_status' => 'active']
        );

        return back()->with('success', "All wallets for {$user->name} have been unfrozen.");
    }
}
