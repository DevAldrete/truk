<?php

namespace App\Http\Requests\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeamMemberRequest extends FormRequest
{
    /**
     * Normalise the handle and compose the organization-scoped login.
     */
    protected function prepareForValidation(): void
    {
        $team = $this->route('team');
        $handle = strtolower(trim((string) $this->input('username')));

        $this->merge([
            'username' => $handle,
            'login' => $team instanceof Team ? $team->slug.'/'.$handle : $handle,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        abort_if(! $this->route('team') instanceof Team, 404);

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/',
            ],
            'login' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(array_column(TeamRole::assignable(), 'value'))],
        ];
    }
}
