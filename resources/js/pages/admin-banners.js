import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { openEntityForm } from '../dhx/adminForm';
import { showSuccess } from '../dhx/message';

const bannerFields = [
    { type: 'input', name: 'title', label: '标题', labelPosition: 'top', required: true },
    { type: 'textarea', name: 'content', label: '内容', labelPosition: 'top' },
    { type: 'input', name: 'link', label: '链接', labelPosition: 'top' },
    {
        type: 'select',
        name: 'type',
        label: '类型',
        labelPosition: 'top',
        options: [
            { value: 0, content: '公告' },
            { value: 1, content: '比赛通知' },
        ],
    },
    { type: 'input', name: 'sort_order', label: '排序', labelPosition: 'top', inputType: 'number' },
    {
        type: 'checkbox',
        name: 'is_active',
        label: '启用',
        checked: true,
    },
];

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/banners',
    activeAdminMenu: 'banners',
    gridHeader: 'Banner 公告',
    columns: [
        { id: 'title', header: [{ text: '标题' }], gravity: 1 },
        { id: 'type', header: [{ text: '类型' }], width: 80 },
        { id: 'is_active', header: [{ text: '启用' }], width: 80 },
        { id: 'sort_order', header: [{ text: '排序' }], width: 80 },
    ],
    headerActions: [
        {
            id: 'create',
            label: '新增 Banner',
            handler: async (reload) => {
                const values = await openEntityForm({
                    title: '新增 Banner',
                    rows: bannerFields,
                });
                if (!values) {
                    return;
                }
                await apiFetch('/admin/api/banners', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: values.title,
                        content: values.content,
                        link: values.link,
                        type: Number(values.type || 0),
                        sort_order: Number(values.sort_order || 0),
                        is_active: !!values.is_active,
                    }),
                });
                showSuccess('Banner 已创建');
                reload();
            },
        },
    ],
    actions: [
        {
            id: 'edit',
            label: '编辑',
            handler: async (row, reload) => {
                const values = await openEntityForm({
                    title: '编辑 Banner',
                    rows: bannerFields,
                    values: {
                        title: row.title,
                        content: row.content,
                        link: row.link,
                        type: row.type,
                        sort_order: row.sort_order,
                        is_active: row.is_active,
                    },
                });
                if (!values) {
                    return;
                }
                await apiFetch(`/admin/api/banners/${row.id}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        title: values.title,
                        content: values.content,
                        link: values.link,
                        type: Number(values.type || 0),
                        sort_order: Number(values.sort_order || 0),
                        is_active: !!values.is_active,
                    }),
                });
                showSuccess('Banner 已更新');
                reload();
            },
        },
    ],
});
