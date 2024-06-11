import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import App from './App.vue';

import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import longPress from './directives/longPress';

const app = createApp(App);

for (const [componentName, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(componentName, component);
}

app.directive('longpress', longPress);

app.use(ElementPlus);
app.mount('#app');