<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public const ROLES = [
        User::ROLE_ADMIN => 'Administrateur (service Informatique)',
        User::ROLE_DIRECTION => 'Direction (DNH / DNAH)',
        User::ROLE_CHEF => 'Chef de division ou de section',
        User::ROLE_CHARGE => 'Chargé d\'études',
        User::ROLE_CONSULTATION => 'Consultation (lecture seule)',
    ];

    public function authorize(): bool
    {
        $cible = $this->route('utilisateur');

        return $cible ? $this->user()->can('update', $cible) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $cible = $this->route('utilisateur');

        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($cible)],
            'agent_id' => ['required', 'integer', Rule::exists('agents', 'id'), Rule::unique('users', 'agent_id')->ignore($cible)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'actif' => ['boolean'],
            'password' => [$cible ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'agent_id.unique' => 'Cette fiche agent est déjà liée à un autre compte.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['actif' => $this->boolean('actif')]);
    }
}
