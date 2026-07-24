import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Terminal / network-ops aesthetic: monospace everywhere.
                sans: ['JetBrains Mono', 'IBM Plex Mono', ...defaultTheme.fontFamily.mono],
                mono: ['JetBrains Mono', 'IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            animation: {
                blink: 'blink 1.1s step-end infinite',
            },
            keyframes: {
                blink: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0' },
                },
            },
        },
    },

    plugins: [forms],
};
