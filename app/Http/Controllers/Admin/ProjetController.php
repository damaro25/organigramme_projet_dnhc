<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleProjet;
use App\Enums\StatutProjet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjetRequest;
use App\Models\Agent;
use App\Models\PieceJointe;
use App\Models\Projet;
use App\Models\Structure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjetController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Projet::class);

        $filtres = $request->validate([
            'recherche' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', Rule::enum(StatutProjet::class)],
            'structure_id' => ['nullable', 'integer', 'exists:structures,id'],
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'archive' => ['nullable', 'boolean'],
            'retard' => ['nullable', 'boolean'],
        ]);
        $filtres['archive'] ??= 0;

        $projets = Projet::query()
            ->filtrer($filtres)
            ->when($filtres['retard'] ?? false, fn ($q) => $q->enRetard())
            ->avecSommesPoids()
            ->with(['structure', 'responsables'])
            ->orderByRaw('date_fin_prevue IS NULL, date_fin_prevue')
            ->paginate(20)
            ->withQueryString();

        return view('admin.projets.index', [
            'projets' => $projets,
            'filtres' => $filtres,
            'structures' => Structure::query()->whereIn('type', ['direction', 'division', 'section'])->ordonne()->get(),
            'agents' => Agent::actifs()->orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Projet::class);

        return view('admin.projets.form', ['projet' => new Projet(['statut' => StatutProjet::NonDemarre])] + $this->listes());
    }

    public function store(ProjetRequest $request): RedirectResponse
    {
        $projet = DB::transaction(function () use ($request) {
            $projet = Projet::create($request->safe()->except(['affectations', 'pieces_jointes']));
            $projet->agents()->sync($request->affectations());
            $this->journaliserAffectations($projet, '');
            $this->enregistrerPiecesJointes($request, $projet);

            return $projet;
        });

        return redirect()->route('admin.projets.edit', $projet)->with('succes', 'Le projet a été créé. Ajoutez maintenant ses étapes.');
    }

    public function edit(Projet $projet): View
    {
        $this->authorize('view', $projet);
        $projet->load(['agents', 'documents.auteur', 'validateur']);

        return view('admin.projets.form', ['projet' => $projet] + $this->listes());
    }

    public function update(ProjetRequest $request, Projet $projet): RedirectResponse
    {
        DB::transaction(function () use ($request, $projet) {
            $avant = $this->resumeAffectations($projet);
            $projet->update($request->safe()->except(['affectations', 'pieces_jointes']));
            $projet->agents()->sync($request->affectations());
            $this->journaliserAffectations($projet, $avant);
            $this->enregistrerPiecesJointes($request, $projet);
        });

        return redirect()->route('admin.projets.edit', $projet)->with('succes', 'Le projet a été enregistré.');
    }

    public function destroy(Projet $projet): RedirectResponse
    {
        $this->authorize('delete', $projet);
        $projet->piecesJointes->each->delete();
        $projet->delete();

        return redirect()->route('admin.projets.index')->with('succes', 'Le projet a été supprimé.');
    }

    public function archiver(Projet $projet): RedirectResponse
    {
        $this->authorize('archiver', $projet);
        $projet->update(['archive' => ! $projet->archive]);

        return back()->with('succes', $projet->archive ? 'Le projet a été archivé.' : 'Le projet a été désarchivé.');
    }

    public function dupliquer(Projet $projet): RedirectResponse
    {
        $this->authorize('dupliquer', $projet);
        $copie = $projet->load('etapes', 'agents')->dupliquer();

        return redirect()->route('admin.projets.edit', $copie)->with('succes', 'Le projet a été dupliqué. Adaptez le titre et les dates.');
    }

    public function valider(Projet $projet): RedirectResponse
    {
        $this->authorize('valider', $projet);
        $projet->update(['valide_par' => auth()->id(), 'valide_le' => now()]);

        return back()->with('succes', 'Le projet a été validé par la direction.');
    }

    public function telechargerPieceJointe(Projet $projet, PieceJointe $pieceJointe): StreamedResponse
    {
        $this->authorize('view', $projet);
        abort_unless($pieceJointe->projet_id === $projet->id, 404);

        return Storage::disk('public')->download($pieceJointe->chemin, $pieceJointe->nom);
    }

    /**
     * Affiche un PDF ou une photo dans le navigateur (visionneuse), au lieu de le
     * télécharger. Les autres formats (Word, Excel…) restent en téléchargement.
     */
    public function afficherPieceJointe(Projet $projet, PieceJointe $pieceJointe): StreamedResponse
    {
        $this->authorize('view', $projet);
        abort_unless($pieceJointe->projet_id === $projet->id, 404);

        if (! $pieceJointe->est_visualisable) {
            return $this->telechargerPieceJointe($projet, $pieceJointe);
        }

        $type = $pieceJointe->extension === 'pdf' ? 'application/pdf' : 'image/'.($pieceJointe->extension === 'jpg' ? 'jpeg' : $pieceJointe->extension);

        return Storage::disk('public')->response($pieceJointe->chemin, $pieceJointe->nom, [
            'Content-Type' => $type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }

    public function supprimerPieceJointe(Projet $projet, PieceJointe $pieceJointe): RedirectResponse
    {
        $this->authorize('update', $projet);
        abort_unless($pieceJointe->projet_id === $projet->id && $pieceJointe->etape_id === null, 404);
        $pieceJointe->delete();
        PieceJointe::journaliser($projet, 'Document supprimé', [$pieceJointe->nom], auth()->user());

        return back()->with('succes', 'La pièce jointe a été supprimée.');
    }

    private function enregistrerPiecesJointes(ProjetRequest $request, Projet $projet): void
    {
        $noms = [];
        foreach ($request->file('pieces_jointes', []) as $fichier) {
            $noms[] = PieceJointe::depuisFichier($fichier, $projet, null, $request->user())->nom;
        }
        PieceJointe::journaliser($projet, 'Documents ajoutés', $noms, $request->user());
    }

    private function listes(): array
    {
        $user = auth()->user();

        return [
            'structures' => Structure::query()->ordonne()->get()
                ->when(! $user->estAdministrateur(), fn ($c) => $c->whereIn('id', $user->structuresGerees())),
            'agents' => Agent::actifs()->with('structure')->orderBy('nom')->get(),
            'statuts' => StatutProjet::cases(),
        ];
    }

    /** Agents affectés, sous forme lisible pour le journal : « Nom (Rôle), … ». */
    private function resumeAffectations(Projet $projet): string
    {
        return $projet->agents()->orderBy('nom')->orderBy('prenom')->get()
            ->map(function ($agent) {
                $role = $agent->pivot->role;
                $role = $role instanceof RoleProjet ? $role : RoleProjet::tryFrom((string) $role);

                return $agent->nom_complet.' ('.($role?->libelle() ?? '?').')';
            })
            ->join(', ');
    }

    /** Le pivot agent_projet n'est pas un modèle journalisé : on trace le changement à la main. */
    private function journaliserAffectations(Projet $projet, string $avant): void
    {
        $apres = $this->resumeAffectations($projet);
        if ($apres === $avant) {
            return;
        }

        activity('projets')
            ->performedOn($projet)
            ->event('updated')
            ->withProperties(['attributes' => ['affectations' => $apres], 'old' => ['affectations' => $avant ?: null]])
            ->log('Affectations du projet modifiées');
    }
}
