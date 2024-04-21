import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import App from './App.vue';

import * as ElementPlusIconsVue from '@element-plus/icons-vue';

const app = createApp(App);

for (const [componentName, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(componentName, component);
}

app.use(ElementPlus);
app.mount('#app');