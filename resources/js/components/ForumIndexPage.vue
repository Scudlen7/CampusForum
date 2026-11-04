<template>
    <AppShell
        :page-title="pageTitle"
        active-menu="forum"
        :user="user"
        :is-admin="isAdmin"
        :unread-count="unreadCount"
        @ready="initContent"
    />
</template>

<script setup>
import { onBeforeUnmount } from 'vue';
import AppShell from './AppShell.vue';
import {
    attachGrid,
    attachLayout,
    attachList,
    attachToolbar,
    bindGridOpenPost,
    forumGridColumns,
} from '../dhx/cell';
import { apiFetch, loadGridData } from '../dhx/api';
import { showError } from '../dhx/message';

const props = defineProps({
    pageTitle: { type: String, default: '论坛' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    categorySlug: { type: String, default: null },
    categoryName: { type: String, default: null },
});

let layout = null;
let grid = null;
let toolbar = null;
let categoryList = null;

function initContent(contentCell) {
    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered forum-page',
        cols: [
            { id: 'categories', width: 220, header: '论坛分类', resizable: true },
            {
                id: 'main',
                gravity: 1,
                rows: [
                    { id: 'toolbar', height: 'content' },
                    { id: 'grid', gravity: 1, header: props.categoryName || '帖子列表' },
                ],
            },
        ],
    });

    categoryList = attachList(layout.getCell('categories'), {
        css: 'dhx_widget--bordered forum-category-list',
        selection: true,
        template: (item) => `${item.icon || ''} ${item.name}`,
        data: [],
    });

    categoryList.events.on('click', (id) => {
        navigateCategory(String(id));
    });

    apiFetch('/api/meta/categories')
        .then(({ data }) => {
            const items = [{ id: 'all', name: '全部帖子', icon: '📋' }];

            (data || []).forEach((category) => {
                items.push({
                    id: category.slug,
                    name: category.name,
                    icon: category.icon || '📁',
                });
            });

            categoryList.data.parse(items);

            if (props.categorySlug) {
                categoryList.selection.add(props.categorySlug);
            } else {
                categoryList.selection.add('all');
            }
        })
        .catch(() => {
            categoryList.data.parse([{ id: 'all', name: '全部帖子', icon: '📋' }]);
            categoryList.selection.add('all');
        });

    const toolbarItems = [
        { id: 'title', type: 'title', value: props.categoryName || '全部帖子' },
        { type: 'spacer' },
    ];

    if (props.user) {
        toolbarItems.push({ id: 'create', type: 'button', value: '发帖' });
    }

    toolbar = attachToolbar(layout.getCell('toolbar'), {
        css: 'dhx_widget--bordered',
        data: toolbarItems,
    });

    toolbar.events.on('click', (id) => {
        if (id === 'create') {
            window.location.href = '/posts/create';
        }
    });

    grid = attachGrid(layout.getCell('grid'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: forumGridColumns,
    });

    reloadPosts();
    bindGridOpenPost(grid);
}

function navigateCategory(id) {
    if (id === 'all') {
        if (!props.categorySlug) {
            reloadPosts();
            return;
        }
        window.location.href = '/forum';
        return;
    }

    if (props.categorySlug === id) {
        reloadPosts();
        return;
    }

    window.location.href = `/forum/${id}`;
}

function reloadPosts() {
    const url = props.categorySlug
        ? `/api/posts?category=${encodeURIComponent(props.categorySlug)}`
        : '/api/posts';

    loadGridData(grid, url).catch(() => {
        grid.data.parse([]);
        showError('帖子列表加载失败');
    });
}

onBeforeUnmount(() => {
    categoryList?.destructor();
    grid?.destructor();
    toolbar?.destructor();
    layout?.destructor();
});
</script>
