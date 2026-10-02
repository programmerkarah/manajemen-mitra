import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { initializeInertiaCsrfGuard } from './lib/csrf-guard';
import { resolveInertiaPage } from './lib/inertia-pages';
import { initializeReturnNavigation } from './lib/return-navigation';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./pages/**/*.tsx');

initializeInertiaCsrfGuard();
initializeReturnNavigation();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolveInertiaPage(name, pages),
    setup({ el, App, props }) {
        createRoot(el).render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#6F93C7',
    },
});

initializeTheme();
