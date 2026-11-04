import { createPageApp } from '../dhx/mount';
import PostShowPage from '../components/PostShowPage.vue';
createPageApp(PostShowPage, window.__PAGE_PROPS__ || {});
