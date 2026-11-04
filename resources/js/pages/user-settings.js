import { createPageApp } from '../dhx/mount';
import UserSettingsPage from '../components/UserSettingsPage.vue';
createPageApp(UserSettingsPage, window.__PAGE_PROPS__ || {});
