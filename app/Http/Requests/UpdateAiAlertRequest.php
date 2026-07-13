<?php

namespace App\Http\Requests;

use App\Enums\AiAlertStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->canManageAlerts();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AiAlertStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Choose the new status for this alert.',
        ];
    }
}
