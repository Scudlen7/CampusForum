import { createPageApp } from '../dhx/mount';
import ForumIndexPage from '../components/ForumIndexPage.vue';
createPageApp(ForumIndexPage, window.__PAGE_PROPS__ || {});
