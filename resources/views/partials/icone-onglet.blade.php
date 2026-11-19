{{--
    Icône de l'onglet du navigateur : les armoiries officielles quand elles ont été
    déposées dans public/images/, sinon le drapeau national en attendant.
    Le paramètre ?v= force les navigateurs à oublier une ancienne icône en cache
    (par exemple le logo Laravel d'une autre application sur la même adresse).
--}}
@if (\App\Support\Armoiries::fichier())
    <link rel="icon" type="{{ \App\Support\Armoiries::typeIcone() }}" href="{{ \App\Support\Armoiries::url() }}">
    <link rel="apple-touch-icon" href="{{ \App\Support\Armoiries::url() }}">
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icone-drapeau.svg') }}?v={{ filemtime(public_path('images/icone-drapeau.svg')) }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
@endif
