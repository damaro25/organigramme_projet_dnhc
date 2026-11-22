{{--
    Message d'alerte. type : info (par défaut), succes, avertissement, danger.
      titre : facultatif. Les alertes de type danger sont annoncées aux lecteurs d'écran (role="alert").
--}}
@props(['type' => 'info', 'titre' => null])

@php
    [$classes, $icone] = match ($type) {
        'succes' => ['border-success-600 bg-success-50 text-success-700', 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z'],
        'avertissement' => ['border-warning-600 bg-warning-50 text-warning-700', 'M8.3 2.9a2 2 0 013.4 0l6.1 10.6A2 2 0 0116.1 17H3.9a2 2 0 01-1.7-3l6.1-11.1zM10 7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1zm0 8a1 1 0 100-2 1 1 0 000 2z'],
        'danger' => ['border-danger-600 bg-danger-50 text-danger-700', 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 101.4 1.4l1.3-1.3 1.3 1.3a1 1 0 001.4-1.4L11.4 10l1.3-1.3a1 1 0 00-1.4-1.4L10 8.6 8.7 7.3z'],
        default => ['border-info-600 bg-info-50 text-info-700', 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z'],
    };
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-md border-l-4 px-4 py-3 text-sm {$classes}"]) }} role="{{ $type === 'danger' ? 'alert' : 'status' }}">
    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="{{ $icone }}" clip-rule="evenodd"/></svg>
    <div>
        @if ($titre)<p class="font-display font-semibold uppercase tracking-titre">{{ $titre }}</p>@endif
        <div class="text-slate-700">{{ $slot }}</div>
    </div>
</div>
