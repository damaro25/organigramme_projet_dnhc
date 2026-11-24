// Animations de l'organigramme (framer-motion, version DOM sans React).
import { animate, stagger } from 'framer-motion/dom';

const EASE_SORTIE = [0.22, 1, 0.36, 1];
const enCours = new WeakMap();

// Respecte le réglage « réduire les animations » du système.
const reduit = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function lancer(el, ...args) {
    enCours.get(el)?.stop();
    const controle = animate(el, ...args);
    enCours.set(el, controle);
    return controle;
}

/* ---------- Nœuds de l'arbre ---------- */

export function apparitionNoeud(el, delai = 0) {
    if (!el || reduit()) return;
    el.style.transformOrigin = '50% 50%';
    animate(el, { opacity: [0, 1], scale: [0.92, 1] }, { duration: 0.35, delay: delai, ease: EASE_SORTIE });
}

/* ---------- Panneau latéral ---------- */

export function ouvrirPanneau(el) {
    if (!el) return;
    el.style.visibility = 'visible';
    if (reduit()) { el.style.transform = 'none'; return; }
    lancer(el, { x: ['100%', '0%'] }, { type: 'spring', stiffness: 380, damping: 36, mass: 0.9 });
}

export function fermerPanneau(el, estOuvert) {
    if (!el) return;
    const masquer = () => { if (!estOuvert()) el.style.visibility = 'hidden'; };
    if (reduit()) { el.style.transform = 'translateX(100%)'; masquer(); return; }
    lancer(el, { x: '100%' }, { duration: 0.18, ease: 'easeIn' }).then(masquer);
}

// Cascade sur les blocs marqués data-revele et remplissage des barres data-barre.
export function revelerContenu(racine) {
    if (!racine || reduit()) return;
    const blocs = racine.querySelectorAll('[data-revele]');
    if (blocs.length) {
        animate(blocs, { opacity: [0, 1], y: [12, 0] }, { duration: 0.3, delay: stagger(0.05), ease: EASE_SORTIE });
    }
    racine.querySelectorAll('[data-barre]').forEach((barre, i) => {
        const cible = barre.style.width || '0%';
        animate(barre, { width: ['0%', cible] }, { duration: 0.7, delay: 0.15 + i * 0.05, ease: EASE_SORTIE });
    });
}
