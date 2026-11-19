{{--
    Icône de l'onglet du navigateur : emblème de la DNH (carte de la Guinée et flamme)
    sur fond marine. Le paramètre ?v= force les navigateurs à oublier une ancienne
    icône en cache.
--}}
<link rel="icon" type="image/svg+xml" href="{{ asset('images/icone-dnh.svg') }}?v={{ filemtime(public_path('images/icone-dnh.svg')) }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
<meta name="theme-color" content="#000b3d">
@include('partials.polices')
