// Layout Cell 挂载工具函数（attachGrid/attachForm/attachLayout/attachToolbar/attachSidebar/attachList/attachChart/attachHtml）；后台布局模板；Grid 列定义；评论导航；隐藏表单提交
import { Layout, Grid, Form, Toolbar, Sidebar, List, Chart } from 'dhx-suite';

export function attachWidget(cell, Widget, config = {}) {
    if (!cell || typeof cell.attach !== 'function') {
        throw new Error('Invalid layout cell');
    }

    return cell.attach(Widget, config);
}

export function attachLayout(cell, config) {
    return attachWidget(cell, Layout, config);
}

export function attachGrid(cell, config) {
    return attachWidget(cell, Grid, config);
}

export function attachForm(cell, config) {
    return attachWidget(cell, Form, config);
}

export function attachToolbar(cell, config) {
    return attachWidget(cell, Toolbar, config);
}

export function attachSidebar(cell, config) {
    return attachWidget(cell, Sidebar, config);
}

export function attachList(cell, config) {
    return attachWidget(cell, List, config);
}

export function attachChart(cell, config) {
    return attachWidget(cell, Chart, config);
}

export const adminMenu = [
    { id: 'dashboard', value: '仪表盘', icon: 'dxi dxi-chart-line' },
    { id: 'users', value: '用户管理', icon: 'dxi dxi-account' },
    { id: 'certifications', value: '认证审核', icon: 'dxi dxi-check-circle' },
    { id: 'posts', value: '帖子管理', icon: 'dxi dxi-file' },
    { id: 'comments', value: '评论管理', icon: 'dxi dxi-message' },
    { id: 'reports', value: '举报处理', icon: 'dxi dxi-alert' },
    { id: 'categories', value: '分类管理', icon: 'dxi dxi-view-grid' },
    { id: 'banners', value: 'Banner', icon: 'dxi dxi-image' },
];

export const adminRoutes = {
    dashboard: '/admin',
    users: '/admin/users',
    certifications: '/admin/certifications',
    posts: '/admin/posts',
    comments: '/admin/comments',
    reports: '/admin/reports',
    categories: '/admin/categories',
    banners: '/admin/banners',
};

export function mountAdminLayout(contentCell, activeAdminMenu) {
    const shell = attachLayout(contentCell, {
        type: 'line',
        css: 'dhx_widget--bordered admin-page',
        cols: [
            { id: 'sidebar', width: 220, header: '后台管理' },
            { id: 'main', gravity: 1 },
        ],
    });

    const sidebar = attachSidebar(shell.getCell('sidebar'), {
        css: 'dhx_widget--bordered',
        data: adminMenu,
        activeItem: activeAdminMenu,
    });

    sidebar.events.on('click', (id) => {
        if (adminRoutes[id]) {
            window.location.href = adminRoutes[id];
        }
    });

    return shell.getCell('main');
}

export function attachHtml(cell, html) {
    cell.attachHTML(html);
}

export function getLayoutCellElement(cell) {
    if (!cell) {
        return null;
    }

    const widget = typeof cell.getWidget === 'function' ? cell.getWidget() : null;
    if (widget?.getRootView?.()?.el) {
        return widget.getRootView().el;
    }

    const cellView = typeof cell.getCellView === 'function' ? cell.getCellView() : null;
    if (cellView?.el) {
        return cellView.el;
    }

    if (cellView?._container) {
        return cellView._container;
    }

    return null;
}

export const postGridColumns = [
    { id: 'title', header: [{ text: '标题' }], minWidth: 220, gravity: 2 },
    { id: 'comment_count', header: [{ text: '评论' }], width: 80 },
    { id: 'view_count', header: [{ text: '浏览' }], width: 80 },
    { id: 'created_at', header: [{ text: '时间' }], width: 160 },
];

export const forumGridColumns = [
    { id: 'title', header: [{ text: '标题' }], minWidth: 220, gravity: 2 },
    { id: 'category', header: [{ text: '分类' }], width: 120 },
    { id: 'author', header: [{ text: '作者' }], width: 120 },
    { id: 'comment_count', header: [{ text: '评论' }], width: 70 },
    { id: 'view_count', header: [{ text: '浏览' }], width: 70 },
    { id: 'created_at', header: [{ text: '时间' }], width: 160 },
];

export function bindGridOpenPost(grid) {
    grid.events.on('cellClick', (row) => {
        if (row?.id) {
            window.location.href = `/posts/${row.id}`;
        }
    });
}

export function mountUserCenterNav(cell, activeId, profileUsername) {
    const routes = {
        profile: profileUsername ? `/user/${profileUsername}` : '/user/settings',
        settings: '/user/settings',
        certification: '/user/certification',
    };

    const nav = attachToolbar(cell, {
        css: 'dhx_widget--bordered user-center-nav',
        data: [
            { id: 'profile', type: 'button', value: '个人主页', disabled: activeId === 'profile' },
            { id: 'settings', type: 'button', value: '个人设置', disabled: activeId === 'settings' },
            { id: 'certification', type: 'button', value: '学号认证', disabled: activeId === 'certification' },
        ],
    });

    nav.events.on('click', (id) => {
        if (id === activeId) {
            return;
        }
        if (routes[id]) {
            window.location.href = routes[id];
        }
    });

    return nav;
}

export function submitHiddenForm(action, fields) {
    const formEl = document.createElement('form');
    formEl.method = 'POST';
    formEl.action = action;

    appendHidden(formEl, '_token', document.querySelector('meta[name="csrf-token"]')?.content);

    Object.entries(fields).forEach(([name, value]) => {
        if (value === undefined || value === null || value === false) {
            return;
        }
        appendHidden(formEl, name, value === true ? 'on' : value);
    });

    document.body.appendChild(formEl);
    formEl.submit();
}

function appendHidden(formEl, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value ?? '';
    formEl.appendChild(input);
}
