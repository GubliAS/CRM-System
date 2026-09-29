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
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    hover: 'var(--color-primary-hover)',
                    soft: 'var(--color-primary-soft)',
                },
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                    hover: 'var(--color-secondary-hover)',
                },
                bg: 'var(--color-bg)',
                surface: 'var(--color-surface)',
                text: {
                    DEFAULT: 'var(--color-text)',
                    muted: 'var(--color-text-muted)',
                },
                border: 'var(--color-border)',
                success: {
                    DEFAULT: 'var(--color-success)',
                    soft: 'var(--color-success-soft)',
                },
                danger: {
                    DEFAULT: 'var(--color-danger)',
                    soft: 'var(--color-danger-soft)',
                },
                warning: {
                    DEFAULT: 'var(--color-warning)',
                    soft: 'var(--color-warning-soft)',
                },
                info: {
                    DEFAULT: 'var(--color-info)',
                    soft: 'var(--color-info-soft)',
                },
                overlay: 'var(--color-overlay)',
                'on-primary': {
                    DEFAULT: 'var(--color-on-primary)',
                    muted: 'var(--color-on-primary-muted)',
                },
                sidebar: {
                    DEFAULT: 'var(--color-sidebar)',
                    elevated: 'var(--color-sidebar-elevated)',
                    hover: 'var(--color-sidebar-hover)',
                    'active-bg': 'var(--color-sidebar-active-bg)',
                    'active-fg': 'var(--color-sidebar-active-fg)',
                },
                'on-sidebar': {
                    DEFAULT: 'var(--color-on-sidebar)',
                    muted: 'var(--color-on-sidebar-muted)',
                },
            },
            fontFamily: {
                sans: 'var(--font-sans)',
            },
            fontSize: {
                h1: ['var(--text-h1)', { lineHeight: '1.3', fontWeight: '700' }],
                h2: ['var(--text-h2)', { lineHeight: '1.35', fontWeight: '600' }],
                h3: ['var(--text-h3)', { lineHeight: '1.4', fontWeight: '600' }],
                body: ['var(--text-body)', { lineHeight: 'var(--leading-body)' }],
                small: ['var(--text-small)', { lineHeight: '1.45' }],
            },
            boxShadow: {
                panel: 'var(--shadow-panel)',
                dropdown: 'var(--shadow-dropdown)',
                card: 'var(--shadow-card)',
                sidebar: 'var(--shadow-sidebar)',
            },
            borderRadius: {
                sm: 'var(--radius-sm)',
                md: 'var(--radius-md)',
                lg: 'var(--radius-lg)',
                xl: 'var(--radius-xl)',
                '2xl': 'var(--radius-2xl)',
                card: 'var(--radius-card)',
                sidebar: 'var(--radius-sidebar)',
                'sidebar-inner': 'var(--radius-sidebar-inner)',
                'sidebar-chip': 'var(--radius-sidebar-chip)',
            },
            transitionDuration: {
                fast: '150ms',
                sidebar: '240ms',
            },
            width: {
                'sidebar-collapsed': 'var(--sidebar-width-collapsed)',
                sidebar: 'var(--sidebar-width)',
            },
            spacing: {
                header: 'var(--header-height)',
                'sidebar-collapsed': 'var(--sidebar-width-collapsed)',
                sidebar: 'var(--sidebar-width)',
                'sidebar-inset': 'var(--sidebar-inset)',
                'sidebar-gap': 'var(--sidebar-gap)',
            },
        },
    },

    plugins: [forms],
};
