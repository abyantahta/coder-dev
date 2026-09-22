import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                canvas: {
                    DEFAULT: '#E7EDF4',
                    soft: '#F3F6FA',
                },
                surface: '#FFFFFF',
                ink: {
                    DEFAULT: '#0F2137',
                    soft: '#243B53',
                    muted: '#627D98',
                    faint: '#9FB3C8',
                },
                brand: {
                    DEFAULT: '#0B6E4F',
                    deep: '#084C37',
                    bright: '#149E72',
                    muted: '#E4F3ED',
                    line: '#B5D6C9',
                },
                copper: {
                    DEFAULT: '#C45C26',
                    soft: '#E07A3D',
                    muted: '#FDF1EA',
                    line: '#F0C9B0',
                },
                line: {
                    DEFAULT: '#D5DEE8',
                    strong: '#B8C5D4',
                },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Sora', '"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'display-sm': ['1.5rem', { lineHeight: '1.25', letterSpacing: '-0.02em', fontWeight: '600' }],
                'display': ['1.875rem', { lineHeight: '1.2', letterSpacing: '-0.025em', fontWeight: '700' }],
            },
            spacing: {
                18: '4.5rem',
                22: '5.5rem',
            },
            borderRadius: {
                panel: '0.75rem',
            },
            boxShadow: {
                panel: '0 1px 0 rgba(15, 33, 55, 0.04), 0 8px 24px -12px rgba(15, 33, 55, 0.18)',
                lift: '0 12px 28px -14px rgba(15, 33, 55, 0.28)',
            },
            transitionTimingFunction: {
                matex: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            transitionDuration: {
                220: '220ms',
                320: '320ms',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'flash-in': {
                    '0%': { opacity: '0', transform: 'translateY(-6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 320ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 220ms ease-out both',
                'flash-in': 'flash-in 280ms cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms],
};
