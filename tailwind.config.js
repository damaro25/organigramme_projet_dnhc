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

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Tons bleus de l'organigramme papier.
                dnh: {
                    50: '#eef4ff',
                    100: '#dbe7fe',
                    200: '#bcd3fc',
                    500: '#2f6fe0',
                    600: '#1f5bd1',
                    700: '#1747a8',
                    800: '#173a85',
                    900: '#152f66',
                },
            },
        },
    },

    plugins: [forms],
};
