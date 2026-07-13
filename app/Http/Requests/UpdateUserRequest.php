<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role->canManageUsers();
    }

    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'phone' => ['required', 'string', 'max:30'],
            // Password is optional on edit — leave blank to keep the current one.
            'password' => ['nullable', 'confirmed', \App\Support\PasswordPolicy::rule()],
            // No 'role' rule: the edit form never changes roles —
            // promotion/demotion happens only through the dedicated
            // users.role endpoint (Super Admin only).
            'status' => ['required', Rule::enum(UserStatus::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
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
            'email.unique' => 'A user with this email already exists.',
            'password.confirmed' => 'The password confirmation does not match.',
            'avatar.image' => 'The avatar must be an image (jpg, png, webp…).',
            'avatar.max' => 'The avatar may not be larger than 2 MB.',
        ];
    }
}
