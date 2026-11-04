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
    attachHtml,
    attachLayout,
    attachList,
    attachToolbar,
    bindGridOpenPost,
    forumGridColumns,
} from '../dhx/cell';
import { apiFetch } from '../dhx/api';
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
                    { id: 'filters', height: 'content' },
                    { id: 'grid', gravity: 1, header: props.categoryName || '帖子列表' },
                    { id: 'pagination', height: 'content' },
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

    attachHtml(layout.getCell('filters'), `
        <form id="forum-search" class="forum-list-controls" method="get">
            <label>标题 <input name="q" maxlength="128" type="search" placeholder="搜索帖子标题"></label>
            <label>标签 <input name="tag" maxlength="32" type="search" placeholder="输入完整标签"></label>
            <button type="submit">搜索</button>
        </form>
    `);
    attachHtml(layout.getCell('pagination'), `
        <nav class="forum-list-controls" aria-label="帖子分页">
            <a id="forum-prev" hidden>上一页</a>
            <span id="forum-page-status" role="status">正在加载帖子…</span>
            <a id="forum-next" hidden>下一页</a>
        </nav>
    `);
    requestAnimationFrame(() => requestAnimationFrame(() => {
        const form = document.getElementById('forum-search');
        if (!form) {
            return;
        }
        const params = new URLSearchParams(window.location.search);
        form.elements.q.value = params.get('q') ?? '';
        form.elements.tag.value = params.get('tag') ?? '';
        reloadPosts(params);
    }));
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

async function reloadPosts(params = new URLSearchParams(window.location.search)) {
    const filters = new URLSearchParams();
    for (const key of ['q', 'tag', 'page']) {
        if (params.get(key)) {
            filters.set(key, params.get(key));
        }
    }
    const query = new URLSearchParams(filters);
    if (props.categorySlug) {
        query.set('category', props.categorySlug);
    }

    const status = document.getElementById('forum-page-status');
    if (!status) {
        return;
    }
    try {
        const result = await apiFetch('/api/posts?' + query);
        if (!grid) {
            return;
        }
        if (result.current_page > result.last_page) {
            filters.set('page', result.last_page);
            window.location.replace('?' + filters);
            return;
        }
        grid.data.parse(result.data);
        status.textContent = result.total
            ? '第 ' + result.current_page + ' / ' + result.last_page + ' 页，共 ' + result.total + ' 条'
            : '没有符合条件的帖子';

        for (const [id, target, available] of [
            ['forum-prev', result.current_page - 1, result.current_page > 1],
            ['forum-next', result.current_page + 1, result.current_page < result.last_page],
        ]) {
            const link = document.getElementById(id);
            filters.set('page', target);
            link.href = '?' + filters;
            link.hidden = !available;
        }
    } catch (error) {
        grid?.data.parse([]);
        status.textContent = '帖子列表加载失败';
        showError(error.message);
    }
}

onBeforeUnmount(() => {
    categoryList?.destructor();
    grid?.destructor();
    grid = null;
    toolbar?.destructor();
    layout?.destructor();
});
</script>

<style>
.forum-list-controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
}

.forum-list-controls input,
.forum-list-controls button {
    padding: 0.25rem 0.5rem;
    border: 1px solid currentColor;
    border-radius: 0.25rem;
}

.forum-list-controls a {
    text-decoration: underline;
}
</style>
