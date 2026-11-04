<template>
    <AppShell
        :page-title="pageTitle"
        active-menu="notifications"
        :user="user"
        :is-admin="isAdmin"
        :unread-count="unreadCount"
        @ready="initContent"
    />
</template>

<script setup>
import { onBeforeUnmount } from 'vue';
import AppShell from './AppShell.vue';
import { attachGrid, attachLayout, attachToolbar } from '../dhx/cell';
import { apiFetch } from '../dhx/api';
import { showError } from '../dhx/message';

const props = defineProps({
    pageTitle: { type: String, default: '消息中心' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
});

let layout = null;
let grid = null;
let toolbar = null;

function initContent(contentCell) {
    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered notifications-page',
        rows: [
            { id: 'toolbar', height: 'content' },
            { id: 'grid', gravity: 1, header: '通知列表' },
        ],
    });

    toolbar = attachToolbar(layout.getCell('toolbar'), {
        css: 'dhx_widget--bordered',
        data: [
            { id: 'title', type: 'title', value: '消息中心' },
            { type: 'spacer' },
            { id: 'refresh', type: 'button', value: '刷新' },
            { id: 'markRead', type: 'button', value: '全部标记已读' },
        ],
    });

    grid = attachGrid(layout.getCell('grid'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: [
            { id: 'type_label', header: [{ text: '类型' }], width: 110 },
            { id: 'actor', header: [{ text: '来自' }], width: 120 },
            { id: 'summary', header: [{ text: '内容' }], minWidth: 240, gravity: 2 },
            { id: 'is_read_label', header: [{ text: '状态' }], width: 80 },
            { id: 'created_at', header: [{ text: '时间' }], width: 170 },
        ],
    });

    toolbar.events.on('click', (id) => {
        if (id === 'refresh') {
            loadNotifications();
            return;
        }
        if (id === 'markRead') {
            apiFetch('/api/notifications/mark-read', { method: 'POST', body: '{}' })
                .then(() => loadNotifications())
                .catch(() => showError('标记已读失败'));
        }
    });

    loadNotifications();
}

function loadNotifications() {
    apiFetch('/api/notifications')
        .then(({ data }) => {
            grid.data.parse(
                (data || []).map((item) => ({
                    ...item,
                    summary: item.data?.post_title || item.data?.content || '',
                    is_read_label: item.is_read ? '已读' : '未读',
                })),
            );
        })
        .catch(() => {
            grid.data.parse([]);
        });
}

onBeforeUnmount(() => {
    grid?.destructor();
    toolbar?.destructor();
    layout?.destructor();
});
</script>
