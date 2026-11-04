// 登录/注册页面模板（居中 Window + Form + 表单提交）
import { Layout, Window, Form } from 'dhx-suite';

export function mountAuthPage(rootEl, options) {
    const {
        title,
        errorMessage = '',
        formRows,
        onSubmit,
        footerLinks = [],
    } = options;

    const layout = new Layout(rootEl, {
        type: 'line',
        css: 'auth-page-layout',
        rows: [{ id: 'center', gravity: 1 }],
    });

    const windowHeight = options.windowHeight || 460;

    const win = new Window({
        title,
        width: 480,
        height: windowHeight,
        modal: false,
        closable: false,
        movable: false,
        resizable: false,
        css: 'dhx_widget--bordered auth-window',
    });

    const rows = [...formRows];

    if (footerLinks.length) {
        rows.push({ type: 'spacer', height: 8 });
        footerLinks.forEach((link) => {
            rows.push({
                type: 'button',
                name: link.name,
                text: link.text,
                view: 'link',
                size: 'medium',
                color: 'primary',
                full: true,
            });
        });
    }

    const form = new Form(null, {
        css: 'dhx_widget--bordered auth-form',
        padding: 20,
        width: '100%',
        helpMessage: errorMessage || undefined,
        rows,
    });

    win.attach(form);
    win.show();
    centerWindow(win);

    form.events.on('click', (name, event) => {
        const footer = footerLinks.find((link) => link.name === name);
        if (footer?.href) {
            event?.preventDefault?.();
            window.location.href = footer.href;
            return;
        }

        if (name === 'submit') {
            if (!form.validate()) {
                return;
            }
            onSubmit(form.getValue());
        }
    });

    const resizeHandler = () => centerWindow(win);
    window.addEventListener('resize', resizeHandler);

    return {
        layout,
        window: win,
        form,
        destroy() {
            window.removeEventListener('resize', resizeHandler);
            form.destructor();
            win.destructor();
            layout.destructor();
        },
    };
}

function centerWindow(win) {
    const size = win.getSize();
    win.setPosition(
        Math.max(16, Math.round((window.innerWidth - size.width) / 2)),
        Math.max(16, Math.round((window.innerHeight - size.height) / 2)),
    );
}

export function submitAuthForm(action, fields) {
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

export function formatErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return '';
    }

    return Object.values(errors).flat().join('；');
}
