<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

use App\Enums\PreferredTopic;
use App\Enums\NotificationType;
use App\Enums\AccountStatus;
use App\Models\PermissionGroup;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->trash === 'trashed', fn($q) => $q->onlyTrashed())
            ->when($request->trash === 'all', fn($q) => $q->withTrashed())
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                $query->whereJsonContains('user_type', $type);
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', fn($q) => $q->where('id', $role));
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'blocked') {
                    $query->where('account_status', 'blocked');
                } else {
                    $query->where('is_active', $status === 'active');
                }
            })
            ->when($request->online, function ($query, $online) {
                $threshold = now()->subMinutes(5);
                if ($online === 'online') {
                    $query->where('last_active_at', '>=', $threshold);
                } else {
                    $query->where(function($q) use ($threshold) {
                        $q->where('last_active_at', '<', $threshold)->orWhereNull('last_active_at');
                    });
                }
            })
            ->with('roles')
            ->latest()
            ->paginate((int) ($request->perPage ?? 10))
            ->withQueryString();

        $onlineThreshold = now()->subMinutes(5);

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'online' => User::where('last_active_at', '>=', $onlineThreshold)->count(),
            'trashed' => User::onlyTrashed()->count(),
        ];

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'online', 'trash', 'perPage', 'type', 'role']),
            'roles' => Role::all(),
            'accountStatuses' => collect(AccountStatus::cases())->map(fn($s) => ['name' => $s->name, 'value' => $s->value])->toArray(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => Role::with('permissions')->get(),
            'permissionGroups' => PermissionGroup::with('permissions')->get(),
            'topics' => collect(PreferredTopic::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'notificationTypes' => collect(NotificationType::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'accountStatuses' => collect(AccountStatus::cases())->map(fn($s) => ['name' => $s->name, 'value' => $s->value])->toArray(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'type' => ['required', 'array'],
            'type.*' => ['string', 'in:admin,writer,user'],
            'roles' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'preferred_locale' => ['nullable', 'string', 'in:ar,en'],
            'preferred_topics' => ['nullable', 'array'],
            'notification_preferences' => ['nullable', 'array'],
            'is_private' => ['nullable', 'boolean'],
            'comments_blocked' => ['nullable', 'boolean'],
            'account_status' => ['nullable', Rule::enum(AccountStatus::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'phone_code' => $validated['phone_code'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'facebook_url' => $validated['facebook_url'] ?? null,
            'instagram_url' => $validated['instagram_url'] ?? null,
            'tiktok_url' => $validated['tiktok_url'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'youtube_url' => $validated['youtube_url'] ?? null,
            'x_url' => $validated['x_url'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'preferred_locale' => $validated['preferred_locale'] ?? 'ar',
            'preferred_topics' => $validated['preferred_topics'] ?? [],
            'notification_preferences' => $validated['notification_preferences'] ?? [],
            'is_private' => $validated['is_private'] ?? false,
            'comments_blocked' => $validated['comments_blocked'] ?? false,
            'account_status' => $validated['account_status'] ?? AccountStatus::Active,
            'user_type' => $validated['type'],
        ]);

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
        }

        if (!empty($validated['roles'])) {
            $user->syncRoles(Role::whereIn('id', $validated['roles'])->get());
        } else {
            $user->syncRoles([]);
        }

        if (in_array('admin', $validated['type']) && !empty($validated['permissions'])) {
            $user->syncPermissions(\Spatie\Permission\Models\Permission::whereIn('name', $validated['permissions'])->where('guard_name', 'admin')->get());
        } else {
            $user->syncPermissions([]);
        }

        return to_route('admin.users.index')->with('toast', [
            'type' => 'success',
            'message' => __('User created successfully.')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user->load(['roles', 'permissions']),
            'roles' => Role::with('permissions')->get(),
            'permissionGroups' => PermissionGroup::with('permissions')->get(),
            'topics' => collect(PreferredTopic::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'notificationTypes' => collect(NotificationType::cases())->map(fn($t) => ['name' => $t->name, 'value' => $t->value])->toArray(),
            'accountStatuses' => collect(AccountStatus::cases())->map(fn($s) => ['name' => $s->name, 'value' => $s->value])->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'type' => ['required', 'array'],
            'type.*' => ['string', 'in:admin,writer,user'],
            'roles' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:20'],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'preferred_locale' => ['nullable', 'string', 'in:ar,en'],
            'preferred_topics' => ['nullable', 'array'],
            'notification_preferences' => ['nullable', 'array'],
            'is_private' => ['nullable', 'boolean'],
            'comments_blocked' => ['nullable', 'boolean'],
            'account_status' => ['nullable', Rule::enum(AccountStatus::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'phone_code' => $validated['phone_code'] ?? $user->phone_code,
            'bio' => $validated['bio'] ?? $user->bio,
            'facebook_url' => $validated['facebook_url'] ?? $user->facebook_url,
            'instagram_url' => $validated['instagram_url'] ?? $user->instagram_url,
            'tiktok_url' => $validated['tiktok_url'] ?? $user->tiktok_url,
            'linkedin_url' => $validated['linkedin_url'] ?? $user->linkedin_url,
            'youtube_url' => $validated['youtube_url'] ?? $user->youtube_url,
            'x_url' => $validated['x_url'] ?? $user->x_url,
            'website_url' => $validated['website_url'] ?? $user->website_url,
            'preferred_locale' => $validated['preferred_locale'] ?? $user->preferred_locale,
            'preferred_topics' => $validated['preferred_topics'] ?? $user->preferred_topics,
            'notification_preferences' => $validated['notification_preferences'] ?? $user->notification_preferences,
            'is_private' => $validated['is_private'] ?? $user->is_private,
            'comments_blocked' => $validated['comments_blocked'] ?? $user->comments_blocked,
            'account_status' => $validated['account_status'] ?? $user->account_status,
            'user_type' => $validated['type'],
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatars');
        }

        if (!empty($validated['roles'])) {
            $user->syncRoles(Role::whereIn('id', $validated['roles'])->get());
        } else {
            $user->syncRoles([]);
        }

        if (in_array('admin', $validated['type']) && !empty($validated['permissions'])) {
            $user->syncPermissions(\Spatie\Permission\Models\Permission::whereIn('name', $validated['permissions'])->where('guard_name', 'admin')->get());
        } else {
            $user->syncPermissions([]);
        }

        return to_route('admin.users.index')->with('toast', [
            'type' => 'success',
            'message' => __('User updated successfully.')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return back()->with('toast', [
            'type' => 'warning',
            'message' => __('User moved to trash.')
        ]);
    }

    /**
     * Display a listing of deleted resources.
     */
    public function trash(): Response
    {
        $users = User::onlyTrashed()->latest()->paginate(10);

        return Inertia::render('admin/users/trash', [
            'users' => $users,
        ]);
    }

    /**
     * Restore the specified resource.
     */
    public function restore(string $id): RedirectResponse
    {
        User::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('User restored successfully.')
        ]);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $id): RedirectResponse
    {
        User::onlyTrashed()->findOrFail($id)->forceDelete();

        return back()->with('toast', [
            'type' => 'error',
            'message' => __('User deleted permanently.')
        ]);
    }

    /**
     * Update user status (Active, Banned, Email Verification).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:active,email_verify,ban'],
            'value' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        switch ($validated['type']) {
            case 'active':
                $user->update(['is_active' => $validated['value']]);
                break;
            case 'email_verify':
                $user->update(['email_verified_at' => $validated['value'] ? now() : null]);
                break;
            case 'ban':
                $user->update([
                    'account_status' => $validated['value'] ? AccountStatus::Banned : AccountStatus::Active,
                    'banned_at' => $validated['value'] ? now() : null,
                    'ban_reason' => $validated['value'] ? $validated['reason'] : null,
                ]);
                break;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('User status updated successfully.')
        ]);
    }
}
