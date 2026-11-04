import { createPageApp } from '../dhx/mount';
import PostEditorPage from '../components/PostEditorPage.vue';
createPageApp(PostEditorPage, window.__PAGE_PROPS__ || {});
