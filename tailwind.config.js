import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                primary: "#4A9B35",
                "primary-dark": "#2F6E25",
                secondary: "#E5A623",
                danger: "#B52A22",
            },
            fontFamily: {
                rajdhani: ["Rajdhani", "sans-serif"],
                sans: ["Inter", "sans-serif"],
            },
        },
    },

    plugins: [forms],
};
