{{--
    Icône de l'onglet du navigateur et de l'écran d'accueil (iOS) : le symbole de la DNH,
    blanc sur marine. Le paramètre ?v= force les navigateurs à oublier une ancienne icône
    en cache (par exemple le logo Laravel d'une autre application sur la même adresse).
--}}
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ filemtime(public_path('apple-touch-icon.png')) }}">
<meta name="theme-color" content="#000b3d">
