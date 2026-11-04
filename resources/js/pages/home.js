import { createPageApp } from '../dhx/mount';
import HomePage from '../components/HomePage.vue';

createPageApp(HomePage, window.__PAGE_PROPS__ || {});
