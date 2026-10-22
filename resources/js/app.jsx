import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

createInertiaApp({
  title: (title) => (title ? `${title} · Aperta` : 'Aperta'),
  resolve: (name) => pages[`./Pages/${name}.jsx`],
  setup: ({ el, App, props }) => createRoot(el).render(<App {...props} />),
  progress: { color: '#4f46e5' },
});
