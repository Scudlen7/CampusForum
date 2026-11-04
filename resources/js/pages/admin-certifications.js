import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { promptInput, showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/certifications',
    activeAdminMenu: 'certifications',
    gridHeader: '认证审核',
    columns: [
        { id: 'username', header: [{ text: '用户' }], width: 120 },
        { id: 'student_no', header: [{ text: '学号' }], width: 120 },
        { id: 'status', header: [{ text: '状态' }], width: 80 },
        { id: 'created_at', header: [{ text: '提交时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'approve',
            label: '通过',
            handler: (row, reload) => apiFetch(`/admin/api/certifications/${row.id}/approve`, { method: 'POST', body: '{}' }).then(() => {
                showSuccess('认证已通过');
                reload();
            }),
        },
        {
            id: 'reject',
            label: '拒绝',
            handler: async (row, reload) => {
                const reason = await promptInput({
                    title: '拒绝认证',
                    label: '拒绝原因',
                });
                if (!reason) {
                    return;
                }
                await apiFetch(`/admin/api/certifications/${row.id}/reject`, {
                    method: 'POST',
                    body: JSON.stringify({ reject_reason: reason }),
                });
                showSuccess('认证已拒绝');
                reload();
            },
        },
    ],
});
