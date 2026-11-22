{{--
    Logo de la Direction Nationale des Hydrocarbures.
      variante : blanc (fonds sombres, par défaut) ou marine (fonds clairs)
      symbole  : true pour la seule carte avec la flamme (espaces réduits, favicon…)
--}}
@props(['variante' => 'blanc', 'symbole' => false])

<img src="{{ asset('images/'.($symbole ? 'symbole' : 'logo').'-dnh-'.($variante === 'marine' ? 'marine' : 'blanc').'.svg') }}"
     alt="Logo de la Direction Nationale des Hydrocarbures"
     {{ $attributes->merge(['class' => 'block h-auto']) }}>
