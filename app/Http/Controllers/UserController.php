<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Notifications\WelcomeInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List users with search, role and status filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['creator', 'updater'])
            ->search($request->query('search'))
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Store a newly created user and email them an invitation
     * to choose their own password.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['created_by'] = $request->user()->id;

        // Every new account starts as a read-only Viewer, no matter
        // what the frontend submitted. Promotion is a separate,
        // Super Admin-only action.
        $data['role'] = UserRole::Viewer;

        $user = User::create($data);

        $user->notify(new WelcomeInvitation(Password::createToken($user)));

        return redirect()->route('users.index')
            ->with('status', "User {$user->name} has been created. An invitation to set a password was sent to {$user->email}.");
    }

    /**
     * Display a user's profile, activity and permissions tabs.
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('users.show', ['user' => $user]);
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Roles never change through this form (see role() below).
        unset($data['role']);

        // Never suspend or deactivate the last Super Admin still able
        // to sign in — the system would become unmanageable.
        if (\App\Policies\UserPolicy::isLastActiveAdministrator($user)
            && $data['status'] !== \App\Enums\UserStatus::Active->value) {
            return redirect()->route('users.edit', $user)
                ->with('error', "{$user->name} is the last active Super Admin — their status cannot be changed.");
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $data['updated_by'] = $request->user()->id;

        $user->update($data);

        return redirect()->route('users.index')
            ->with('status', "User {$user->name} has been updated.");
    }

    /**
     * Delete the given user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role->canManageUsers(), 403);

        if ($user->is($request->user())) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account while signed in.');
        }

        // The last Super Admin able to sign in can never be deleted.
        if (\App\Policies\UserPolicy::isLastActiveAdministrator($user)) {
            return redirect()->route('users.index')
                ->with('error', "{$user->name} is the last active Super Admin and cannot be deleted.");
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('status', "User {$name} has been deleted.");
    }

    /**
     * Promote to Super Admin / demote to Viewer, from the Users
     * table. Super Admin only.
     */
    public function role(Request $request, User $user): RedirectResponse
    {
        $this->authorize('changeRole', $user);

        // Non-bypassable guard: even the Super Admin (who passes
        // Gate::before) can never change their own role.
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $data = $request->validate([
            'role' => ['required', \Illuminate\Validation\Rule::in(array_column($request->user()->role->assignableRoles(), 'value'))],
        ], [
            'role.in' => 'You are not allowed to assign this role.',
        ]);

        $newRole = UserRole::from($data['role']);

        // Demoting the last Super Admin would lock everyone out.
        if (\App\Policies\UserPolicy::isLastActiveAdministrator($user) && ! $newRole->isAdmin()) {
            return back()->with('error', "{$user->name} is the last active Super Admin — they cannot be demoted.");
        }

        $previousRole = $user->role;
        $user->forceFill(['role' => $newRole, 'updated_by' => $request->user()->id])->save();

        \App\Models\AuditLog::record('Users', 'Role Changed', "{$user->name}: {$previousRole->label()} → {$newRole->label()}");

        return back()->with('status', "{$user->name} is now {$newRole->label()}.");
    }
}
