{{-- Bouton principal (Breeze) : s'appuie sur le style .btn-primaire de l'identité DNH. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primaire']) }}>
    {{ $slot }}
</button>
