<template>
    <AppShell
        :page-title="pageTitle"
        active-menu="profile"
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
    attachForm,
    attachGrid,
    attachLayout,
    bindGridOpenPost,
    mountUserCenterNav,
    postGridColumns,
} from '../dhx/cell';
import { loadGridData } from '../dhx/api';

const props = defineProps({
    pageTitle: { type: String, default: '个人主页' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    profile: { type: Object, required: true },
});

let layout = null;
let profileForm = null;
let postsGrid = null;
let nav = null;

function initContent(contentCell) {
    const certified = props.profile.is_certified ? ' ✓ 已认证学生' : '';
    const username = props.profile.username;
    const showNav = props.profile.is_self && props.user;

    const rows = [];

    if (showNav) {
        rows.push({ id: 'nav', height: 'content' });
    }

    rows.push({ id: 'info', height: 'content', header: '基本资料' });
    rows.push({ id: 'posts', gravity: 1, header: 'TA 的帖子' });

    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered user-profile-page',
        rows,
    });

    if (showNav) {
        nav = mountUserCenterNav(layout.getCell('nav'), 'profile', username);
    }

    profileForm = attachForm(layout.getCell('info'), {
        css: 'dhx_widget--bordered',
        padding: 20,
        disabled: true,
        rows: [
            {
                type: 'input',
                name: 'nickname',
                label: '昵称',
                labelPosition: 'top',
                value: props.profile.nickname || props.profile.username,
                readOnly: true,
            },
            {
                type: 'input',
                name: 'username',
                label: '用户名',
                labelPosition: 'top',
                value: `@${props.profile.username}${certified}`,
                readOnly: true,
            },
            {
                type: 'textarea',
                name: 'bio',
                label: '个人简介',
                labelPosition: 'top',
                value: props.profile.bio || '暂无简介',
                readOnly: true,
            },
            {
                type: 'input',
                name: 'college',
                label: '学院 / 专业 / 年级',
                labelPosition: 'top',
                value: [props.profile.college, props.profile.major, props.profile.grade].filter(Boolean).join(' · ') || '未填写',
                readOnly: true,
            },
        ],
    });

    postsGrid = attachGrid(layout.getCell('posts'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: postGridColumns,
    });

    loadGridData(postsGrid, `/api/posts?user_id=${props.profile.id}`).catch(() => {
        postsGrid.data.parse([]);
    });
    bindGridOpenPost(postsGrid);
}

onBeforeUnmount(() => {
    profileForm?.destructor();
    postsGrid?.destructor();
    nav?.destructor();
    layout?.destructor();
});
</script>
