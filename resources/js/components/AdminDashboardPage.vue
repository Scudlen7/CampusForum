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
import { attachChart, attachGrid, attachHtml, attachLayout, mountAdminLayout } from '../dhx/cell';

const props = defineProps({
    pageTitle: { type: String, default: '后台管理' },
    user: { type: Object, default: null },
    unreadCount: { type: Number, default: 0 },
    stats: { type: Object, default: () => ({}) },
});

let innerLayout = null;
let chart = null;
let statsGrid = null;

function buildChartData(stats) {
    return [
        { module: '总用户', count: stats.users ?? 0 },
        { module: '新增用户', count: stats.new_users ?? 0 },
        { module: '活跃用户', count: stats.active_users ?? 0 },
        { module: '帖子', count: stats.posts ?? 0 },
        { module: '评论', count: stats.comments ?? 0 },
        { module: '待处理举报', count: stats.pending_reports ?? 0 },
        { module: '待审核认证', count: stats.pending_certs ?? 0 },
    ];
}

function initContent(contentCell) {
    const mainCell = mountAdminLayout(contentCell, 'dashboard');
    const s = props.stats;
    const chartData = buildChartData(s);
    const maxCount = Math.max(...chartData.map((item) => item.count), 1);

    innerLayout = attachLayout(mainCell, {
        type: 'line',
        css: 'dhx_widget--bordered admin-dashboard',
        rows: [
            { id: 'stats', height: 'content', header: '数据概览' },
            { id: 'chart', height: 360, header: '数据统计' },
            { id: 'grid', height: 280, header: '统计明细' },
        ],
    });

    attachHtml(
        innerLayout.getCell('stats'),
        `
            <div style="padding:24px;display:flex;gap:32px;flex-wrap:wrap;font-size:15px;">
                <div><strong>总用户</strong>：${s.users ?? 0}</div>
                <div><strong>新增用户（7天）</strong>：${s.new_users ?? 0}</div>
                <div><strong>活跃用户（7天）</strong>：${s.active_users ?? 0}</div>
                <div><strong>帖子数</strong>：${s.posts ?? 0}</div>
                <div><strong>评论数</strong>：${s.comments ?? 0}</div>
                <div><strong>待处理举报</strong>：${s.pending_reports ?? 0}</div>
                <div><strong>待审核认证</strong>：${s.pending_certs ?? 0}</div>
            </div>
        `,
    );

    chart = attachChart(innerLayout.getCell('chart'), {
        type: 'bar',
        css: 'dhx_widget--bordered admin-stats-chart',
        scales: {
            bottom: {
                text: 'module',
            },
            left: {
                min: 0,
                max: maxCount + Math.ceil(maxCount * 0.2),
                maxTicks: 8,
            },
        },
        series: [
            {
                id: 'stats',
                value: 'count',
                color: '#0288d1',
                fill: '#0288d1',
            },
        ],
    });

    chart.data.parse(chartData);

    statsGrid = attachGrid(innerLayout.getCell('grid'), {
        css: 'dhx_widget--bordered',
        headerRowHeight: 40,
        rowHeight: 42,
        autoWidth: true,
        columns: [
            { id: 'module', header: [{ text: '指标' }], minWidth: 180, gravity: 2 },
            { id: 'count', header: [{ text: '数量' }], width: 120 },
            { id: 'description', header: [{ text: '说明' }], minWidth: 260, gravity: 1 },
        ],
    });

    statsGrid.data.parse([
        { id: 'users', module: '总用户', count: s.users ?? 0, description: '平台注册用户总数' },
        { id: 'new_users', module: '新增用户（7天）', count: s.new_users ?? 0, description: '近 7 天新注册用户数' },
        { id: 'active_users', module: '活跃用户（7天）', count: s.active_users ?? 0, description: '近 7 天发帖或评论的用户数' },
        { id: 'posts', module: '帖子', count: s.posts ?? 0, description: '平台帖子总数' },
        { id: 'comments', module: '评论', count: s.comments ?? 0, description: '平台评论总数' },
        { id: 'pending_reports', module: '待处理举报', count: s.pending_reports ?? 0, description: '等待管理员处理的举报' },
        { id: 'pending_certs', module: '待审核认证', count: s.pending_certs ?? 0, description: '等待审核的学号认证申请' },
    ]);
}

onBeforeUnmount(() => {
    statsGrid?.destructor();
    chart?.destructor();
    innerLayout?.destructor();
});
</script>
