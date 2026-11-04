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
import { Window, Form } from 'dhx-suite';
import {
    attachForm,
    attachHtml,
    attachLayout,
    attachList,
    attachToolbar,
} from '../dhx/cell';
import { apiFetch } from '../dhx/api';
import { showError, showSuccess } from '../dhx/message';

const props = defineProps({
    pageTitle: { type: String, default: '帖子详情' },
    user: { type: Object, default: null },
    isAdmin: { type: Boolean, default: false },
    unreadCount: { type: Number, default: 0 },
    post: { type: Object, required: true },
});

let layout = null;
let innerLayout = null;
let actionToolbar = null;
let commentForm = null;
let commentList = null;
let reportWindow = null;
let reportForm = null;
let replyTarget = null;

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function buildPostHtml(post) {
    const tags = (post.tags || [])
        .map((tag) => `<span class="post-tag">#${escapeHtml(tag)}</span>`)
        .join(' ');
    const certified = post.author_certified
        ? ' <span class="post-certified">✓ 已认证</span>'
        : '';

    return `
        <div class="post-show-body">
            <h2 class="post-show-title">${escapeHtml(post.title)}</h2>
            <div class="post-show-meta">
                <a class="post-category-link" href="/forum/${escapeHtml(post.category_slug)}">${escapeHtml(post.category)}</a>
                <span>·</span>
                <a href="/user/${escapeHtml(post.author_username)}">${escapeHtml(post.author)}</a>${certified}
                <span>·</span>
                <span>${escapeHtml(post.created_at)}</span>
            </div>
            <div class="post-show-stats">
                浏览 ${post.view_count} · 赞 ${post.like_count} · 评论 ${post.comment_count}
            </div>
            ${tags ? `<div class="post-show-tags">${tags}</div>` : ''}
            <div class="post-content-body">${post.content_html}</div>
        </div>
    `;
}

function initContent(contentCell) {
    layout = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered post-show-page',
        rows: [
            { id: 'scroll', gravity: 1, css: 'post-show-scroll' },
        ],
    });

    innerLayout = attachLayout(layout.getCell('scroll'), {
        type: 'line',
        css: 'post-show-inner',
        rows: [
            { id: 'post', height: 'content', header: '帖子详情' },
            { id: 'actions', height: 'content' },
            { id: 'comments', height: 'content', header: '评论列表' },
            {
                id: 'commentForm',
                height: props.user ? 180 : 0,
                hidden: !props.user,
                header: '发表评论',
            },
        ],
    });

    attachHtml(innerLayout.getCell('post'), buildPostHtml(props.post));

    const actions = [];
    if (props.user) {
        actions.push({ id: 'like', type: 'button', value: '点赞' });
        actions.push({ id: 'report', type: 'button', value: '举报' });
    }
    if (props.post.is_owner) {
        actions.push({ id: 'edit', type: 'button', value: '编辑' });
    }

    if (actions.length) {
        actionToolbar = attachToolbar(innerLayout.getCell('actions'), {
            css: 'dhx_widget--bordered',
            data: actions,
        });
        actionToolbar.events.on('click', handleAction);
    }

    loadComments(innerLayout);

    if (props.user) {
        commentForm = attachForm(innerLayout.getCell('commentForm'), {
            css: 'dhx_widget--bordered',
            padding: 12,
            helpMessage: '点击评论可回复该条评论',
            rows: [
                {
                    type: 'textarea',
                    name: 'content',
                    label: '评论内容',
                    labelPosition: 'top',
                    placeholder: '写下你的评论…',
                    required: true,
                },
                {
                    type: 'button',
                    name: 'cancelReply',
                    text: '取消回复',
                    view: 'link',
                    hidden: true,
                },
                {
                    type: 'button',
                    name: 'submit',
                    text: '提交评论',
                    submit: true,
                    size: 'medium',
                    view: 'flat',
                    color: 'primary',
                },
            ],
        });

        commentForm.events.on('click', (name) => {
            if (name === 'submit') {
                submitComment();
                return;
            }
            if (name === 'cancelReply') {
                clearReplyTarget();
            }
        });
    }
}

function clearReplyTarget() {
    replyTarget = null;
    if (!commentForm) {
        return;
    }
    commentForm.setProperties('cancelReply', { hidden: true });
    commentForm.config.helpMessage = '点击评论可回复该条评论';
}

function setReplyTarget(item) {
    replyTarget = {
        parent_id: item.parent_id,
        reply_to_id: item.reply_to_id,
        author: item.author,
    };
    commentForm.setProperties('cancelReply', { hidden: false });
    commentForm.config.helpMessage = `正在回复 @${item.author}`;
}

async function loadComments(pageLayout) {
    const { data } = await apiFetch(`/api/posts/${props.post.id}/comments`);
    const items = [];

    (data || []).forEach((comment) => {
        items.push({
            id: String(comment.id),
            parent_id: comment.id,
            reply_to_id: comment.id,
            author: comment.author,
            value: formatCommentLine(comment),
        });

        (comment.replies || []).forEach((reply) => {
            items.push({
                id: `r-${reply.id}`,
                parent_id: comment.id,
                reply_to_id: reply.id,
                author: reply.author,
                value: formatReplyLine(reply),
            });
        });
    });

    if (commentList) {
        commentList.destructor();
    }

    commentList = attachList(pageLayout.getCell('comments'), {
        css: 'dhx_widget--bordered post-comment-list',
        selection: false,
        htmlEnable: true,
        template: (item) => item.value,
        data: items.length ? items : [{ id: 'empty', value: '<div style="padding:16px;color:#888;">暂无评论，快来抢沙发吧</div>' }],
    });

    if (props.user && items.length) {
        commentList.events.on('click', (id) => {
            if (id === 'empty') {
                return;
            }
            const item = commentList.data.getItem(id);
            if (item?.parent_id) {
                setReplyTarget(item);
            }
        });
    }
}

function formatCommentLine(comment) {
    const certified = comment.author_certified ? ' <span class="post-certified">✓</span>' : '';
    return `
        <div class="comment-item">
            <strong>${escapeHtml(comment.author)}</strong>${certified}
            <span class="comment-time">${escapeHtml(comment.created_at)}</span>
            <div class="comment-text">${escapeHtml(comment.content)}</div>
        </div>
    `;
}

function formatReplyLine(reply) {
    const certified = reply.author_certified ? ' <span class="post-certified">✓</span>' : '';
    const replyTo = reply.reply_to ? ` 回复 @${escapeHtml(reply.reply_to)}` : '';
    return `
        <div class="comment-reply">
            ↳ <strong>${escapeHtml(reply.author)}</strong>${certified}${replyTo}
            <span class="comment-time">${escapeHtml(reply.created_at)}</span>
            <div class="comment-text">${escapeHtml(reply.content)}</div>
        </div>
    `;
}

async function handleAction(id) {
    if (id === 'like') {
        try {
            const result = await apiFetch('/api/likes/toggle', {
                method: 'POST',
                body: JSON.stringify({ type: 'post', id: props.post.id }),
            });
            showSuccess(result.liked ? '已点赞' : '已取消点赞');
            window.location.reload();
        } catch (error) {
            showError(error.message || '操作失败');
        }
        return;
    }

    if (id === 'report') {
        openReport();
        return;
    }

    if (id === 'edit') {
        window.location.href = `/posts/${props.post.id}/edit`;
    }
}

function openReport() {
    reportWindow = new Window({
        width: 420,
        height: 320,
        modal: true,
        title: '举报帖子',
        closable: true,
    });

    reportForm = new Form(null, {
        css: 'dhx_widget--bordered',
        padding: 16,
        rows: [
            {
                type: 'select',
                name: 'reason',
                label: '原因',
                labelPosition: 'top',
                options: [
                    { value: 1, content: '违规内容' },
                    { value: 2, content: '广告' },
                    { value: 3, content: '色情' },
                    { value: 4, content: '侮辱' },
                    { value: 5, content: '其他' },
                ],
            },
            {
                type: 'textarea',
                name: 'description',
                label: '说明',
                labelPosition: 'top',
            },
            {
                type: 'button',
                name: 'submit',
                text: '提交举报',
                submit: true,
                size: 'medium',
                view: 'flat',
                color: 'primary',
            },
        ],
    });

    reportWindow.attach(reportForm);

    reportForm.events.on('click', async (name) => {
        if (name !== 'submit') {
            return;
        }

        const values = reportForm.getValue();

        try {
            await apiFetch('/api/reports', {
                method: 'POST',
                body: JSON.stringify({
                    type: 'post',
                    id: props.post.id,
                    reason: Number(values.reason),
                    description: values.description,
                }),
            });
            reportWindow.hide();
            reportForm.destructor();
            reportWindow.destructor();
            reportWindow = null;
            reportForm = null;
            showSuccess('举报已提交');
        } catch (error) {
            showError(error.message || '举报提交失败');
        }
    });

    reportWindow.show();
}

async function submitComment() {
    if (!commentForm.validate()) {
        return;
    }

    const values = commentForm.getValue();
    const payload = {
        content: values.content,
    };

    if (replyTarget) {
        payload.parent_id = replyTarget.parent_id;
        payload.reply_to_id = replyTarget.reply_to_id;
    }

    try {
        await apiFetch(`/api/posts/${props.post.id}/comments`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        showSuccess('评论已发布');
        window.location.reload();
    } catch (error) {
        showError(error.message || '评论发布失败');
    }
}

onBeforeUnmount(() => {
    reportForm?.destructor();
    reportWindow?.destructor();
    commentList?.destructor();
    commentForm?.destructor();
    actionToolbar?.destructor();
    innerLayout?.destructor();
    layout?.destructor();
});
</script>
