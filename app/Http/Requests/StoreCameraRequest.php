<?php

namespace App\Http\Requests;

use App\Enums\CameraBrand;
use App\Enums\CameraStatus;
use App\Enums\CameraType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCameraRequest extends FormRequest
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
            'camera_id' => ['required', 'string', 'max:50', 'unique:cameras,camera_id'],
            'brand' => ['required', Rule::enum(CameraBrand::class)],
            'model' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(CameraType::class)],
            'ip_address' => ['required', 'ip'],
            'mac_address' => ['required', 'mac_address'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
            'rtsp_url' => ['required', 'string', 'max:255', 'regex:/^(rtsp|rtsps|http|https):\/\/.+/i'],
            'building' => ['required', 'string', 'max:100'],
            'floor' => ['required', 'string', 'max:50'],
            'zone' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:150'],
            'resolution' => ['required', 'string', 'max:20'],
            'fps' => ['required', 'integer', 'min:1', 'max:120'],
            'recording_enabled' => ['nullable', 'boolean'],
            'status' => ['required', Rule::enum(CameraStatus::class)],
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
            'camera_id.unique' => 'A camera with this ID already exists.',
            'ip_address.ip' => 'Enter a valid IP address (e.g. 192.168.1.20).',
            'mac_address.mac_address' => 'Enter a valid MAC address (e.g. A4:5E:60:B2:1C:9F).',
            'rtsp_url.regex' => 'The stream URL must start with rtsp:// (or http(s):// for HTTP streams).',
        ];
    }
}
