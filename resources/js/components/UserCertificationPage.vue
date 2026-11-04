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
import { attachForm, attachLayout, mountUserCenterNav } from '../dhx/cell';
import { apiFetch, csrfToken } from '../dhx/api';
import { showError, showSuccess } from '../dhx/message';

const props = defineProps({
    pageTitle: { type: String, default: '学号认证' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    certification: { type: Object, default: null },
});

let layout = null;
let form = null;
let nav = null;

function initContent(contentCell) {
    const status = props.certification?.status;
    const isApproved = status === 1;
    const isPending = status === 0;
    const canSubmit = status === undefined || status === 2;
    const statusText = getStatusText(props.certification);

    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered user-certification-page',
        rows: [
            { id: 'nav', height: 'content' },
            { id: 'form', gravity: 1, header: '学号认证' },
        ],
    });

    nav = mountUserCenterNav(
        layout.getCell('nav'),
        'certification',
        props.user?.username || '',
    );

    form = attachForm(layout.getCell('form'), {
        css: 'dhx_widget--bordered',
        padding: 24,
        helpMessage: `当前状态：${statusText}`,
        rows: [
            {
                type: 'input',
                name: 'student_no',
                label: '学号',
                labelPosition: 'top',
                placeholder: '请输入学号',
                required: true,
                value: props.certification?.student_no || '',
                readOnly: isApproved || isPending,
            },
            {
                type: 'simplevault',
                name: 'image',
                label: '学生证图片',
                labelPosition: 'top',
                accept: 'image/*',
                fieldName: 'image',
                $vaultHeight: 160,
                hidden: !canSubmit,
            },
            {
                type: 'button',
                name: 'submit',
                text: '提交认证申请',
                submit: true,
                size: 'medium',
                view: 'flat',
                color: 'primary',
                full: true,
                hidden: !canSubmit,
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
        submitCertification();
    });
}

function getStatusText(certification) {
    if (!certification) {
        return '未提交认证';
    }

    const map = { 0: '待审核', 1: '已通过', 2: '已拒绝' };
    let text = map[certification.status] || '未知';

    if (certification.reject_reason) {
        text += `（${certification.reject_reason}）`;
    }

    return text;
}

function getVaultControl() {
    const item = form.getItem('image');
    if (item && typeof item.getValue === 'function') {
        return item;
    }

    return null;
}

function extractVaultFile(vault) {
    const values = vault?.getValue?.() || [];

    for (const item of values) {
        if (item?.file instanceof File) {
            return item.file;
        }

        if (item?.file instanceof Blob) {
            const name = item.name || item.file.name || 'student-card.jpg';
            const type = item.file.type || 'image/jpeg';

            return new File([item.file], name, { type });
        }

        if (item instanceof File) {
            return item;
        }
    }

    return null;
}

async function submitCertification() {
    const vault = getVaultControl();
    const file = extractVaultFile(vault);

    if (!file) {
        showError('请上传学生证图片');
        return;
    }

    const values = form.getValue();
    const body = new FormData();
    body.append('_token', csrfToken());
    body.append('student_no', values.student_no || '');
    body.append('image', file, file.name || 'student-card.jpg');

    try {
        const result = await apiFetch('/user/certification', {
            method: 'POST',
            body,
        });
        showSuccess(result.message || '认证申请已提交');
        setTimeout(() => window.location.reload(), 800);
    } catch (error) {
        showError(error.message || '提交失败，请稍后重试');
    }
}

onBeforeUnmount(() => {
    form?.destructor();
    nav?.destructor();
    layout?.destructor();
});
</script>
