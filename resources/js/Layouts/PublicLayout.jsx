import { Head, Link, usePage } from '@inertiajs/react';

export default function PublicLayout({ title, wide = false, children }) {
  const { flash } = usePage().props;

  return (
    <div className="min-h-screen">
      <Head title={title} />
      <header className="border-b border-stone-200 bg-white">
        <div className={`mx-auto flex ${wide ? 'max-w-5xl' : 'max-w-3xl'} items-center justify-between px-4 py-4`}>
          <Link href="/"><img src="/aperta-logo.svg" alt="Aperta" className="h-10 w-auto" /></Link>
          <nav className="flex gap-5 text-sm text-stone-600">
            <Link href="/" className="hover:text-stone-900">Booking</Link>
            <Link href="/lacak" className="hover:text-stone-900">Lacak booking</Link>
          </nav>
        </div>
      </header>
      <main className={`mx-auto ${wide ? 'max-w-5xl' : 'max-w-3xl'} px-4 py-8`}>
        {flash?.success && (
          <div role="status" className="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{flash.success}</div>
        )}
        {children}
      </main>
    </div>
  );
}
