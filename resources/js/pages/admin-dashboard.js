import { createPageApp } from '../dhx/mount';
import AdminDashboardPage from '../components/AdminDashboardPage.vue';
createPageApp(AdminDashboardPage, window.__PAGE_PROPS__ || {});
