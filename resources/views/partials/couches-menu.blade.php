{{-- Mesure la barre supérieure (data-menu-haut) pour que fenêtres et panneaux s'ouvrent en dessous. --}}
<script>
    (() => {
        const barre = document.querySelector('[data-menu-haut]');
        if (! barre) return;
        const maj = () => document.documentElement.style.setProperty('--menu-haut', barre.offsetHeight + 'px');
        maj();
        if ('ResizeObserver' in window) new ResizeObserver(maj).observe(barre);
    })();
</script>
