<?php

namespace App\Http\Controllers;

use App\Enums\AccessLevel;
use App\Http\Requests\StoreAccessPermissionRequest;
use App\Models\AccessPermission;
use App\Models\Door;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessPermissionController extends Controller
{
    /**
     * Show the form for adding an access permission.
     */
    public function create(): View
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        return view('access.permissions.create', $this->formOptions());
    }

    /**
     * Store a new employee access permission.
     */
    public function store(StoreAccessPermissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $doors = $data['doors'];
        unset($data['doors']);

        $permission = AccessPermission::create($data + ['type' => 'permanent']);
        $permission->doors()->sync($doors);

        return redirect()->route('access.index')
            ->with('status', "Access permission (badge {$permission->badge_id}) created for {$permission->holderName()}.");
    }

    /**
     * Show the form for editing an access permission.
     */
    public function edit(AccessPermission $permission): View
    {
        abort_unless(auth()->user()->role->canManageAccess(), 403);

        return view('access.permissions.edit', ['permission' => $permission->load('doors')] + $this->formOptions());
    }

    /**
     * Update the given permission.
     */
    public function update(StoreAccessPermissionRequest $request, AccessPermission $permission): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        $doors = $data['doors'];
        unset($data['doors']);

        $permission->update($data);
        $permission->doors()->sync($doors);

        return redirect()->route('access.index')
            ->with('status', "Access permission for {$permission->holderName()} has been updated.");
    }

    /**
     * Delete the given permission. Administrators only.
     */
    public function destroy(AccessPermission $permission): RedirectResponse
    {
        abort_unless(auth()->user()->role->canAdministerAccess(), 403);

        $label = "{$permission->holderName()} (badge {$permission->badge_id})";
        $permission->delete();

        return redirect()->route('access.index')
            ->with('status', "Access permission {$label} has been deleted.");
    }

    /**
     * Grant a temporary visitor access pass.
     */
    public function storeTemporary(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->role->canGrantTemporaryAccess(), 403);

        $data = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'host_user_id' => ['required', 'exists:users,id'],
            'door_id' => ['required', 'exists:doors,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $permission = AccessPermission::create([
            'visitor_name' => $data['visitor_name'],
            'company' => $data['company'] ?? null,
            'host_user_id' => $data['host_user_id'],
            'badge_id' => 'TMP-'.strtoupper(Str::random(5)),
            'access_level' => AccessLevel::Reception,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'valid_from' => today(),
            'valid_until' => $data['valid_until'],
            'active' => true,
            'type' => 'temporary',
        ]);

        $permission->doors()->sync([(int) $data['door_id']]);

        return back()->with('status', "Temporary access granted to {$permission->visitor_name} — badge {$permission->badge_id}, expires {$permission->valid_until->format('M j')}.");
    }

    /**
     * Shared select options for the permission forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'employees' => User::orderBy('first_name')->get(),
            'doors' => Door::orderBy('building')->orderBy('name')->get(),
            'levels' => AccessLevel::cases(),
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'departments' => collect(\App\Support\Departments::ALL),
        ];
    }
}
