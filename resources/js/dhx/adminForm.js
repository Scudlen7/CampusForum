// 表单管理工具
import { Window, Form } from 'dhx-suite';
import { showError } from './message';

export function openEntityForm({ title, rows, values = {} }) {
    return new Promise((resolve) => {
        let settled = false;

        const win = new Window({
            width: 480,
            height: 'content',
            modal: true,
            title,
            closable: true,
        });

        const formRows = rows.map((row) => ({
            ...row,
            value: values[row.name] ?? row.value ?? '',
        }));

        formRows.push({
            type: 'button',
            name: 'submit',
            text: '保存',
            submit: true,
            size: 'medium',
            view: 'flat',
            color: 'primary',
        });
        formRows.push({
            type: 'button',
            name: 'cancel',
            text: '取消',
            view: 'link',
        });

        const form = new Form(null, {
            css: 'dhx_widget--bordered',
            padding: 16,
            rows: formRows,
        });

        win.attach(form);

        const finish = (result) => {
            if (settled) {
                return;
            }
            settled = true;
            form.destructor();
            win.destructor();
            resolve(result);
        };

        form.events.on('click', (name) => {
            if (name === 'cancel') {
                finish(null);
                return;
            }

            if (name === 'submit') {
                if (!form.validate()) {
                    showError('请填写完整信息');
                    return;
                }
                finish(form.getValue());
            }
        });

        win.show();
    });
}
