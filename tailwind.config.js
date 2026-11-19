import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
            // Charte graphique du Gouvernement de la République de Guinée (Cellule de
            // Communication du Gouvernement, 2021) : police Century Gothic, couleurs
            // nationales rouge, jaune, vert, avec du blanc et du gris.
            fontFamily: {
                sans: ['"Century Gothic"', 'CenturyGothic', 'AppleGothic', '"URW Gothic"', '"URW Gothic L"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Couleurs officielles du drapeau (valeurs de la charte).
                guinee: {
                    rouge: '#ce1126',
                    jaune: '#fcd116',
                    vert: '#009460',
                },
                // Fonds neutres légèrement teintés de vert : moins éblouissants que le
                // blanc pur sur de grandes surfaces (grand écran, salle de réunion),
                // tout en restant dans le blanc et le gris de la charte.
                fond: {
                    DEFAULT: '#f2f4f1', // fond des pages
                    toile: '#e7ece7',   // fond de l'arbre, sur lequel les cartes blanches ressortent
                    point: '#cbd5cd',   // trame de points de la toile
                    bord: '#d9e0da',    // bordures des cartes
                },
                // Couleur principale de l'application : le vert national (500) et ses
                // nuances foncées, la charte recommandant un fond blanc ou vert foncé.
                dnh: {
                    50: '#ebf7f1',
                    100: '#cdeedd',
                    200: '#9fdcbf',
                    500: '#009460',
                    600: '#007f52',
                    700: '#006a45',
                    800: '#005538',
                    900: '#00402a',
                },
            },
        },
    },

    plugins: [forms],
};
