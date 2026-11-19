<?php

namespace App\Http\Requests\Admin;

use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Création et modification d'un agent (y compris son déplacement de structure ou de supérieur). */
class AgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agent = $this->route('agent');

        return $agent ? $this->user()->can('update', $agent) : $this->user()->can('create', Agent::class);
    }

    public function rules(): array
    {
        $agent = $this->route('agent');
        $perimetre = $this->user()->perimetre();
        $structures = $this->user()->estAdministrateur()
            ? Rule::exists('structures', 'id')
            : Rule::in($this->user()->structuresGerees());

        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'poste' => ['required', 'string', 'max:255'],
            'structure_id' => ['required', 'integer', $structures],
            'superieur_id' => array_filter(['nullable', 'integer', Rule::exists('agents', 'id')->where('actif', true), Rule::notIn(array_filter([$agent?->id])),
                // Hors administrateur : un supérieur de la branche du compte (ou le supérieur actuel).
                $perimetre->estGlobal() ? null : Rule::in(array_filter([...$perimetre->agentIds(), $agent?->superieur_id]))]),
            'email' => ['nullable', 'email', 'max:255', Rule::unique('agents', 'email')->ignore($agent)],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().-]+$/'],
            'est_responsable' => ['boolean'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'structure_id.in' => 'Vous ne pouvez rattacher un agent qu\'à votre structure ou à l\'une de ses sous-structures.',
            'superieur_id.not_in' => 'Un agent ne peut pas être son propre supérieur.',
            'superieur_id.in' => 'Le supérieur doit faire partie de votre branche de l\'organigramme.',
            'telephone.regex' => 'Le numéro de téléphone contient des caractères non autorisés.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_responsable' => $this->boolean('est_responsable'),
            'ordre' => $this->input('ordre', 0) ?? 0,
        ]);
    }

    /** Empêche les boucles hiérarchiques : le supérieur ne peut pas être un subordonné de l'agent. */
    public function after(): array
    {
        return [function (Validator $v) {
            $agent = $this->route('agent');
            $superieurId = $this->input('superieur_id');
            if (! $agent || ! $superieurId) {
                return;
            }
            $courant = Agent::find($superieurId);
            $vus = [];
            while ($courant && ! isset($vus[$courant->id])) {
                if ($courant->superieur_id === $agent->id) {
                    $v->errors()->add('superieur_id', 'Ce supérieur est déjà placé sous cet agent dans la hiérarchie.');

                    return;
                }
                $vus[$courant->id] = true;
                $courant = $courant->superieur;
            }
        }];
    }
}
