<?php

namespace App\Http\Requests\Admin;

use App\Enums\GroupeStructure;
use App\Enums\TypeLien;
use App\Enums\TypeStructure;
use App\Models\Structure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $structure = $this->route('structure');

        return $structure ? $this->user()->can('update', $structure) : $this->user()->can('create', Structure::class);
    }

    public function rules(): array
    {
        $structure = $this->route('structure');

        return [
            'nom' => ['required', 'string', 'max:255'],
            'sigle' => ['nullable', 'string', 'max:30'],
            'type' => ['required', Rule::enum(TypeStructure::class)],
            'groupe' => ['nullable', 'required_if:type,service_rattache', Rule::enum(GroupeStructure::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('structures', 'id'),
                Rule::notIn($structure ? $structure->descendantIds() : [])],
            'type_lien' => ['required', Rule::enum(TypeLien::class)],
            'couleur' => ['nullable', 'hex_color'],
            'ordre' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'Une structure ne peut pas être rattachée à elle-même ni à l\'une de ses sous-structures.',
            'groupe.required_if' => 'Indiquez le groupe d\'un service rattaché (appui technique ou personnel d\'appui).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'groupe' => $this->input('type') === 'service_rattache' ? $this->input('groupe') : null,
            'type_lien' => $this->input('type_lien', 'hierarchique'),
            'ordre' => $this->input('ordre', 0) ?? 0,
        ]);
    }
}
