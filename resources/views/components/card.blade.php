{{--
    Carte blanche à bord fin et ombre teintée de marine.
      titre : en-tête facultatif (capitales condensées) ; slot « actions » à droite du titre.
--}}
@props(['titre' => null])

<section {{ $attributes->merge(['class' => 'carte']) }}>
    @if ($titre || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-fond-bord px-5 py-3">
            @if ($titre)<h2 class="titre-section">{{ $titre }}</h2>@endif
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="p-5">{{ $slot }}</div>
</section>
