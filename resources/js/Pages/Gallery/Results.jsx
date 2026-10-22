import { Link } from '@inertiajs/react';
import { useState } from 'react';
import ResultLightbox from '../../Components/gallery/ResultLightbox';
import { Card } from '../../Components/ui';
import PublicLayout from '../../Layouts/PublicLayout';
import { rupiah } from '../../lib/format';

/** Galeri hasil edit; unduhan resolusi penuh terbuka setelah lunas (DLV-7, DLV-8). */
export default function Results({ booking, results }) {
  const [viewing, setViewing] = useState(null);
  const base = `/lacak/${booking.token}/hasil`;
  const downloadUrl = booking.canDownload ? (r) => `${base}/${r.id}/unduh` : null;

  return (
    <PublicLayout title={`Hasil edit · ${booking.code}`} wide>
      <Link href={`/lacak/${booking.token}`} className="text-sm text-ash hover:text-ink">← Kembali ke status booking</Link>
      <div className="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Hasil edit kamu sudah jadi</h1>
          <p className="mt-1 text-sm text-ash">{results.length} foto dikirim {booking.deliveredAt}. Ketuk foto untuk melihat ukuran penuh dan membandingkan dengan sebelum diedit.</p>
        </div>
        {booking.canDownload && results.length > 0 && (
          <a href={`${base}/zip`} className="rounded-lg bg-amber px-4 py-2 text-sm font-semibold text-ink hover:bg-brick hover:text-paper">Unduh semua (ZIP)</a>
        )}
      </div>

      {!booking.canDownload && (
        <Card tone="warning" className="mt-4 text-sm">
          Unduhan resolusi penuh terbuka setelah sisa tagihan <b>{rupiah(booking.remaining)}</b> dilunasi. Hubungi admin untuk konfirmasi pembayaran.
        </Card>
      )}

      <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        {results.map((r, i) => (
          <button key={r.id} type="button" onClick={() => setViewing(i)} aria-label={`Lihat ${r.filename}`}
            className="group min-w-0 rounded-lg border border-ink/10 bg-white p-2 text-left hover:border-ink/30">
            <img src={r.thumb} alt={r.filename} loading="lazy" className="aspect-[3/2] w-full rounded bg-ink/5 object-cover" />
            <span className="mt-1.5 block truncate font-mono text-[11px] text-ash">{r.filename}</span>
          </button>
        ))}
      </div>

      {viewing !== null && (
        <ResultLightbox results={results} index={viewing} downloadUrl={downloadUrl} onIndex={setViewing} onClose={() => setViewing(null)} />
      )}
    </PublicLayout>
  );
}
