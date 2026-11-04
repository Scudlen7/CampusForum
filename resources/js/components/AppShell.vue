<template>
    <div ref="root" class="dhx-app-shell"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Layout } from 'dhx-suite';
import { attachToolbar } from '../dhx/cell';
import { apiFetch } from '../dhx/api';

const props = defineProps({
    pageTitle: { type: String, default: '华东师范大学校园论坛' },
    activeMenu: { type: String, default: 'home' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
});

const emit = defineEmits(['ready', 'menuClick']);

const root = ref(null);
let layout = null;
let toolbar = null;
let pollTimer = null;

const menuItems = [
    { id: 'home', value: '首页', icon: 'dxi dxi-home' },
    { id: 'forum', value: '论坛', icon: 'dxi dxi-view-grid' },
    { id: 'notifications', value: '消息中心', icon: 'dxi dxi-bell' },
    { id: 'profile', value: '用户中心', icon: 'dxi dxi-account' },
];

const routes = {
    home: '/',
    forum: '/forum',
    notifications: '/notifications',
    profile: '/user/settings',
    admin: '/admin',
    login: '/login',
    register: '/register',
    logout: '/logout',
};

onMounted(() => {
    layout = new Layout(root.value, {
        type: 'line',
        css: 'dhx_widget--bordered app-shell',
        rows: [
            { id: 'toolbar', height: 'content' },
            { id: 'content', gravity: 1 },
        ],
    });

    const toolbarItems = [
        { id: 'title', type: 'title', value: props.pageTitle },
        { type: 'separator' },
        ...menuItems.map((item) => ({
            id: item.id,
            type: 'navItem',
            value: item.value,
            icon: item.icon,
            active: item.id === props.activeMenu,
        })),
        { type: 'spacer' },
    ];

    if (props.user) {
        toolbarItems.push({
            id: 'user',
            type: 'text',
            value: props.user.nickname || props.user.username,
        });
        if (props.unreadCount > 0) {
            toolbarItems.push({
                id: 'unread',
                type: 'text',
                value: `未读 ${props.unreadCount}`,
            });
        }
        toolbarItems.push({ id: 'logout', type: 'button', value: '退出' });
    } else {
        toolbarItems.push({ id: 'login', type: 'button', value: '登录' });
        toolbarItems.push({ id: 'register', type: 'button', value: '注册' });
    }

    if (props.isAdmin) {
        toolbarItems.push({ id: 'admin', type: 'button', value: '后台' });
    }

    toolbar = attachToolbar(layout.getCell('toolbar'), {
        css: 'dhx_widget--bordered app-shell-toolbar',
        navigationType: 'click',
        data: toolbarItems,
    });

    toolbar.events.on('click', (id) => {
        if (routes[id]) {
            if (id === 'logout') {
                submitLogout();
                return;
            }
            window.location.href = routes[id];
        }
        emit('menuClick', id);
    });

    emit('ready', layout.getCell('content'));

    if (props.user) {
        pollTimer = setInterval(async () => {
            try {
                const { count } = await apiFetch('/api/notifications/unread-count');
                const unreadItem = toolbar.data.getItem('unread');
                if (count > 0) {
                    if (unreadItem) {
                        toolbar.data.update('unread', { value: `未读 ${count}` });
                    } else {
                        toolbar.data.add({ id: 'unread', type: 'text', value: `未读 ${count}` }, 2);
                    }
                } else if (unreadItem) {
                    toolbar.data.remove('unread');
                }
            } catch (e) {
                // ignore polling errors
            }
        }, 30000);
    }
});

function submitLogout() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = routes.logout;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrf) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrf;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}

onBeforeUnmount(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
    toolbar?.destructor();
    layout?.destructor();
});
</script>
