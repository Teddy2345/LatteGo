import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/**/*.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Presentation/Web/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Paleta compartida con la app Android (ver Brand.kt): el portal y
            // el telefono deben verse como el mismo producto.
            colors: {
                bosque: '#173E32',
                campo: '#356B50',
                salvia: '#E7EEE3',
                crema: '#F7F7F0',
                piedra: '#68746A',
                dorado: '#E4C58A',
                linea: '#DFE5DA',
            },
        },
    },
    plugins: [],
};
