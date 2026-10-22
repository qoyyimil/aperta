import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

// Klik kanan dimatikan, kecuali di kolom isian agar tetap bisa tempel.
document.addEventListener('contextmenu', (e) => {
  if (!e.target.closest('input, textarea')) e.preventDefault();
});

createInertiaApp({
  title: (title) => (title ? `${title} · Aperta` : 'Aperta'),
  resolve: (name) => pages[`./Pages/${name}.jsx`],
  setup: ({ el, App, props }) => createRoot(el).render(<App {...props} />),
  progress: { color: '#E8912D' },
});
