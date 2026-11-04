import { createPageApp } from '../dhx/mount';
import AuthRegisterPage from '../components/AuthRegisterPage.vue';

createPageApp(AuthRegisterPage, window.__PAGE_PROPS__ || {});
