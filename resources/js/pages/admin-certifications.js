import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { promptInput, showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/certifications',
    activeAdminMenu: 'certifications',
    gridHeader: '认证审核',
    paginated: true,
    columns: [
        { id: 'username', header: [{ text: '用户' }], width: 120 },
        { id: 'student_no', header: [{ text: '学号' }], width: 120 },
        { id: 'status', header: [{ text: '状态' }], width: 90, template: (value) => ['待审核', '已通过', '已拒绝'][value] },
        { id: 'created_at', header: [{ text: '提交时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'image',
            label: '查看认证材料',
            handler: (row) => window.open('/admin/api/certifications/' + row.id + '/image', '_blank', 'noopener'),
        },
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
