<x-app-layout titre="Utilisateurs">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">Utilisateurs</h1>
            <a href="{{ route('admin.utilisateurs.create') }}" class="btn-primaire">+ Nouveau compte</a>
        </div>
    </x-slot>
    <form method="GET" class="mb-4 flex gap-2">
        <input name="recherche" value="{{ request('recherche') }}" placeholder="Nom ou adresse e-mail" class="champ mt-0 max-w-sm" aria-label="Rechercher">
        <button class="btn-secondaire">Rechercher</button>
    </form>
    <div class="carte overflow-x-auto">
        <table class="tableau">
            <thead class="bg-slate-50"><tr><th>Utilisateur</th><th>Rôle</th><th>Fiche agent</th><th>État</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($utilisateurs as $u)
                    <tr>
                        <td><p class="font-medium">{{ $u->name }}</p><p class="text-xs text-slate-500">{{ $u->email }}</p></td>
                        <td>{{ $roles[$u->roles->first()?->name] ?? '—' }}</td>
                        <td>{{ $u->agent ? $u->agent->poste.' · '.$u->agent->structure?->libelle_court : '—' }}</td>
                        <td>@if ($u->actif)<span class="rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Actif</span>@else<span class="rounded bg-slate-200 px-2 py-0.5 text-xs">Désactivé</span>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.utilisateurs.edit', $u) }}" class="text-dnh-700 hover:underline">Modifier</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $utilisateurs->links() }}</div>
</x-app-layout>
