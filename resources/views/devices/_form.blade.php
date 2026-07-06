{{-- Shared device form: $device is null on create, a Device model on edit. --}}
@php($editing = $device !== null)

<form method="POST"
      action="{{ $editing ? route('devices.update', $device) : route('devices.store') }}"
      class="panel form-panel form-panel-wide"
      data-loading>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <h2 class="form-section-title">Identification</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="name">Device Name <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $device?->name) }}"
                   placeholder="e.g. Motion-Lobby-01" required maxlength="100"
                   @class(['is-invalid' => $errors->has('name')])>
            @error('name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="device_id">Device ID <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="device_id" name="device_id" value="{{ old('device_id', $device?->device_id) }}"
                   placeholder="e.g. DEV-0042" required maxlength="50"
                   @class(['is-invalid' => $errors->has('device_id')])>
            @error('device_id') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="type">Device Type <span class="req" aria-hidden="true">*</span></label>
            <select id="type" name="type" required @class(['is-invalid' => $errors->has('type')])>
                <option value="" disabled @selected(old('type', $device?->type?->value) === null)>Select a type…</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $device?->type?->value) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('type') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="brand">Brand <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="brand" name="brand" value="{{ old('brand', $device?->brand) }}"
                   placeholder="e.g. Aqara, Bosch, Ajax…" required maxlength="60"
                   @class(['is-invalid' => $errors->has('brand')])>
            @error('brand') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="model">Model <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="model" name="model" value="{{ old('model', $device?->model) }}"
                   placeholder="e.g. MS-S02" required maxlength="100"
                   @class(['is-invalid' => $errors->has('model')])>
            @error('model') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="serial_number">Serial Number <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $device?->serial_number) }}"
                   placeholder="e.g. SN-83921004" required maxlength="100"
                   @class(['is-invalid' => $errors->has('serial_number')])>
            @error('serial_number') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="firmware_version">Firmware Version <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="firmware_version" name="firmware_version" value="{{ old('firmware_version', $device?->firmware_version) }}"
                   placeholder="e.g. 1.4.02" required maxlength="40"
                   @class(['is-invalid' => $errors->has('firmware_version')])>
            @error('firmware_version') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="status">Status <span class="req" aria-hidden="true">*</span></label>
            <select id="status" name="status" required @class(['is-invalid' => $errors->has('status')])>
                <option value="" disabled @selected(old('status', $device?->status?->value) === null)>Select a status…</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $device?->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <h2 class="form-section-title">Connectivity & Credentials</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="protocol">Protocol <span class="req" aria-hidden="true">*</span></label>
            <select id="protocol" name="protocol" required @class(['is-invalid' => $errors->has('protocol')])>
                <option value="" disabled @selected(old('protocol', $device?->protocol?->value) === null)>Select a protocol…</option>
                @foreach ($protocols as $protocol)
                    <option value="{{ $protocol->value }}" @selected(old('protocol', $device?->protocol?->value) === $protocol->value)>{{ $protocol->label() }}</option>
                @endforeach
            </select>
            @error('protocol') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="ip_address">IP Address <span class="label-hint">(empty for Zigbee/Z-Wave…)</span></label>
            <input type="text" id="ip_address" name="ip_address" value="{{ old('ip_address', $device?->ip_address) }}"
                   placeholder="e.g. 192.168.5.30"
                   @class(['is-invalid' => $errors->has('ip_address')])>
            @error('ip_address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="mac_address">MAC Address <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="mac_address" name="mac_address" value="{{ old('mac_address', $device?->mac_address) }}"
                   placeholder="e.g. A4:5E:60:B2:1C:9F" required maxlength="17"
                   @class(['is-invalid' => $errors->has('mac_address')])>
            @error('mac_address') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="username">Username <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="username" name="username" value="{{ old('username', $device?->username) }}"
                   placeholder="Device login user" required maxlength="100" autocomplete="off"
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
                       placeholder="Device login password" autocomplete="new-password"
                       @required(! $editing)
                       @class(['is-invalid' => $errors->has('password')])>
                <x-password-toggle target="password" />
            </div>
            @error('password') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="signal_strength">Signal Strength <span class="req" aria-hidden="true">*</span></label>
            <select id="signal_strength" name="signal_strength" required @class(['is-invalid' => $errors->has('signal_strength')])>
                <option value="" disabled @selected(old('signal_strength', $device?->signal_strength?->value) === null)>Select signal…</option>
                @foreach ($signals as $signal)
                    <option value="{{ $signal->value }}" @selected(old('signal_strength', $device?->signal_strength?->value) === $signal->value)>{{ $signal->label() }}</option>
                @endforeach
            </select>
            @error('signal_strength') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="battery_level">Battery Level % <span class="label-hint">(empty for mains-powered)</span></label>
            <input type="number" id="battery_level" name="battery_level" value="{{ old('battery_level', $device?->battery_level) }}"
                   placeholder="0–100" min="0" max="100"
                   @class(['is-invalid' => $errors->has('battery_level')])>
            @error('battery_level') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <h2 class="form-section-title">Placement</h2>
    <div class="form-grid">
        <div class="form-field">
            <label for="building">Building <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="building" name="building" value="{{ old('building', $device?->building) }}"
                   placeholder="e.g. HQ Building A" required maxlength="100"
                   @class(['is-invalid' => $errors->has('building')])>
            @error('building') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="floor">Floor <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="floor" name="floor" value="{{ old('floor', $device?->floor) }}"
                   placeholder="e.g. Ground Floor" required maxlength="50"
                   @class(['is-invalid' => $errors->has('floor')])>
            @error('floor') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="zone">Zone <span class="req" aria-hidden="true">*</span></label>
            <input type="text" id="zone" name="zone" value="{{ old('zone', $device?->zone) }}"
                   placeholder="e.g. Zone North" required maxlength="100"
                   @class(['is-invalid' => $errors->has('zone')])>
            @error('zone') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="room">Room <span class="label-hint">(optional)</span></label>
            <input type="text" id="room" name="room" value="{{ old('room', $device?->room) }}"
                   placeholder="e.g. Server Room" maxlength="100"
                   @class(['is-invalid' => $errors->has('room')])>
            @error('room') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>

        <div class="form-field form-field-full">
            <label for="description">Description <span class="label-hint">(optional)</span></label>
            <textarea id="description" name="description" rows="3" maxlength="1000"
                      placeholder="Notes about this device…"
                      @class(['is-invalid' => $errors->has('description')])>{{ old('description', $device?->description) }}</textarea>
            @error('description') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('devices.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save</button>
    </div>
</form>
