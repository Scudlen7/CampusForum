import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/users',
    activeAdminMenu: 'users',
    gridHeader: '用户管理',
    columns: [
        { id: 'username', header: [{ text: '用户名' }], width: 120 },
        { id: 'email', header: [{ text: '邮箱' }], gravity: 1 },
        { id: 'is_banned', header: [{ text: '封禁' }], width: 80 },
        { id: 'is_certified', header: [{ text: '认证' }], width: 80 },
        { id: 'created_at', header: [{ text: '注册时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'ban',
            label: '封禁',
            handler: (row, reload) => apiFetch(`/admin/api/users/${row.id}/ban`, { method: 'POST', body: '{}' }).then(() => {
                showSuccess('用户已封禁');
                reload();
            }),
        },
        {
            id: 'unban',
            label: '解封',
            handler: (row, reload) => apiFetch(`/admin/api/users/${row.id}/unban`, { method: 'POST', body: '{}' }).then(() => {
                showSuccess('用户已解封');
                reload();
            }),
        },
    ],
});
