<?php

namespace App\Http\Requests;

use App\Enums\BiometricStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBiometricProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role->canManageBiometrics();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'assigned_device_id' => ['nullable', 'exists:devices,id'],
            'status' => ['required', Rule::enum(BiometricStatus::class)],
            'security_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
