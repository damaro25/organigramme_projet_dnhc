@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-success-100 bg-success-50 px-3 py-2 text-sm font-medium text-success-700']) }}>
        {{ $status }}
    </div>
@endif
