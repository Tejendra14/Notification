<?php
namespace App\Http\Requests;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(NotificationType::class)],
            'channel' => ['required', 'string', 'in:notification,marketing,alert'],
            'payload' => ['required', 'array'],
            'payload.subject' => ['required_if:type,email', 'string', 'max:500'],
            'payload.content' => ['required', 'string'],
            'payload.recipient' => ['required_if:type,email,sms', 'email', 'string'],
            'payload.template_name' => ['sometimes', 'string', 'max:100'],
            'payload.variables' => ['sometimes', 'array'],
            'tenant_id' => ['sometimes', 'uuid', 'exists:tenants,id'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'payload.recipient.required_if' => 'Recipient is required for email and SMS notifications',
            'payload.subject.required_if' => 'Subject is required for email notifications',
        ];
    }
}