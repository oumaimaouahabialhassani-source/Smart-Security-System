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
            'roles' => UserRole::cases(),
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
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Update the given user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Never demote, suspend or deactivate the last administrator
        // still able to sign in — the system would become unmanageable.
        if (\App\Policies\UserPolicy::isLastActiveAdministrator($user)
            && ($data['role'] !== \App\Enums\UserRole::Administrator->value || $data['status'] !== \App\Enums\UserStatus::Active->value)) {
            return redirect()->route('users.edit', $user)
                ->with('error', "{$user->name} is the last active administrator — their role and status cannot be changed.");
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

        if (\App\Policies\UserPolicy::isLastActiveAdministrator($user)) {
            return redirect()->route('users.index')
                ->with('error', "{$user->name} is the last active administrator and cannot be deleted.");
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('status', "User {$name} has been deleted.");
    }
}
