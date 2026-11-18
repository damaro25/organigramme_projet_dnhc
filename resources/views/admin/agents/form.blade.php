@php($creation = ! $agent->exists)
<x-app-layout :titre="$creation ? 'Nouvel agent' : $agent->nom_complet">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">{{ $creation ? 'Nouvel agent' : $agent->nom_complet }}</h1>
            <a href="{{ route('admin.agents.index') }}" class="text-sm text-slate-600 hover:underline">← Retour à la liste</a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Photo --}}
        @unless ($creation)
            <section class="carte p-5 lg:order-2">
                <h2 class="mb-3 font-semibold">Photo</h2>
                @if ($peutModifierPhoto)
                    <div x-data="editeurPhoto({ urlEnvoi: @js(route('admin.agents.photo.store', $agent)), urlSuppression: @js(route('admin.agents.photo.destroy', $agent)), photo: @js($agent->photo_url), avatarDefaut: @js(asset(\App\Models\Agent::AVATAR_DEFAUT)), aPhoto: @js((bool) $agent->photo) })">
                        @include('admin.agents.partials.editeur-photo-corps')
                    </div>
                @else
                    <img src="{{ $agent->photo_url }}" alt="" class="h-24 w-24 rounded-full object-cover">
                @endif
            </section>
        @endunless

        {{-- Identité, poste et rattachement --}}
        <form method="POST" action="{{ $creation ? route('admin.agents.store') : route('admin.agents.update', $agent) }}"
              class="carte space-y-4 p-5 lg:col-span-2 lg:order-1">
            @csrf
            @unless ($creation) @method('PUT') @endunless
            <fieldset @disabled(! $peutModifier) class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nom" class="etiquette">Nom *</label>
                        <input id="nom" name="nom" value="{{ old('nom', $agent->nom) }}" required maxlength="100" class="champ">
                        <x-input-error :messages="$errors->get('nom')" class="mt-1" />
                    </div>
                    <div>
                        <label for="prenom" class="etiquette">Prénom *</label>
                        <input id="prenom" name="prenom" value="{{ old('prenom', $agent->prenom) }}" required maxlength="100" class="champ">
                        <x-input-error :messages="$errors->get('prenom')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <label for="poste" class="etiquette">Poste *</label>
                    <input id="poste" name="poste" value="{{ old('poste', $agent->poste) }}" required maxlength="255" class="champ">
                    <x-input-error :messages="$errors->get('poste')" class="mt-1" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="structure_id" class="etiquette">Structure *</label>
                        <select id="structure_id" name="structure_id" required class="champ">
                            <option value="">— Choisir —</option>
                            @foreach ($structures as $s)
                                <option value="{{ $s->id }}" @selected(old('structure_id', $agent->structure_id) == $s->id)>{{ $s->nom }}{{ $s->sigle ? " ({$s->sigle})" : '' }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('structure_id')" class="mt-1" />
                    </div>
                    <div>
                        <label for="superieur_id" class="etiquette">Supérieur hiérarchique</label>
                        <select id="superieur_id" name="superieur_id" class="champ">
                            <option value="">— Aucun —</option>
                            @foreach ($superieurs as $s)
                                <option value="{{ $s->id }}" @selected(old('superieur_id', $agent->superieur_id) == $s->id)>{{ $s->nom_complet }} – {{ $s->poste }} ({{ $s->structure?->libelle_court }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('superieur_id')" class="mt-1" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="etiquette">Adresse e-mail</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $agent->email) }}" class="champ">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <div>
                        <label for="telephone" class="etiquette">Téléphone</label>
                        <input id="telephone" name="telephone" value="{{ old('telephone', $agent->telephone) }}" maxlength="30" class="champ">
                        <x-input-error :messages="$errors->get('telephone')" class="mt-1" />
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="est_responsable" value="0">
                        <input type="checkbox" name="est_responsable" value="1" @checked(old('est_responsable', $agent->est_responsable)) class="rounded border-slate-300 text-dnh-700">
                        Responsable de la structure (chef)
                    </label>
                    <label class="flex items-center gap-2 text-sm">Ordre d'affichage
                        <input type="number" name="ordre" min="0" max="999" value="{{ old('ordre', $agent->ordre ?? 0) }}" class="w-20 rounded-lg border-slate-300 text-sm">
                    </label>
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
                @if ($peutModifier)
                    <button class="btn-primaire">{{ $creation ? 'Créer l\'agent' : 'Enregistrer' }}</button>
                @endif
                <a href="{{ route('admin.agents.index') }}" class="btn-secondaire">Annuler</a>
            </div>
        </form>
    </div>

    {{-- Désactivation --}}
    @if ($peutDesactiver)
        <form method="POST" action="{{ route('admin.agents.actif', $agent) }}" class="carte mt-6 flex flex-wrap items-center justify-between gap-3 p-5"
              onsubmit="return confirm(@js($agent->actif ? 'Désactiver cet agent ? Il disparaîtra de l\'organigramme mais son historique sera conservé.' : 'Réactiver cet agent ?'))">
            @csrf @method('PATCH')
            <div>
                <h2 class="font-semibold">{{ $agent->actif ? 'Désactiver l\'agent' : 'Réactiver l\'agent' }}</h2>
                <p class="text-sm text-slate-500">{{ $agent->actif ? 'En cas de départ ou de mutation. L\'agent est retiré de l\'organigramme, son historique est conservé et son compte est désactivé.' : 'L\'agent réapparaîtra dans l\'organigramme.' }}</p>
            </div>
            <button class="{{ $agent->actif ? 'btn-danger' : 'btn-secondaire' }}">{{ $agent->actif ? 'Désactiver' : 'Réactiver' }}</button>
        </form>
    @endif
</x-app-layout>
