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
});

let layout = null;
let grid = null;
let selectedRow = null;

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

    if (toolbarItems.length) {
        const toolbar = attachToolbar(layout.getCell('toolbar'), {
            css: 'dhx_widget--bordered',
            data: toolbarItems,
        });

        toolbar.events.on('click', (id) => {
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
            action?.handler(selectedRow, reload);
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

function reload() {
    loadGridData(grid, props.apiUrl).catch(() => {
        grid.data.parse([]);
    });
}

onBeforeUnmount(() => {
    grid?.destructor();
    layout?.destructor();
});
</script>
