@php($creation = ! $structure->exists)
<x-app-layout :titre="$creation ? 'Nouvelle structure' : $structure->nom">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="titre-page">{{ $creation ? 'Nouvelle structure' : $structure->nom }}</h1>
            <a href="{{ route('admin.structures.index') }}" class="text-sm text-slate-600 hover:underline">← Retour</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ $creation ? route('admin.structures.store') : route('admin.structures.update', $structure) }}"
          class="carte max-w-3xl space-y-4 p-5" x-data="{ type: @js(old('type', $structure->type?->value)) }">
        @csrf
        @unless ($creation) @method('PUT') @endunless
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="nom" class="etiquette">Libellé *</label>
                <input id="nom" name="nom" value="{{ old('nom', $structure->nom) }}" required maxlength="255" class="champ">
                <x-input-error :messages="$errors->get('nom')" class="mt-1" />
            </div>
            <div>
                <label for="sigle" class="etiquette">Sigle</label>
                <input id="sigle" name="sigle" value="{{ old('sigle', $structure->sigle) }}" maxlength="30" class="champ">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="type" class="etiquette">Type *</label>
                <select id="type" name="type" x-model="type" class="champ">
                    @foreach ($types as $t)<option value="{{ $t->value }}">{{ $t->libelle() }}</option>@endforeach
                </select>
            </div>
            <div x-show="type === 'service_rattache'">
                <label for="groupe" class="etiquette">Groupe (affichage sur le côté du DNH) *</label>
                <select id="groupe" name="groupe" class="champ">
                    <option value="">— Choisir —</option>
                    @foreach ($groupes as $g)<option value="{{ $g->value }}" @selected(old('groupe', $structure->groupe?->value) === $g->value)>{{ $g->libelle() }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('groupe')" class="mt-1" />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="parent_id" class="etiquette">Structure parente</label>
                <select id="parent_id" name="parent_id" class="champ">
                    <option value="">— Aucune (sommet) —</option>
                    @foreach ($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $structure->parent_id) == $p->id)>{{ $p->nom }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('parent_id')" class="mt-1" />
            </div>
            <div>
                <label for="type_lien" class="etiquette">Lien avec la structure parente</label>
                <select id="type_lien" name="type_lien" class="champ">
                    @foreach ($liens as $l)<option value="{{ $l->value }}" @selected(old('type_lien', $structure->type_lien?->value) === $l->value)>{{ $l->libelle() }}{{ $l->value === 'fonctionnel' ? ' (trait en pointillés)' : '' }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div x-data="{ couleur: @js(old('couleur', $structure->couleur)), herite: @js(! old('couleur', $structure->couleur)) }">
                <label class="etiquette" for="couleur-texte">Couleur</label>
                <div class="mt-1 flex items-center gap-2">
                    <input type="color" :value="couleur || '#006a45'" @input="couleur = $event.target.value; herite = false" :disabled="herite" class="h-9 w-12 rounded border-slate-300" aria-label="Choisir la couleur">
                    <input id="couleur-texte" name="couleur" x-model="couleur" :disabled="herite" placeholder="#006a45" maxlength="7" class="w-28 rounded-lg border-slate-300 text-sm">
                    <label class="flex items-center gap-1 text-xs text-slate-600"><input type="checkbox" x-model="herite" @change="if (herite) couleur = ''" class="rounded border-slate-300"> Hériter de la division</label>
                </div>
                <x-input-error :messages="$errors->get('couleur')" class="mt-1" />
            </div>
            <div>
                <label for="ordre" class="etiquette">Ordre d'affichage</label>
                <input id="ordre" type="number" name="ordre" min="0" max="999" value="{{ old('ordre', $structure->ordre ?? 0) }}" class="champ w-28">
            </div>
        </div>
        <div class="flex gap-2 border-t border-slate-200 pt-4">
            <button class="btn-primaire">{{ $creation ? 'Ajouter' : 'Enregistrer' }}</button>
            <a href="{{ route('admin.structures.index') }}" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</x-app-layout>
