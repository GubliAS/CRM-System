import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: 'var(--color-primary)',
                secondary: 'var(--color-secondary)',
                bg: 'var(--color-bg)',
                surface: 'var(--color-surface)',
                text: {
                    DEFAULT: 'var(--color-text)',
                    muted: 'var(--color-text-muted)',
                },
                border: 'var(--color-border)',
                success: 'var(--color-success)',
                danger: 'var(--color-danger)',
                warning: 'var(--color-warning)',
                info: 'var(--color-info)',
            },
            fontFamily: {
                sans: 'var(--font-sans)',
            },
            fontSize: {
                h1: ['var(--text-h1)', { lineHeight: '1.3' }],
                h2: ['var(--text-h2)', { lineHeight: '1.35' }],
                h3: ['var(--text-h3)', { lineHeight: '1.4' }],
                body: ['var(--text-body)', { lineHeight: 'var(--leading-body)' }],
                small: ['var(--text-small)', { lineHeight: '1.45' }],
            },
        },
    },

    plugins: [forms],
};
