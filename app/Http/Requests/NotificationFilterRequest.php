<?php
namespace App\Http\Requests;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class NotificationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'string'],
            'status' => ['sometimes', new Enum(NotificationStatus::class)],
            'type' => ['sometimes', new Enum(NotificationType::class)],
            'tenant_id' => ['sometimes', 'uuid', 'exists:tenants,id'],
            'from_date' => ['sometimes', 'date', 'before_or_equal:to_date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}