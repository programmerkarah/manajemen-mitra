import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { initializeInertiaCsrfGuard } from './lib/csrf-guard';
import { resolveInertiaPage } from './lib/inertia-pages';
import { initializeReturnNavigation } from './lib/return-navigation';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./pages/**/*.tsx');

initializeTheme();
initializeInertiaCsrfGuard();
initializeReturnNavigation();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolveInertiaPage(name, pages),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#6F93C7',
    },
});
