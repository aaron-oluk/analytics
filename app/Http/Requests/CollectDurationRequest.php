<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectDurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255'],
            'pathname' => ['required', 'string', 'max:2048'],
            'duration' => ['required', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
