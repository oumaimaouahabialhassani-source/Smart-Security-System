<?php

namespace App\Http\Requests;

use App\Enums\DeviceProtocol;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\SignalStrength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'device_id' => [
                'required', 'string', 'max:50',
                Rule::unique('devices', 'device_id')->ignore($this->route('device')),
            ],
            'type' => ['required', Rule::enum(DeviceType::class)],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:100'],
            'protocol' => ['required', Rule::enum(DeviceProtocol::class)],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['required', 'mac_address'],
            'serial_number' => ['required', 'string', 'max:100'],
            'firmware_version' => ['required', 'string', 'max:40'],
            'username' => ['required', 'string', 'max:100'],
            // Optional on edit — leave blank to keep the current password.
            'password' => ['nullable', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:100'],
            'floor' => ['required', 'string', 'max:50'],
            'zone' => ['required', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:100'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'signal_strength' => ['required', Rule::enum(SignalStrength::class)],
            'status' => ['required', Rule::enum(DeviceStatus::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_id.unique' => 'A device with this ID already exists.',
            'ip_address.ip' => 'Enter a valid IPv4 or IPv6 address, or leave it empty.',
            'mac_address.mac_address' => 'Enter a valid MAC address (e.g. A4:5E:60:B2:1C:9F).',
            'battery_level.max' => 'Battery level is a percentage (0–100). Leave empty for mains-powered devices.',
        ];
    }
}
