<?php

namespace App\Http\Requests;

use App\Support\ActivityCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client';
    }

    public function rules(): array
    {
        $rules = ['performed_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'activity_key' => ['required', Rule::in(array_keys(ActivityCatalog::all()))],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'body_weight' => ['required', 'numeric', 'min:20', 'max:400'],
            'notes' => ['nullable', 'string', 'max:500']];
        if ($this->isMethod('post')) {
            $rules['request_token'] = ['required', 'uuid'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['performed_on' => 'fecha', 'activity_key' => 'actividad',
            'duration_minutes' => 'duración en minutos', 'body_weight' => 'peso corporal', 'notes' => 'notas'];
    }
}
