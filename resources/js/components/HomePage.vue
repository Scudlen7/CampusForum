<template>
    <AppShell
        :page-title="pageTitle"
        :active-menu="activeMenu || 'home'"
        :user="user"
        :is-admin="isAdmin"
        :unread-count="unreadCount"
        @ready="initContent"
    />
</template>

<script setup>
import { onBeforeUnmount } from 'vue';
import AppShell from './AppShell.vue';
import { attachGrid, attachHtml, attachLayout, bindGridOpenPost, postGridColumns } from '../dhx/cell';
import { apiFetch, loadGridData } from '../dhx/api';

const props = defineProps({
    pageTitle: { type: String, default: '华东师范大学校园论坛' },
    activeMenu: { type: String, default: 'home' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
});

let innerLayout = null;
let pinnedGrid = null;
let hotGrid = null;

function initContent(contentCell) {
    innerLayout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered home-page',
        rows: [
            { id: 'banners', height: 'content', header: '校园公告' },
            { id: 'tags', height: 'content', header: '热门话题' },
            {
                id: 'posts',
                gravity: 1,
                cols: [
                    { id: 'pinned', width: '50%', header: '置顶帖子', resizable: true },
                    { id: 'hot', gravity: 1, header: '热门帖子' },
                ],
            },
        ],
    });

    apiFetch('/api/meta/banners')
        .then(({ data }) => {
            const items = data || [];
            if (!items.length) {
                attachHtml(innerLayout.getCell('banners'), '<div style="padding:16px;color:#888;">暂无公告</div>');
                return;
            }

            const html = items
                .map(
                    (banner) => `
                        <div style="padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                            <strong>${escapeHtml(banner.title)}</strong>
                            ${banner.content ? `<div style="color:#666;margin-top:6px;">${escapeHtml(banner.content)}</div>` : ''}
                            ${banner.link ? `<div style="margin-top:6px;"><a href="${escapeHtml(banner.link)}">查看详情</a></div>` : ''}
                        </div>
                    `,
                )
                .join('');

            attachHtml(innerLayout.getCell('banners'), html);
        })
        .catch(() => {
            attachHtml(innerLayout.getCell('banners'), '<div style="padding:16px;color:#c0392b;">公告加载失败</div>');
        });

    apiFetch('/api/meta/tags?hot=1')
        .then(({ data }) => {
            const tags = (data || [])
                .map((tag) => `#${tag.name} (${tag.post_count})`)
                .join(' · ');

            attachHtml(
                innerLayout.getCell('tags'),
                `<div style="padding:16px;">${tags || '暂无热门话题'}</div>`,
            );
        })
        .catch(() => {
            attachHtml(innerLayout.getCell('tags'), '<div style="padding:16px;color:#c0392b;">话题加载失败</div>');
        });

    pinnedGrid = attachGrid(innerLayout.getCell('pinned'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: postGridColumns,
    });
    loadGridData(pinnedGrid, '/api/posts?pinned=1').catch(() => {
        pinnedGrid.data.parse([]);
    });
    bindGridOpenPost(pinnedGrid);

    hotGrid = attachGrid(innerLayout.getCell('hot'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: postGridColumns,
    });
    loadGridData(hotGrid, '/api/posts?hot=1').catch(() => {
        hotGrid.data.parse([]);
    });
    bindGridOpenPost(hotGrid);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

onBeforeUnmount(() => {
    pinnedGrid?.destructor();
    hotGrid?.destructor();
    innerLayout?.destructor();
});
</script>
