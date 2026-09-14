import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                erp: {
                    dark: '#171c26',
                    'card-dark': '#202736',
                    light: '#f7f9fc',
                    'card-light': '#ffffff',
                    border: {
                        dark: '#2d3748',
                        light: '#edf2f7',
                    },
                    accent: {
                        DEFAULT: '#6366f1', // Indigo
                        dark: '#818cf8',
                    }
                }
            }
        },
    },

    plugins: [forms],
};
