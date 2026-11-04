import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { showConfirm, showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/comments',
    activeAdminMenu: 'comments',
    gridHeader: '评论管理',
    columns: [
        { id: 'content', header: [{ text: '内容' }], gravity: 2 },
        { id: 'author', header: [{ text: '作者' }], width: 120 },
        { id: 'post_title', header: [{ text: '帖子' }], width: 180 },
        { id: 'status_label', header: [{ text: '状态' }], width: 90 },
        { id: 'created_at', header: [{ text: '时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'toggle_hidden',
            label: '设为隐藏',
            handler: (row, reload) => apiFetch(`/admin/api/comments/${row.id}/toggle-hidden`, {
                method: 'POST',
                body: '{}',
            }).then(() => {
                showSuccess('评论状态已更新');
                reload();
            }),
        },
        {
            id: 'delete',
            label: '删除评论',
            handler: async (row, reload) => {
                const confirmed = await showConfirm({
                    header: '删除确认',
                    text: `确定要永久删除评论「${row.content}」吗？`,
                });

                if (!confirmed) {
                    return;
                }

                await apiFetch(`/admin/api/comments/${row.id}`, { method: 'DELETE' });
                showSuccess('评论及关联数据已永久删除');
                reload();
            },
        },
    ],
});
