import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/reports',
    activeAdminMenu: 'reports',
    gridHeader: '举报处理',
    columns: [
        { id: 'reporter', header: [{ text: '举报人' }], width: 120 },
        { id: 'reason', header: [{ text: '原因' }], width: 120 },
        { id: 'status', header: [{ text: '状态' }], width: 80 },
        { id: 'created_at', header: [{ text: '时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'handle',
            label: '标记已处理',
            handler: (row, reload) => apiFetch(`/admin/api/reports/${row.id}/handle`, {
                method: 'POST',
                body: JSON.stringify({ status: 1 }),
            }).then(() => {
                showSuccess('举报已处理');
                reload();
            }),
        },
        {
            id: 'ignore',
            label: '忽略',
            handler: (row, reload) => apiFetch(`/admin/api/reports/${row.id}/handle`, {
                method: 'POST',
                body: JSON.stringify({ status: 2 }),
            }).then(() => {
                showSuccess('举报已忽略');
                reload();
            }),
        },
    ],
});
