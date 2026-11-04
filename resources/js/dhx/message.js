// 消息/提示/确认框封装 + promptInput 弹窗（Window+Form）
import { message, alert, confirm, Window, Form } from 'dhx-suite';

const MESSAGE_EXPIRE_MS = 1000;

export function showMessage(text, options = {}) {
    return message({
        text,
        expire: options.expire ?? MESSAGE_EXPIRE_MS,
        icon: options.icon,
        css: options.css,
        position: options.position,
    });
}

export function showSuccess(text) {
    return showMessage(text, { css: 'dhx_message--success' });
}

export function showError(text) {
    return showMessage(text, { css: 'dhx_message--error' });
}

export function showAlert(text, header = '提示') {
    const props = typeof text === 'string' ? { text, header } : { header, ...text };
    return alert({ buttons: ['确定'], ...props });
}

export function showConfirm(text, header = '确认') {
    const props = typeof text === 'string' ? { text, header } : { header, ...text };

    // DHTMLX confirm: 第一个按钮 resolve false，第二个按钮 resolve true
    return confirm({ buttons: ['取消', '确定'], ...props }).then((result) => result === true);
}

export function showFlashMessages(flash) {
    if (!flash) {
        return;
    }

    if (flash.success) {
        showSuccess(flash.success);
    }

    if (flash.error) {
        showError(flash.error);
    }
}

/**
 * Prompt for text input via DHTMLX Window + Form.
 */
export function promptInput({ title = '请输入', label = '内容', required = true } = {}) {
    return new Promise((resolve) => {
        let settled = false;

        const win = new Window({
            width: 420,
            height: 260,
            modal: true,
            title,
            closable: true,
        });

        const form = new Form(null, {
            css: 'dhx_widget--bordered',
            padding: 16,
            rows: [
                {
                    type: 'textarea',
                    name: 'value',
                    label,
                    labelPosition: 'top',
                    required,
                },
                {
                    type: 'button',
                    name: 'submit',
                    text: '确定',
                    submit: true,
                    size: 'medium',
                    view: 'flat',
                    color: 'primary',
                },
                {
                    type: 'button',
                    name: 'cancel',
                    text: '取消',
                    view: 'link',
                },
            ],
        });

        win.attach(form);

        const finish = (value) => {
            if (settled) {
                return;
            }
            settled = true;
            form.destructor();
            win.destructor();
            resolve(value);
        };

        form.events.on('click', (name) => {
            if (name === 'cancel') {
                finish(null);
                return;
            }

            if (name === 'submit' && form.validate()) {
                finish(form.getValue().value?.trim() || null);
            }
        });

        win.show();
    });
}
