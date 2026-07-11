<?php

namespace App\Http\Requests;

use App\Enums\VisitAccessLevel;
use App\Enums\VisitDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role->canManageVisitors();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal information
            'full_name' => ['required', 'string', 'max:150'],
            'national_id' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],

            // Visit information
            'host_user_id' => ['required', 'exists:users,id'],
            'department' => ['required', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:200'],
            'visit_date' => ['required', 'date'],
            'expected_check_in' => ['nullable', 'date_format:H:i'],
            'expected_duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'companions' => ['nullable', 'integer', 'min:0', 'max:50'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],

            // Security information
            'document_type' => ['required', Rule::enum(VisitDocumentType::class)],
            'badge_number' => ['nullable', 'string', 'max:30'],
            'bag_inspected' => ['nullable', 'boolean'],
            'special_permission' => ['nullable', 'boolean'],
            'access_level' => ['required', Rule::enum(VisitAccessLevel::class)],
            'blacklisted' => ['nullable', 'boolean'],
            'security_notes' => ['nullable', 'string', 'max:1000'],
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
            'host_user_id.required' => 'Select the employee this person is visiting.',
            'host_user_id.exists' => 'The selected host employee no longer exists.',
            'photo.image' => 'The photo must be an image (jpg, png, webp…).',
            'photo.max' => 'The photo may not be larger than 2 MB.',
            'expected_check_in.date_format' => 'Enter the expected check-in time as HH:MM.',
        ];
    }
}
