/** @type {import('tailwindcss').Config} */
export default {
    prefix: 'fl-',
    important: true,
    content: [
        './resources/views/**/*.blade.php',
        './src/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
    corePlugins: {
        preflight: false,
    }
}
