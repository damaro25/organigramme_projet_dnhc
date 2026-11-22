{{--
    Champ de formulaire complet : libellé, champ, aide contextuelle et message d'erreur
    (directive @error sur le nom du champ). Les attributs (wire:model, x-model, required…)
    passent tels quels sur le champ.
      <x-input name="titre" label="Titre du projet" :value="old('titre', $projet->titre)" required aide="Visible dans l'organigramme." />
--}}
@props(['name', 'label', 'type' => 'text', 'aide' => null, 'id' => null])

@php($id ??= str_replace(['[', ']', '.'], '-', $name))

<div>
    <label for="{{ $id }}" class="etiquette">{{ $label }}@if ($attributes->has('required'))<span class="text-danger-700" aria-hidden="true"> *</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @error($name) aria-invalid="true" aria-describedby="{{ $id }}-erreur" @enderror
           {{ $attributes->class(['champ', 'border-danger-600 focus:border-danger-600 focus:ring-danger-600' => $errors->has($name)]) }}>
    @if ($aide)<p class="aide">{{ $aide }}</p>@endif
    @error($name)<p id="{{ $id }}-erreur" class="mt-1 text-xs font-medium text-danger-700">{{ $message }}</p>@enderror
</div>
