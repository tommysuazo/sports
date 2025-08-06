<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportWnbaGamesRequest extends FormRequest
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
            // 'source_site' => 'required|in:sportsnet',
            'from' => ['required', 'date', 'before_or_equal:to', 'after_or_equal:2025-05-16'],
            'to' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:2025-09-11'],
            'league' => 'required',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge(['league' => 'wnba']);
    }
}
