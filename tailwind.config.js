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
            // Couches : les menus passent toujours au-dessus du reste de la page.
            zIndex: {
                superpose: '50',   // fenêtres, fiche de l'agent, notifications
                entete: '60',      // barre supérieure de l'administration, en-tête public
                'menu-fond': '65', // voile du menu mobile
                menu: '70',        // menu latéral
            },
            // Identité visuelle de la Direction Nationale des Hydrocarbures : bleu marine
            // du logo (#000B3D), blanc, typographie sans-serif condensée en capitales.
            // Les couleurs nationales restent disponibles pour la bande tricolore et les
            // armoiries (charte graphique du Gouvernement).
            fontFamily: {
                // Corps de texte, tableaux, formulaires.
                sans: ['Barlow', '"Century Gothic"', ...defaultTheme.fontFamily.sans],
                // Titres, navigation, boutons, libellés : condensée comme le logo.
                titre: ['"Barlow Condensed"', '"Arial Narrow"', 'Barlow', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Couleurs officielles du drapeau (valeurs de la charte).
                guinee: {
                    rouge: '#ce1126',
                    jaune: '#fcd116',
                    vert: '#009460',
                },
                // Fonds neutres légèrement bleutés, accordés au marine du logo : moins
                // éblouissants que le blanc pur sur grand écran.
                fond: {
                    DEFAULT: '#f4f6fb', // fond des pages
                    toile: '#e9edf6',   // fond de l'arbre, sur lequel les cartes blanches ressortent
                    point: '#c8d0e3',   // trame de points de la toile
                    bord: '#dbe1ee',    // bordures des cartes
                },
                // Couleur principale : le bleu marine du logo DNH (950) et son échelle.
                // Contrastes sur blanc : 500 = 8,4:1 ; 700 = 14:1 (WCAG AA et AAA).
                dnh: {
                    50: '#eef1fb',
                    100: '#d9e0f5',
                    200: '#b4c1ea',
                    300: '#8497d8',
                    400: '#5469bf',
                    500: '#2f45a3',
                    600: '#1f3289',
                    700: '#16246c',
                    800: '#0e1a52',
                    900: '#071245',
                    950: '#000b3d',
                },
                // Accent « énergie » (la flamme) : à utiliser avec parcimonie, pour
                // signaler l'élément actif, une mise en avant ou une tendance.
                energie: {
                    50: '#fff8e6',
                    100: '#fdecbf',
                    DEFAULT: '#f2a900',
                    600: '#c98a00',
                    700: '#9a6a00',
                },
            },
            boxShadow: {
                carte: '0 1px 2px rgb(0 11 61 / 0.05), 0 2px 8px rgb(0 11 61 / 0.05)',
                flottant: '0 8px 24px rgb(0 11 61 / 0.14)',
            },
        },
    },

    plugins: [forms],
};
