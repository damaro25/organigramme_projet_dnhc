{{--
    Bouton de l'identité DNH.
      variante : primaire (marine plein, par défaut), secondaire (contour marine),
                 tertiaire (texte), danger (contour rouge), accent (ambre, à petites doses)
      href     : rend un lien <a> avec l'apparence d'un bouton
      chargement : nom d'une action Livewire (wire:target) pendant laquelle un indicateur
                   s'affiche et le bouton se désactive
    Exemples : <x-button>Enregistrer</x-button>
               <x-button variante="secondaire" href="{{ route('admin.projets.index') }}">Annuler</x-button>
--}}
@props(['variante' => 'primaire', 'href' => null, 'chargement' => null])

@php($classe = 'btn-'.(in_array($variante, ['primaire', 'secondaire', 'tertiaire', 'danger', 'accent'], true) ? $variante : 'primaire'))

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classe]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classe]) }}
            @if ($chargement) wire:loading.attr="disabled" wire:target="{{ $chargement }}" @endif>
        @if ($chargement)
            <svg wire:loading wire:target="{{ $chargement }}" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3"/></svg>
        @endif
        {{ $slot }}
    </button>
@endif
