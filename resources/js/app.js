import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import GrandPrize from './components/GrandPrize.vue';

// Determine which component to mount based on URL pathname
const isGrandPrize = window.location.pathname === '/grand-prize';
const rootComponent = isGrandPrize ? GrandPrize : App;

const app = createApp(rootComponent);

// Inject global config via provide
app.provide('appName', window.appConfig?.appName ?? 'Laravel');
app.provide('year', window.appConfig?.year ?? '2025');
app.provide('platJateng', window.appConfig?.platJateng ?? []);
app.provide('kabkota', window.appConfig?.kabkota ?? '');
app.provide('winnersPerPage', window.appConfig?.winnersPerPage ?? 5);

app.mount('#app');
