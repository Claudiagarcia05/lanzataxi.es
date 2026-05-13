/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                lanzarote: {
                    blue: "#244194",
                    yellow: "#FDD342",
                },
                neutral: {
                    soft: "#F8FAFC",
                    volcanic: "#E2E8F0",
                    slate: "#64748B",
                    dark: "#1E293B",
                },
                success: {
                    jable: "#3A7A5F",
                },
            },
        },
    },
    plugins: [],
};
