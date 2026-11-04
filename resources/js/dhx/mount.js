// Vue 应用挂载 + 闪存消息显示
import './theme.js';
import { createApp } from 'vue';
import { showFlashMessages } from './message';

export function createPageApp(component, props = {}) {
    const pageProps = { ...(window.__PAGE_PROPS__ || {}), ...props };
    const app = createApp(component, pageProps);
    app.mount('#app');
    showFlashMessages(pageProps.flash);

    return app;
}
