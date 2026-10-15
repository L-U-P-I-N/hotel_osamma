/** @type {import('tailwindcss').Config} */

/*
 * نُقل هذا الإعداد حرفياً من وسم <script> في layouts/app.blade.php حيث كان
 * يُمرَّر إلى tailwind من CDN. فالبناء المحلي يجب أن يُخرج نفس الأصناف
 * بالضبط، وإلا تغيّرت ألوان النظام أو خطّه بلا أن يطلب أحد ذلك.
 *
 * ولا نضبط theme.fontFamily.sans هنا (كان Figtree في إعداد Laravel
 * الافتراضي): إعداد الـCDN لم يضبطه، والعربية تعتمد على Cairo المحدَّد في
 * app-theme.css — فضبطه كان سيُغيّر خطّ النظام كله.
 */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: { cairo: ['Cairo', 'sans-serif'] },
            colors: {
                primary: {
                    DEFAULT: '#0F4C75',
                    50: '#e8f0f7', 100: '#c5d8ea', 200: '#9fbedd', 300: '#79a4cf',
                    400: '#5b90c5', 500: '#3d7cbb', 600: '#2d6aab', 700: '#1e578f',
                    800: '#0F4C75', 900: '#0a3555',
                },
                accent: {
                    DEFAULT: '#D4A574',
                    50: '#fdf6ee', 100: '#f9e8d2', 200: '#f3d5b0', 300: '#ecc18e',
                    400: '#e6ae6c', 500: '#D4A574', 600: '#c08d5a', 700: '#a67040',
                    800: '#8c5530', 900: '#6e3f22',
                },
            },
        },
    },

    plugins: [],
};
