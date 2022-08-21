import '../scss/app.scss'

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// @ts-ignore
import { ZiggyVue } from 'ziggy-vue';
import axios from 'axios';
import VueAxios from 'vue-axios'
import _ from 'lodash';

InertiaProgress.init({ showSpinner: true, delay: 500, color: '#4B5563' });

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';
window._ = _;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    // @ts-ignore
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, app, props, plugin }) {
        createApp({ render: () => h(app, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueAxios, axios)
            .mount(el);
    },
});