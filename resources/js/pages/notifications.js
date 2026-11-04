import { createPageApp } from '../dhx/mount';
import NotificationsPage from '../components/NotificationsPage.vue';
createPageApp(NotificationsPage, window.__PAGE_PROPS__ || {});
