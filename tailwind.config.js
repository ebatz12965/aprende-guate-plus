import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Familia Azul Lago (ver DESIGN.md). Reemplaza las escalas `blue` y `sky` de Tailwind para que
 * todas las clases existentes usen el único azul de identidad. Cada tono conserva el papel que
 * tenía en Tailwind, por eso se asigna por contraste y no por la numeración de Material:
 * 600 lleva texto blanco (4.60:1) y 700 es el tono de texto sobre fondos claros (5.75:1).
 */
const azulLago = {
    50: '#E3F2FD',
    100: '#D3EAFC',
    200: '#90CAF9',
    300: '#64B5F6',
    400: '#42A5F5',
    500: '#2196F3',
    600: '#1976D2',
    700: '#1565C0',
    800: '#0D47A1',
    900: '#0B3A86',
    950: '#072859',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                blue: azulLago,
                sky: azulLago,
                brand: {
                    DEFAULT: '#2196F3',
                    text: '#81D4FA',
                    white: '#8DFFFF',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms],
};
