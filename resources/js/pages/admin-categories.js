import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { openEntityForm } from '../dhx/adminForm';
import { showSuccess } from '../dhx/message';

const categoryFields = [
    { type: 'input', name: 'name', label: '名称', labelPosition: 'top', required: true },
    { type: 'input', name: 'slug', label: 'Slug', labelPosition: 'top', required: true },
    { type: 'input', name: 'icon', label: '图标', labelPosition: 'top', placeholder: '例如 📁' },
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
    apiUrl: '/admin/api/categories',
    activeAdminMenu: 'categories',
    gridHeader: '论坛分类',
    columns: [
        { id: 'name', header: [{ text: '名称' }], width: 120 },
        { id: 'slug', header: [{ text: 'Slug' }], width: 140 },
        { id: 'sort_order', header: [{ text: '排序' }], width: 80 },
        { id: 'is_active', header: [{ text: '启用' }], width: 80 },
    ],
    headerActions: [
        {
            id: 'create',
            label: '新增分类',
            handler: async (reload) => {
                const values = await openEntityForm({
                    title: '新增分类',
                    rows: categoryFields,
                });
                if (!values) {
                    return;
                }
                await apiFetch('/admin/api/categories', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: values.name,
                        slug: values.slug,
                        icon: values.icon,
                        sort_order: Number(values.sort_order || 0),
                        is_active: !!values.is_active,
                    }),
                });
                showSuccess('分类已创建');
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
                    title: '编辑分类',
                    rows: categoryFields,
                    values: {
                        name: row.name,
                        slug: row.slug,
                        icon: row.icon,
                        sort_order: row.sort_order,
                        is_active: row.is_active,
                    },
                });
                if (!values) {
                    return;
                }
                await apiFetch(`/admin/api/categories/${row.id}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        name: values.name,
                        slug: values.slug,
                        icon: values.icon,
                        sort_order: Number(values.sort_order || 0),
                        is_active: !!values.is_active,
                    }),
                });
                showSuccess('分类已更新');
                reload();
            },
        },
    ],
});
