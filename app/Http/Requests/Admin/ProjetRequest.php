<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleProjet;
use App\Enums\StatutProjet;
use App\Models\PieceJointe;
use App\Models\Projet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProjetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projet = $this->route('projet');

        return $projet ? $this->user()->can('update', $projet) : $this->user()->can('create', Projet::class);
    }

    public function rules(): array
    {
        $structures = $this->user()->estAdministrateur()
            ? Rule::exists('structures', 'id')
            : Rule::in($this->user()->structuresGerees());

        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'structure_id' => ['required', 'integer', $structures],
            'date_debut' => ['nullable', 'date'],
            'date_fin_prevue' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::enum(StatutProjet::class)],
            'affectations' => ['array'],
            'affectations.*.agent_id' => ['required', 'integer', 'distinct', Rule::exists('agents', 'id')],
            'affectations.*.role' => ['required', Rule::enum(RoleProjet::class)],
            'pieces_jointes' => ['array', 'max:'.PieceJointe::NOMBRE_MAX],
            'pieces_jointes.*' => PieceJointe::reglesFichier(),
        ];
    }

    public function messages(): array
    {
        return [
            'structure_id.in' => 'Vous ne pouvez créer un projet que dans votre structure ou l\'une de ses sous-structures.',
            'date_fin_prevue.after_or_equal' => 'L\'échéance doit être postérieure ou égale à la date de début.',
            'pieces_jointes.max' => 'Vous pouvez joindre au maximum '.PieceJointe::NOMBRE_MAX.' fichiers à la fois.',
            'pieces_jointes.*.mimes' => 'Les pièces jointes doivent être des documents PDF, Word, Excel, PowerPoint ou des photos (JPG, PNG, WEBP).',
            'pieces_jointes.*.max' => 'Chaque pièce jointe ne doit pas dépasser 10 Mo.',
            'pieces_jointes.*.uploaded' => 'Un fichier n\'a pas pu être envoyé : il dépasse probablement la taille autorisée par le serveur ('.PieceJointe::tailleMaxLisible().').',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $roles = collect($this->input('affectations', []))->pluck('role');
            if ($roles->isNotEmpty() && ! $roles->contains(RoleProjet::Responsable->value)) {
                $v->errors()->add('affectations', 'Désignez au moins un responsable parmi les agents affectés.');
            }
        }];
    }

    /** Affectations au format attendu par sync(). */
    public function affectations(): array
    {
        return collect($this->validated('affectations', []))
            ->mapWithKeys(fn ($a) => [(int) $a['agent_id'] => ['role' => $a['role']]])
            ->all();
    }
}
