<?php

namespace App\Http\Requests;

use App\Enums\AccessLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role->canManageAccess();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'badge_id' => ['required', 'string', 'max:30'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'access_level' => ['required', Rule::enum(AccessLevel::class)],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'doors' => ['required', 'array', 'min:1'],
            'doors.*' => ['exists:doors,id'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => [Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
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
            'doors.required' => 'Select at least one door.',
            'end_time.after' => 'The end time must be after the start time.',
            'valid_until.after_or_equal' => 'The expiry date must be on or after the start date.',
        ];
    }
}
