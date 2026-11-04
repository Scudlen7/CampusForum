import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

const pages = [
    'home',
    'auth-login',
    'auth-register',
    'forum-index',
    'post-show',
    'post-create',
    'post-edit',
    'user-profile',
    'user-settings',
    'user-certification',
    'notifications',
    'admin-dashboard',
    'admin-users',
    'admin-certifications',
    'admin-posts',
    'admin-comments',
    'admin-reports',
    'admin-categories',
    'admin-banners',
];

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/css/dhx.css',
                ...pages.map((page) => `resources/js/pages/${page}.js`),
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
