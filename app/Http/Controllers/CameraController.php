<?php

namespace App\Http\Controllers;

use App\Enums\CameraBrand;
use App\Enums\CameraStatus;
use App\Enums\CameraType;
use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Models\Camera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CameraController extends Controller
{
    /**
     * List cameras with summary stats, search and filters.
     */
    public function index(Request $request): View
    {
        $cameras = Camera::query()
            ->search($request->query('search'))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('brand'), fn ($q, $v) => $q->where('brand', $v))
            ->when($request->query('building'), fn ($q, $v) => $q->where('building', $v))
            ->when($request->query('floor'), fn ($q, $v) => $q->where('floor', $v))
            ->when($request->query('zone'), fn ($q, $v) => $q->where('zone', $v))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('cameras.index', [
            'cameras' => $cameras,
            'stats' => $this->stats(),
            'statuses' => CameraStatus::cases(),
            'types' => CameraType::cases(),
            'brands' => CameraBrand::cases(),
            'buildings' => Camera::distinct()->orderBy('building')->pluck('building'),
            'floors' => Camera::distinct()->orderBy('floor')->pluck('floor'),
            'zones' => Camera::distinct()->orderBy('zone')->pluck('zone'),
        ]);
    }

    /**
     * Show the form for registering a new camera.
     */
    public function create(): View
    {
        $this->authorize('create', Camera::class);

        return view('cameras.create', $this->formOptions());
    }

    /**
     * Store a newly registered camera.
     */
    public function store(StoreCameraRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['recording_enabled'] = $request->boolean('recording_enabled');

        $camera = Camera::create($data);

        return redirect()->route('cameras.index')
            ->with('status', "Camera {$camera->name} ({$camera->camera_id}) has been registered.");
    }

    /**
     * Display a camera's details, health, events and settings.
     */
    public function show(Camera $camera): View
    {
        return view('cameras.show', ['camera' => $camera]);
    }

    /**
     * Show the form for editing a camera.
     */
    public function edit(Camera $camera): View
    {
        $this->authorize('update', $camera);

        return view('cameras.edit', ['camera' => $camera] + $this->formOptions());
    }

    /**
     * Update the given camera.
     */
    public function update(UpdateCameraRequest $request, Camera $camera): RedirectResponse
    {
        $data = $request->validated();
        $data['recording_enabled'] = $request->boolean('recording_enabled');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $camera->update($data);

        return redirect()->route('cameras.index')
            ->with('status', "Camera {$camera->name} has been updated.");
    }

    /**
     * Delete the given camera.
     */
    public function destroy(Camera $camera): RedirectResponse
    {
        $this->authorize('delete', $camera);

        $name = $camera->name;
        $camera->delete();

        return redirect()->route('cameras.index')
            ->with('status', "Camera {$name} has been deleted.");
    }

    /**
     * Summary counts for the stat cards.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'total' => Camera::count(),
            'online' => Camera::where('status', CameraStatus::Online)->count(),
            'offline' => Camera::where('status', CameraStatus::Offline)->count(),
            'recording' => Camera::where('recording_enabled', true)->count(),
            'errors' => Camera::where('status', CameraStatus::Maintenance)->count(),
        ];
    }

    /**
     * Shared select options for the create/edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'statuses' => CameraStatus::cases(),
            'types' => CameraType::cases(),
            'brands' => CameraBrand::cases(),
            'resolutions' => ['1280x720', '1920x1080', '2560x1440', '3840x2160'],
        ];
    }
}
