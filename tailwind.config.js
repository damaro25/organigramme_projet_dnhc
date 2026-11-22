import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * Identité visuelle de la Direction Nationale des Hydrocarbures (DNH).
 * Les valeurs sont aussi déclarées en variables CSS dans resources/css/tokens.css.
 *
 * - primary : bleu marine du logo (#000B3D, relevé à la pipette) et son échelle.
 *   « dnh » est un alias de « primary » : les classes dnh-* existantes suivent.
 * - accent  : ambre « flamme », à petites doses (indicateurs, CTA secondaires,
 *   graphiques). En texte sur fond blanc, utiliser accent-700 (contraste AA).
 * - guinee  : couleurs nationales, seulement pour la fine bande tricolore.
 */
const primary = {
    50: '#eef2fb',
    100: '#d9e1f5',
    200: '#b5c4eb',
    300: '#869ddb',
    400: '#5672c6',
    500: '#3552ae',
    600: '#263f95',
    700: '#1c3079',
    800: '#13235c',
    900: '#0a1747',
    950: '#000b3d',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Enums/*.php',
    ],

    // Classes construites dynamiquement dans organigramme-composant.js
    // (`oc-service--${groupe}`) : Tailwind ne peut pas les voir dans le code.
    safelist: ['oc-service--clair', 'oc-service--bleu'],

    theme: {
        extend: {
            fontFamily: {
                // Corps de texte et tableaux : Barlow, la police du logo.
                sans: ['Barlow', ...defaultTheme.fontFamily.sans],
                // Titres, navigation, boutons, libellés : Barlow Condensed.
                display: ['"Barlow Condensed"', 'Barlow', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary,
                dnh: primary,
                accent: {
                    50: '#fff8e6',
                    100: '#feecbf',
                    300: '#f9c94d',
                    500: '#f2a900',
                    600: '#d98f00',
                    700: '#b45309',
                },
                success: { 50: '#ecf7f1', 100: '#d3ecdf', 600: '#1e7b4f', 700: '#17633f' },
                warning: { 50: '#fdf6e7', 100: '#fae7c0', 600: '#b45309', 700: '#92400e' },
                danger: { 50: '#fcedee', 100: '#f8d5d9', 600: '#b42335', 700: '#931c2b' },
                info: { 50: '#eef2fb', 100: '#d9e1f5', 600: '#2f5bb7', 700: '#24478f' },
                guinee: { rouge: '#ce1126', jaune: '#fcd116', vert: '#009460' },
                // Fonds neutres légèrement bleutés : le blanc pur reste réservé aux cartes.
                fond: {
                    DEFAULT: '#f5f7fb', // fond des pages
                    toile: '#ecf0f7',   // fond de l'arbre, sur lequel les cartes blanches ressortent
                    point: '#c9d2e3',   // trame de points de la toile
                    bord: '#dde3ee',    // bordures des cartes
                },
            },
            // Formes institutionnelles : rayons modérés (4 à 8 px).
            borderRadius: {
                lg: '0.375rem',
                xl: '0.5rem',
                '2xl': '0.5rem',
            },
            // Ombres légères teintées de marine.
            boxShadow: {
                sm: '0 1px 2px rgb(1 10 58 / 0.06)',
                DEFAULT: '0 1px 3px rgb(1 10 58 / 0.08), 0 1px 2px rgb(1 10 58 / 0.04)',
                md: '0 4px 8px -2px rgb(1 10 58 / 0.08), 0 2px 4px -2px rgb(1 10 58 / 0.05)',
                lg: '0 10px 20px -4px rgb(1 10 58 / 0.10), 0 4px 8px -4px rgb(1 10 58 / 0.06)',
                xl: '0 18px 32px -8px rgb(1 10 58 / 0.14)',
                '2xl': '0 24px 48px -12px rgb(1 10 58 / 0.22)',
            },
            letterSpacing: {
                titre: '0.04em',
            },
        },
    },

    plugins: [forms],
};
