import { createPageApp } from '../dhx/mount';
import AdminGridPage from '../components/AdminGridPage.vue';
import { apiFetch } from '../dhx/api';
import { promptInput, showSuccess } from '../dhx/message';

async function showReport(row) {
    const report = await apiFetch('/admin/api/reports/' + row.id);
    const dialog = document.createElement('dialog');
    dialog.setAttribute('aria-label', '举报详情');
    dialog.style.cssText = 'margin:auto; padding:1.25rem; max-width:min(90vw, 42rem); max-height:80vh; overflow:auto;';

    const details = document.createElement('p');
    details.style.whiteSpace = 'pre-wrap';
    details.textContent = [
        '举报对象：' + report.target_type + ' #' + report.target_id,
        '帖子标题：' + (report.title ?? '无'),
        '举报原因：' + report.reason,
        '补充说明：' + (report.description ?? '无'),
        '处理理由：' + (report.resolution_note ?? '无'),
    ].join('\n');

    const content = document.createElement('pre');
    content.style.cssText = 'white-space:pre-wrap; overflow-wrap:anywhere; margin:1rem 0;';
    content.textContent = report.content ?? '被举报内容已不存在';

    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = '关闭';
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => dialog.remove(), { once: true });
    dialog.append(details, content, close);
    document.body.append(dialog);
    dialog.showModal();
}

async function resolveReport(row, reload, status, hide, title) {
    const reason = await promptInput({ title, label: '处理理由（最多 255 字）' });
    if (!reason) {
        return;
    }
    await apiFetch('/admin/api/reports/' + row.id + '/handle', {
        method: 'POST',
        body: JSON.stringify({ status, hide, reason }),
    });
    showSuccess('举报处理结果已保存');
    await reload();
}

createPageApp(AdminGridPage, {
    ...(window.__PAGE_PROPS__ || {}),
    apiUrl: '/admin/api/reports',
    activeAdminMenu: 'reports',
    gridHeader: '举报处理',
    paginated: true,
    columns: [
        { id: 'reporter', header: [{ text: '举报人' }], width: 120 },
        { id: 'reason', header: [{ text: '原因' }], width: 120 },
        { id: 'status', header: [{ text: '状态' }], width: 90, template: (value) => ['待处理', '已处理', '已驳回'][value] },
        { id: 'created_at', header: [{ text: '时间' }], width: 160 },
    ],
    actions: [
        { id: 'details', label: '查看详情', handler: showReport },
        {
            id: 'hide',
            label: '隐藏内容',
            handler: (row, reload) => resolveReport(row, reload, 1, true, '隐藏被举报内容'),
        },
        {
            id: 'handle',
            label: '结束处理',
            handler: (row, reload) => resolveReport(row, reload, 1, false, '结束处理'),
        },
        {
            id: 'ignore',
            label: '驳回',
            handler: (row, reload) => resolveReport(row, reload, 2, false, '驳回举报'),
        },
    ],
});
