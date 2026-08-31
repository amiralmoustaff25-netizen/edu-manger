import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/charts/payments-chart.js',
                'resources/js/cahier-textes.js',
            ],
            refresh: true,
        }),
    ],
    // Force l'IPv4 : sans ceci, Node résout "localhost" en ::1 sur cette machine et
    // public/hot enregistre une URL en http://[::1]:5173 — une syntaxe que les
    // navigateurs rejettent dans un en-tête Content-Security-Policy ("invalid source",
    // ignorée), ce qui cassait le rechargement à chaud même une fois l'origine ajoutée
    // à la CSP (voir AddSecurityHeaders).
    server: {
        host: '127.0.0.1',
    },
});
