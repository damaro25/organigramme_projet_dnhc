@php($creation = ! $utilisateur->exists)
<x-app-layout :titre="$creation ? 'Nouveau compte' : $utilisateur->name">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">{{ $creation ? 'Nouveau compte' : $utilisateur->name }}</h1>
            <a href="{{ route('admin.utilisateurs.index') }}" class="text-sm text-slate-600 hover:underline">← Retour</a>
        </div>
    </x-slot>
    <form method="POST" action="{{ $creation ? route('admin.utilisateurs.store') : route('admin.utilisateurs.update', $utilisateur) }}" class="carte max-w-2xl space-y-4 p-5">
        @csrf
        @unless ($creation) @method('PUT') @endunless
        <div>
            <label for="agent_id" class="etiquette">Fiche agent liée *</label>
            <select id="agent_id" name="agent_id" required class="champ">
                <option value="">— Choisir —</option>
                @foreach ($agents as $a)
                    <option value="{{ $a->id }}" @selected(old('agent_id', $utilisateur->agent_id) == $a->id)>{{ $a->nom_complet }} – {{ $a->poste }} ({{ $a->structure?->libelle_court }})</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Le nom du compte reprend celui de la fiche agent.</p>
            <x-input-error :messages="$errors->get('agent_id')" class="mt-1" />
        </div>
        <div>
            <label for="email" class="etiquette">Adresse e-mail de connexion *</label>
            <input id="email" type="email" name="email" value="{{ old('email', $utilisateur->email) }}" required class="champ">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div>
            <label for="role" class="etiquette">Rôle *</label>
            <select id="role" name="role" class="champ">
                @foreach ($roles as $valeur => $libelle)
                    <option value="{{ $valeur }}" @selected(old('role', $utilisateur->roles->first()?->name) === $valeur)>{{ $libelle }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-1" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="etiquette">Mot de passe {{ $creation ? '*' : '(laisser vide pour ne pas changer)' }}</label>
                <input id="password" type="password" name="password" autocomplete="new-password" class="champ" @required($creation)>
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <label for="password_confirmation" class="etiquette">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="champ">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="actif" value="0">
            <input type="checkbox" name="actif" value="1" @checked(old('actif', $utilisateur->actif ?? true)) class="rounded border-slate-300 text-dnh-700"> Compte actif
        </label>
        <div class="flex gap-2 border-t border-slate-200 pt-4">
            <button class="btn-primaire">{{ $creation ? 'Créer le compte' : 'Enregistrer' }}</button>
            <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-app-layout>
