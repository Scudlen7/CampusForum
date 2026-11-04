// Vditor Markdown 编辑器挂载/销毁
import Vditor from 'vditor';
import 'vditor/dist/index.css';

export function mountVditor(container, options = {}) {
    return new Promise((resolve, reject) => {
        if (!container) {
            reject(new Error('编辑器容器不存在'));
            return;
        }

        try {
            const instance = new Vditor(container, {
                height: options.height ?? 420,
                minHeight: options.minHeight ?? 360,
                mode: options.mode ?? 'ir',
                placeholder: options.placeholder ?? '支持 Markdown、代码高亮、图片上传等功能',
                value: options.value ?? '',
                cache: { enable: false },
                upload: options.upload,
                preview: options.preview ?? {
                    markdown: {
                        toc: true,
                    },
                },
                after: () => resolve(instance),
            });
        } catch (error) {
            reject(error);
        }
    });
}

export function destroyVditor(instance) {
    if (instance && typeof instance.destroy === 'function') {
        instance.destroy();
    }
}
