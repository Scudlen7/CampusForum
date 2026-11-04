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
    attachForm,
    attachHtml,
    attachLayout,
    getLayoutCellElement,
} from '../dhx/cell';
import { apiFetch, csrfToken } from '../dhx/api';
import { showError, showSuccess } from '../dhx/message';
import { destroyVditor, mountVditor } from '../dhx/vditor';

const props = defineProps({
    pageTitle: { type: String, default: '发帖' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    mode: { type: String, default: 'create' },
    post: { type: Object, default: null },
});

const VDITOR_ROOT_CLASS = 'post-vditor-root';

let layout = null;
let metaForm = null;
let actionForm = null;
let vditor = null;

function initContent(contentCell) {
    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered post-editor-page',
        rows: [
            { id: 'meta', height: 220, header: '帖子信息' },
            { id: 'editor', gravity: 1, header: '正文（Markdown）' },
            { id: 'actions', height: 'content' },
        ],
    });

    apiFetch('/api/meta/categories')
        .then(({ data }) => {
            const categories = data || [];

            metaForm = attachForm(layout.getCell('meta'), {
                css: 'dhx_widget--bordered',
                padding: 16,
                rows: [
                    {
                        type: 'input',
                        name: 'title',
                        label: '标题',
                        labelPosition: 'top',
                        placeholder: '请输入帖子标题',
                        value: props.post?.title || '',
                        required: true,
                    },
                    {
                        type: 'select',
                        name: 'category_id',
                        label: '帖子分类',
                        labelPosition: 'top',
                        helpMessage: '分类即帖子类型，可在论坛左侧按分类筛选',
                        options: categories.map((category) => ({
                            value: category.id,
                            content: `${category.icon || ''} ${category.name}`.trim(),
                        })),
                        value: props.post?.category_id || categories[0]?.id,
                        required: true,
                    },
                    {
                        type: 'input',
                        name: 'tags',
                        label: '话题标签（逗号分隔）',
                        labelPosition: 'top',
                        placeholder: '例如 Laravel, Vue',
                        value: props.post?.tags || '',
                    },
                ],
            });

            setupEditor();

            actionForm = attachForm(layout.getCell('actions'), {
                css: 'dhx_widget--bordered',
                padding: 12,
                rows: [
                    {
                        type: 'button',
                        name: 'submit',
                        text: props.mode === 'edit' ? '保存修改' : '发布帖子',
                        submit: true,
                        size: 'medium',
                        view: 'flat',
                        color: 'primary',
                    },
                ],
            });

            actionForm.events.on('click', (name) => {
                if (name === 'submit') {
                    submitPost();
                }
            });
        })
        .catch((error) => {
            showError(error.message || '分类加载失败');
        });
}

function setupEditor() {
    const editorCell = layout.getCell('editor');
    attachHtml(editorCell, `<div class="${VDITOR_ROOT_CLASS}"></div>`);

    requestAnimationFrame(() => {
        requestAnimationFrame(async () => {
            const cellElement = getLayoutCellElement(editorCell);
            const container = cellElement?.querySelector(`.${VDITOR_ROOT_CLASS}`) || document.querySelector(`.post-editor-page .${VDITOR_ROOT_CLASS}`);

            if (!container) {
                showError('编辑器容器加载失败，请刷新页面重试');
                return;
            }

            try {
                destroyVditor(vditor);
                vditor = await mountVditor(container, {
                    value: props.post?.content || '',
                    upload: {
                        url: '/api/upload/image',
                        fieldName: 'file',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    },
                });
            } catch (error) {
                showError(error.message || '编辑器初始化失败');
            }
        });
    });
}

async function submitPost() {
    if (!metaForm?.validate()) {
        showError('请填写完整的帖子信息');
        return;
    }

    if (!vditor) {
        showError('编辑器尚未就绪，请稍后再试');
        return;
    }

    const values = metaForm.getValue();
    const content = vditor.getValue()?.trim();

    if (!content) {
        showError('请输入帖子正文');
        return;
    }

    const tags = (values.tags || '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean);

    const payload = {
        title: values.title,
        category_id: Number(values.category_id),
        content,
        tags,
    };

    try {
        if (props.mode === 'edit' && props.post?.id) {
            await apiFetch(`/api/posts/${props.post.id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
            showSuccess('帖子已更新');
            window.location.href = `/posts/${props.post.id}`;
            return;
        }

        const result = await apiFetch('/api/posts', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        showSuccess('帖子发布成功');
        window.location.href = `/posts/${result.data.id}`;
    } catch (error) {
        showError(error.message || '发布失败，请稍后重试');
    }
}

onBeforeUnmount(() => {
    destroyVditor(vditor);
    vditor = null;
    actionForm?.destructor();
    metaForm?.destructor();
    layout?.destructor();
});
</script>
