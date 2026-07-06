{{-- Shared camera form: $camera is null on create, a Camera model on edit. --}}
@php($editing = $camera !== null)

<form method="POST"
      action="{{ $editing ? route('cameras.update', $camera) : route('cameras.store') }}"
      class="panel form-panel form-panel-wide"
      data-loading>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <h2 class="form-section-title">Identification</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="name">Camera Name <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $camera?->name) }}"
                   placeholder="e.g. Entrance-01" required maxlength="100"
                   @class(['is-invalid' => $errors->has('name')])>
            @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="camera_id">Camera ID <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="camera_id" name="camera_id" value="{{ old('camera_id', $camera?->camera_id) }}"
                   placeholder="e.g. CAM-014" required maxlength="50"
                   @class(['is-invalid' => $errors->has('camera_id')])>
            @error('camera_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="brand">Brand <span class="req" aria-hidden="true">*</span></label>
            <select id="brand" name="brand" required @class(['is-invalid' => $errors->has('brand')])>
                <option value="" disabled @selected(old('brand', $camera?->brand?->value) === null)>Select a brand…</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->value }}" @selected(old('brand', $camera?->brand?->value) === $brand->value)>{{ $brand->label() }}</option>
                @endforeach
            </select>
            @error('brand') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="model">Model <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="model" name="model" value="{{ old('model', $camera?->model) }}"
                   placeholder="e.g. DS-2CD2143-I" required maxlength="100"
                   @class(['is-invalid' => $errors->has('model')])>
            @error('model') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="type">Camera Type <span class="req" aria-hidden="true">*</span></label>
            <select id="type" name="type" required @class(['is-invalid' => $errors->has('type')])>
                <option value="" disabled @selected(old('type', $camera?->type?->value) === null)>Select a type…</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $camera?->type?->value) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('type') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="status">Status <span class="req" aria-hidden="true">*</span></label>
            <select id="status" name="status" required @class(['is-invalid' => $errors->has('status')])>
                <option value="" disabled @selected(old('status', $camera?->status?->value) === null)>Select a status…</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $camera?->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <h2 class="form-section-title">Network & Stream</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="ip_address">IP Address <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address', $camera?->ip_address) }}"
                   placeholder="e.g. 192.168.1.20" required
                   @class(['is-invalid' => $errors->has('ip_address')])>
            @error('ip_address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="mac_address">MAC Address <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="mac_address" name="mac_address" value="{{ old('mac_address', $camera?->mac_address) }}"
                   placeholder="e.g. A4:5E:60:B2:1C:9F" required maxlength="17"
                   @class(['is-invalid' => $errors->has('mac_address')])>
            @error('mac_address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="username">Username <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="username" name="username" value="{{ old('username', $camera?->username) }}"
                   placeholder="Camera login user" required maxlength="100" autocomplete="off"
                   @class(['is-invalid' => $errors->has('username')])>
            @error('username') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="password">
                Password
                @if ($editing)
                    <span class="label-hint">(leave blank to keep current)</span>
                @else
                    <span class="req" aria-hidden="true">*</span>
                @endif
            </label>
            <div class="password-field">
                <input type="password" id="password" name="password"
                       placeholder="Camera login password" autocomplete="new-password"
                       @required(! $editing)
                       @class(['is-invalid' => $errors->has('password')])>
                <x-password-toggle target="password" />
            </div>
            @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field form-field-full">
            <label for="rtsp_url">RTSP Stream URL <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="rtsp_url" name="rtsp_url" value="{{ old('rtsp_url', $camera?->rtsp_url) }}"
                   placeholder="rtsp://192.168.1.20:554/Streaming/Channels/101" required maxlength="255"
                   @class(['is-invalid' => $errors->has('rtsp_url')])>
            @error('rtsp_url') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <h2 class="form-section-title">Placement</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="building">Building <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="building" name="building" value="{{ old('building', $camera?->building) }}"
                   placeholder="e.g. HQ Building A" required maxlength="100"
                   @class(['is-invalid' => $errors->has('building')])>
            @error('building') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="floor">Floor <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="floor" name="floor" value="{{ old('floor', $camera?->floor) }}"
                   placeholder="e.g. Ground Floor" required maxlength="50"
                   @class(['is-invalid' => $errors->has('floor')])>
            @error('floor') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="zone">Zone <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="zone" name="zone" value="{{ old('zone', $camera?->zone) }}"
                   placeholder="e.g. Zone North" required maxlength="100"
                   @class(['is-invalid' => $errors->has('zone')])>
            @error('zone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="location">Location <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="location" name="location" value="{{ old('location', $camera?->location) }}"
                   placeholder="e.g. Main entrance, above door" required maxlength="150"
                   @class(['is-invalid' => $errors->has('location')])>
            @error('location') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <h2 class="form-section-title">Video & Recording</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="resolution">Resolution <span class="req" aria-hidden="true">*</span></label>
            <select id="resolution" name="resolution" required @class(['is-invalid' => $errors->has('resolution')])>
                <option value="" disabled @selected(old('resolution', $camera?->resolution) === null)>Select resolution…</option>
                @foreach ($resolutions as $resolution)
                    <option value="{{ $resolution }}" @selected(old('resolution', $camera?->resolution) === $resolution)>{{ $resolution }}</option>
                @endforeach
            </select>
            @error('resolution') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="fps">FPS <span class="req" aria-hidden="true">*</span></label>
            <input type="number" id="fps" name="fps" value="{{ old('fps', $camera?->fps) }}"
                   placeholder="e.g. 25" required min="1" max="120"
                   @class(['is-invalid' => $errors->has('fps')])>
            @error('fps') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field form-field-full">
            <label class="switch-row">
                <input type="hidden" name="recording_enabled" value="0">
                <input type="checkbox" name="recording_enabled" value="1"
                       @checked(old('recording_enabled', $camera?->recording_enabled ?? true))>
                Recording Enabled
                <span class="label-hint">— continuously record this camera's stream</span>
            </label>
        </div>

        <div class="form-field form-field-full">
            <label for="description">Description <span class="label-hint">(optional)</span></label>
            <textarea id="description" name="description" rows="3" maxlength="1000"
                      placeholder="Notes about this camera…"
                      @class(['is-invalid' => $errors->has('description')])>{{ old('description', $camera?->description) }}</textarea>
            @error('description') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('cameras.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save</button>
    </div>
</form>
