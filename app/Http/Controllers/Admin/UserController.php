<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $utilisateurs = User::query()
            ->with(['agent.structure', 'roles'])
            ->when($request->string('recherche')->toString(), fn ($q, $t) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.utilisateurs.index', ['utilisateurs' => $utilisateurs, 'roles' => UserRequest::ROLES]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.utilisateurs.form', ['utilisateur' => new User(['actif' => true])] + $this->listes());
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $donnees = $request->validated();
        $agent = Agent::findOrFail($donnees['agent_id']);

        $utilisateur = User::create([
            'name' => $agent->nom_complet,
            'email' => $donnees['email'],
            'password' => $donnees['password'],
            'agent_id' => $agent->id,
            'actif' => $donnees['actif'],
            'email_verified_at' => now(),
        ]);
        $utilisateur->syncRoles([$donnees['role']]);

        return redirect()->route('admin.utilisateurs.index')->with('succes', "Le compte de {$utilisateur->name} a été créé.");
    }

    public function edit(User $utilisateur): View
    {
        $this->authorize('update', $utilisateur);

        return view('admin.utilisateurs.form', ['utilisateur' => $utilisateur] + $this->listes($utilisateur));
    }

    public function update(UserRequest $request, User $utilisateur): RedirectResponse
    {
        $donnees = $request->validated();

        if ($utilisateur->is($request->user()) && (! $donnees['actif'] || $donnees['role'] !== User::ROLE_ADMIN)) {
            return back()->withErrors(['role' => 'Vous ne pouvez pas retirer vos propres droits d\'administrateur ni désactiver votre compte.']);
        }

        $agent = Agent::findOrFail($donnees['agent_id']);
        $utilisateur->fill([
            'name' => $agent->nom_complet,
            'email' => $donnees['email'],
            'agent_id' => $agent->id,
            'actif' => $donnees['actif'],
        ]);
        if (! empty($donnees['password'])) {
            $utilisateur->password = $donnees['password'];
        }
        $utilisateur->save();
        $utilisateur->syncRoles([$donnees['role']]);

        return redirect()->route('admin.utilisateurs.index')->with('succes', 'Le compte a été modifié.');
    }

    private function listes(?User $utilisateur = null): array
    {
        $pris = User::query()->whereNotNull('agent_id')
            ->when($utilisateur?->exists, fn ($q) => $q->whereKeyNot($utilisateur->id))
            ->pluck('agent_id');

        return [
            'agents' => Agent::actifs()->with('structure')->whereNotIn('id', $pris)->orderBy('nom')->get(),
            'roles' => UserRequest::ROLES,
        ];
    }
}
