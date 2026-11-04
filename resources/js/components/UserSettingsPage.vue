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
import { attachForm, attachLayout, mountUserCenterNav, submitHiddenForm } from '../dhx/cell';

const props = defineProps({
    pageTitle: { type: String, default: '个人设置' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    profile: { type: Object, required: true },
});

let layout = null;
let form = null;
let nav = null;

function initContent(contentCell) {
    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered user-settings-page',
        rows: [
            { id: 'nav', height: 'content' },
            { id: 'form', gravity: 1, header: '编辑个人资料' },
        ],
    });

    nav = mountUserCenterNav(layout.getCell('nav'), 'settings', props.profile.username);

    form = attachForm(layout.getCell('form'), {
        css: 'dhx_widget--bordered',
        padding: 24,
        rows: [
            {
                type: 'input',
                name: 'username',
                label: '用户名',
                labelPosition: 'top',
                value: props.profile.username,
                readOnly: true,
            },
            {
                type: 'input',
                name: 'email',
                label: '邮箱',
                labelPosition: 'top',
                value: props.profile.email,
                readOnly: true,
            },
            {
                type: 'input',
                name: 'nickname',
                label: '昵称',
                labelPosition: 'top',
                placeholder: '个人主页显示名称',
                value: props.profile.nickname || '',
            },
            {
                type: 'textarea',
                name: 'bio',
                label: '个人简介',
                labelPosition: 'top',
                placeholder: '介绍一下自己',
                value: props.profile.bio || '',
            },
            {
                type: 'input',
                name: 'college',
                label: '学院',
                labelPosition: 'top',
                value: props.profile.college || '',
            },
            {
                type: 'input',
                name: 'major',
                label: '专业',
                labelPosition: 'top',
                value: props.profile.major || '',
            },
            {
                type: 'input',
                name: 'grade',
                label: '年级',
                labelPosition: 'top',
                placeholder: '例如 2022',
                value: props.profile.grade || '',
            },
            {
                type: 'button',
                name: 'submit',
                text: '保存资料',
                submit: true,
                size: 'medium',
                view: 'flat',
                color: 'primary',
                full: true,
            },
        ],
    });

    form.events.on('click', (name) => {
        if (name !== 'submit') {
            return;
        }
        if (!form.validate()) {
            return;
        }
        const values = form.getValue();
        submitHiddenForm('/user/settings', {
            nickname: values.nickname,
            bio: values.bio,
            college: values.college,
            major: values.major,
            grade: values.grade,
        });
    });
}

onBeforeUnmount(() => {
    form?.destructor();
    nav?.destructor();
    layout?.destructor();
});
</script>
