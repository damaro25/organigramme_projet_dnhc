<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TypeStructure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentRequest;
use App\Models\Agent;
use App\Models\Structure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agent::class);

        $filtres = $request->validate([
            'recherche' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'integer', 'exists:structures,id'],
            'section' => ['nullable', 'integer', 'exists:structures,id'],
            'etat' => ['nullable', Rule::in(['actifs', 'inactifs', 'tous'])],
        ]);

        $agents = Agent::query()
            ->with('structure', 'superieur')
            ->recherche($filtres['recherche'] ?? null)
            ->dansStructure($filtres['section'] ?? $filtres['division'] ?? null)
            ->when(($filtres['etat'] ?? 'actifs') !== 'tous', fn ($q) => $q->where('actif', ($filtres['etat'] ?? 'actifs') === 'actifs'))
            ->orderBy('nom')->orderBy('prenom')
            ->paginate(20)
            ->withQueryString();

        return view('admin.agents.index', [
            'agents' => $agents,
            'filtres' => $filtres,
            'divisions' => Structure::deType(TypeStructure::Division)->ordonne()->get(),
            'sections' => Structure::deType(TypeStructure::Section)->ordonne()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Agent::class);

        return view('admin.agents.form', ['agent' => new Agent(['actif' => true])] + $this->listes());
    }

    public function store(AgentRequest $request): RedirectResponse
    {
        $agent = Agent::create($request->validated());

        return redirect()->route('admin.agents.edit', $agent)->with('succes', "L'agent {$agent->nom_complet} a été créé.");
    }

    public function edit(Agent $agent): View
    {
        // Un chargé d'études peut ouvrir sa propre fiche pour changer sa photo.
        abort_unless(auth()->user()->can('update', $agent) || auth()->user()->can('updatePhoto', $agent), 403);

        return view('admin.agents.form', ['agent' => $agent] + $this->listes($agent));
    }

    public function update(AgentRequest $request, Agent $agent): RedirectResponse|JsonResponse
    {
        $agent->update($request->validated());

        return redirect()->route('admin.agents.edit', $agent)->with('succes', 'Les modifications ont été enregistrées.');
    }

    /** Modification rapide du poste depuis l'organigramme. */
    public function modifierPoste(Request $request, Agent $agent): JsonResponse
    {
        $this->authorize('update', $agent);
        $donnees = $request->validate(['poste' => ['required', 'string', 'max:255']]);
        $agent->update($donnees);

        return response()->json(['message' => 'Poste mis à jour.', 'poste' => $agent->poste]);
    }

    /** Désactivation (départ, mutation) ou réactivation, sans suppression pour conserver l'historique. */
    public function basculerActif(Agent $agent): RedirectResponse
    {
        $this->authorize('desactiver', $agent);

        $agent->update(['actif' => ! $agent->actif]);
        if (! $agent->actif) {
            // Les subordonnés directs remontent au supérieur de l'agent désactivé.
            $agent->subordonnes()->update(['superieur_id' => $agent->superieur_id]);
            $agent->user?->update(['actif' => false]);
        }

        return back()->with('succes', $agent->actif
            ? "{$agent->nom_complet} a été réactivé."
            : "{$agent->nom_complet} a été désactivé. Son historique est conservé.");
    }

    private function listes(?Agent $agent = null): array
    {
        $user = auth()->user();
        $structures = Structure::query()->ordonne()->get()
            ->when(! $user->estAdministrateur(), fn ($c) => $c->whereIn('id', $user->structuresGerees()));

        return [
            'structures' => $structures,
            'superieurs' => Agent::actifs()->with('structure')
                ->when($agent, fn ($q) => $q->whereKeyNot($agent->id))
                ->orderBy('nom')->get(),
            'peutModifier' => $agent ? $user->can('update', $agent) : $user->can('create', Agent::class),
            'peutDesactiver' => $agent && $user->can('desactiver', $agent),
            'peutModifierPhoto' => $agent && $user->can('updatePhoto', $agent),
        ];
    }
}
