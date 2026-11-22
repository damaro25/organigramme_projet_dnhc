{{-- Action destructive confirmée (suppression du compte…) : rouge plein, réservé aux confirmations. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-base bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600']) }}>
    {{ $slot }}
</button>
