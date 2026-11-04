import { createPageApp } from '../dhx/mount';
import UserProfilePage from '../components/UserProfilePage.vue';
createPageApp(UserProfilePage, window.__PAGE_PROPS__ || {});
