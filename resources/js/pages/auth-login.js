import { createPageApp } from '../dhx/mount';
import AuthLoginPage from '../components/AuthLoginPage.vue';

createPageApp(AuthLoginPage, window.__PAGE_PROPS__ || {});
