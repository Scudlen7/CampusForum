import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { showConfirm, showSuccess } from '../dhx/message';

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/posts',
    activeAdminMenu: 'posts',
    gridHeader: '帖子管理',
    columns: [
        { id: 'title', header: [{ text: '标题' }], gravity: 2 },
        { id: 'author', header: [{ text: '作者' }], width: 120 },
        { id: 'is_hidden', header: [{ text: '隐藏' }], width: 70 },
        { id: 'is_pinned', header: [{ text: '置顶' }], width: 70 },
        { id: 'created_at', header: [{ text: '时间' }], width: 160 },
    ],
    actions: [
        {
            id: 'toggle_hidden',
            label: '设为隐藏',
            handler: (row, reload) => apiFetch(`/admin/api/posts/${row.id}/toggle-hidden`, { method: 'POST', body: '{}' }).then(() => {
                showSuccess('帖子隐藏状态已更新');
                reload();
            }),
        },
        {
            id: 'toggle_pinned',
            label: '设为置顶',
            handler: (row, reload) => apiFetch(`/admin/api/posts/${row.id}/toggle-pinned`, { method: 'POST', body: '{}' }).then(() => {
                showSuccess('帖子置顶状态已更新');
                reload();
            }),
        },
        {
            id: 'delete',
            label: '删除帖子',
            handler: async (row, reload) => {
                const confirmed = await showConfirm({
                    header: '删除确认',
                    text: `确定要永久删除帖子「${row.title}」吗？`,
                });

                if (!confirmed) {
                    return;
                }

                await apiFetch(`/admin/api/posts/${row.id}`, { method: 'DELETE' });
                showSuccess('帖子及关联数据已永久删除');
                reload();
            },
        },
    ],
});
