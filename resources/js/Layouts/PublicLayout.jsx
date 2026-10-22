import { Head, Link, usePage } from '@inertiajs/react';

export default function PublicLayout({ title, wide = false, children }) {
  const { flash } = usePage().props;

  return (
    <div className="min-h-screen">
      <Head title={title} />
      <header className="border-b border-ink/10 bg-paper">
        <div className={`mx-auto flex ${wide ? 'max-w-5xl' : 'max-w-3xl'} items-center justify-between px-4 py-4`}>
          <Link href="/"><img src="/aperta-logo.svg" alt="Aperta" className="h-10 w-auto" /></Link>
          <nav className="flex gap-5 text-sm text-ash">
            <Link href="/" className="hover:text-ink">Booking</Link>
            <Link href="/lacak" className="hover:text-ink">Lacak booking</Link>
          </nav>
        </div>
      </header>
      <main className={`mx-auto ${wide ? 'max-w-5xl' : 'max-w-3xl'} px-4 py-8`}>
        {flash?.success && (
          <div role="status" className="mb-6 rounded-lg border border-amber/40 bg-amber/10 px-4 py-3 text-sm text-ink">{flash.success}</div>
        )}
        {children}
      </main>
    </div>
  );
}
