<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;

class ReorderStopsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stop_ids' => ['required', 'array', 'min:1'],
            'stop_ids.*' => ['integer'],
        ];
    }
}
