@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-green-200 bg-green-50 px-3 py-2 font-medium text-sm text-green-800']) }}>
        {{ $status }}
    </div>
@endif
