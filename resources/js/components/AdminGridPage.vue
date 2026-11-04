<template>
    <AppShell
        :page-title="pageTitle"
        active-menu="admin"
        :user="user"
        :is-admin="true"
        :unread-count="unreadCount"
        @ready="initContent"
    />
</template>

<script setup>
import { onBeforeUnmount } from 'vue';
import AppShell from './AppShell.vue';
import { attachGrid, attachLayout, attachToolbar, mountAdminLayout } from '../dhx/cell';
import { loadGridData } from '../dhx/api';
import { showError } from '../dhx/message';

const props = defineProps({
    pageTitle: { type: String, default: '后台管理' },
    user: { type: Object, default: null },
    unreadCount: { type: Number, default: 0 },
    apiUrl: { type: String, required: true },
    columns: { type: Array, required: true },
    actions: { type: Array, default: () => [] },
    headerActions: { type: Array, default: () => [] },
    activeAdminMenu: { type: String, default: 'dashboard' },
    gridHeader: { type: String, default: '' },
    paginated: { type: Boolean, default: false },
});

let layout = null;
let toolbar = null;
let grid = null;
let selectedRow = null;
let page = 1;
let lastPage = 1;
let loading = false;

function initContent(contentCell) {
    const mainCell = mountAdminLayout(contentCell, props.activeAdminMenu);

    layout = attachLayout(mainCell, {
        type: 'line',
        rows: [
            { id: 'toolbar', height: 'content' },
            { id: 'grid', gravity: 1, header: props.gridHeader || props.pageTitle },
        ],
    });

    const toolbarItems = [
        ...props.headerActions.map((action) => ({
            id: action.id,
            type: 'button',
            value: action.label,
        })),
        ...props.actions.map((action) => ({
            id: action.id,
            type: 'button',
            value: action.label,
        })),
    ];

    if (props.paginated) {
        toolbarItems.push(
            { id: 'spacer', type: 'spacer' },
            { id: 'previousPage', type: 'button', value: '上一页' },
            { id: 'nextPage', type: 'button', value: '下一页' },
            { id: 'pageNumber', type: 'title', value: '第 1 页' },
        );
    }

    if (toolbarItems.length) {
        toolbar = attachToolbar(layout.getCell('toolbar'), {
            css: 'dhx_widget--bordered',
            data: toolbarItems,
        });

        toolbar.events.on('click', async (id) => {
            if (loading) {
                return;
            }
            if (id === 'previousPage' || id === 'nextPage') {
                const next = page + (id === 'nextPage' ? 1 : -1);
                if (next >= 1 && next <= lastPage) {
                    await reload(next);
                }
                return;
            }
            const headerAction = props.headerActions.find((action) => action.id === id);
            if (headerAction) {
                headerAction.handler(reload);
                return;
            }

            if (!selectedRow) {
                showError('请先选择一行');
                return;
            }

            const action = props.actions.find((item) => item.id === id);
            try {
                await action?.handler(selectedRow, reload);
            } catch (error) {
                showError(error.message);
            }
        });
    }

    grid = attachGrid(layout.getCell('grid'), {
        css: 'dhx_widget--bordered',
        selection: 'row',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: props.columns,
    });

    grid.events.on('cellClick', (row) => {
        selectedRow = row;
    });

    reload();
}

async function reload(targetPage = page) {
    loading = true;
    selectedRow = null;
    try {
        const url = props.paginated ? props.apiUrl + '?page=' + targetPage : props.apiUrl;
        const result = await loadGridData(grid, url);
        if (props.paginated) {
            lastPage = result.last_page;
            if (targetPage > lastPage) {
                await reload(lastPage);
                return;
            }
            page = targetPage;
            toolbar.data.update('pageNumber', { value: '第 ' + page + ' / ' + lastPage + ' 页' });
        }
    } catch (error) {
        grid.data.parse([]);
        showError(error.message);
    } finally {
        loading = false;
    }
}

onBeforeUnmount(() => {
    grid?.destructor();
    layout?.destructor();
});
</script>
