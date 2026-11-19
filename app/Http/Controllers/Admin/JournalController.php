<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Etape;
use App\Models\Projet;
use App\Models\Structure;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/** Journal des modifications : qui a modifié quelle photo, quel projet ou quelle étape, et quand. */
class JournalController extends Controller
{
    public function __invoke(Request $request, JournalService $journal): View
    {
        $filtres = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(JournalService::TYPES))],
            'utilisateur' => ['nullable', 'integer'],
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', 'after_or_equal:du'],
        ]);

        $perimetre = $request->user()->perimetre();

        $activites = Activity::query()
            ->with(['causer', 'subject'])
            // Hors vue globale : seulement les modifications portant sur la branche du compte.
            ->when(! $perimetre->estGlobal(), fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($x) => $x->where('subject_type', Agent::class)->whereIn('subject_id', $perimetre->restreindreAgents(Agent::query())->pluck('agents.id')))
                ->orWhere(fn ($x) => $x->where('subject_type', Projet::class)->whereIn('subject_id', $perimetre->projetIds()))
                ->orWhere(fn ($x) => $x->where('subject_type', Etape::class)->whereIn('subject_id', $perimetre->etapeIds()))
                ->orWhere(fn ($x) => $x->where('subject_type', Structure::class)->whereIn('subject_id', $perimetre->structureIds()))
                ->orWhere(fn ($x) => $x->where('subject_type', User::class)->where('subject_id', $request->user()->id))))
            ->when($filtres['type'] ?? null, fn ($q, $t) => $q->where('log_name', $t))
            ->when($filtres['utilisateur'] ?? null, fn ($q, $u) => $q->where('causer_type', User::class)->where('causer_id', $u))
            ->when($filtres['du'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtres['au'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.journal', [
            'activites' => $activites,
            'journal' => $journal,
            'filtres' => $filtres,
            'types' => JournalService::TYPES,
            'utilisateurs' => User::query()
                ->when(! $perimetre->estGlobal(), fn ($q) => $q->whereIn('agent_id', $perimetre->agentIds()))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
