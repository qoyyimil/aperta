import Timeline from '../../Components/Timeline';
import { Card, Row } from '../../Components/ui';
import PublicLayout from '../../Layouts/PublicLayout';
import { rupiah } from '../../lib/format';
import { Link } from '@inertiajs/react';

export default function Show({ booking: b }) {
  return (
    <PublicLayout title={`Booking ${b.code}`}>
      <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="font-mono text-xs tracking-wider text-stone-500">{b.code}</p>
          <h1 className="text-2xl font-bold tracking-tight">Halo, {b.name.split(' ')[0]}</h1>
        </div>
        <span className="rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold">Pembayaran: {b.payment}</span>
      </div>

      {b.cancelled ? (
        <Card tone="danger" className="text-sm">
          <p className="font-semibold">Booking dibatalkan</p>
          {b.cancelReason && <p className="mt-1">Alasan: {b.cancelReason}</p>}
        </Card>
      ) : (
        <Card><Timeline steps={b.steps} /></Card>
      )}

      <Card tone="info" className="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div className="min-w-0">
          <p className="text-sm font-semibold">Langkah berikutnya</p>
          <p className="mt-1 text-sm">{b.next}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {b.resultsUrl && (
            <Link href={b.resultsUrl} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lihat hasil edit</Link>
          )}
          {b.gallery && (
            <Link href={b.gallery.url}
              className={`rounded-lg px-4 py-2 text-sm font-semibold ${b.resultsUrl ? 'border border-indigo-200 bg-white text-indigo-700' : 'bg-indigo-600 text-white hover:bg-indigo-700'}`}>
              {b.gallery.open ? 'Pilih foto' : 'Lihat foto pilihan'}
            </Link>
          )}
        </div>
      </Card>

      <div className="mt-4 grid gap-4 md:grid-cols-2">
        <Card>
          <h2 className="mb-2 font-semibold">Jadwal</h2>
          <Row label="Layanan">{b.service}</Row>
          <Row label="Tanggal">{b.date}</Row>
          <Row label="Waktu">{b.time}</Row>
          <Row label="Lokasi">{b.location || '-'}</Row>
        </Card>
        <Card>
          <h2 className="mb-2 font-semibold">Tagihan</h2>
          <Row label={b.package}>{rupiah(b.price.package)}</Row>
          {b.addons.map((a) => <Row key={a.name} label={`${a.name} × ${a.qty}`}>{rupiah(a.total)}</Row>)}
          {b.price.extras > 0 && <Row label="Foto edit tambahan">{rupiah(b.price.extras)}</Row>}
          {b.price.discount > 0 && <Row label="Diskon">−{rupiah(b.price.discount)}</Row>}
          <div className="mt-2 border-t border-stone-200 pt-2">
            <Row label="Total" strong>{rupiah(b.price.total)}</Row>
            <Row label="Sudah dibayar">{rupiah(b.paid)}</Row>
            <Row label="Sisa tagihan">{rupiah(Math.max(0, b.price.total - b.paid))}</Row>
          </div>
        </Card>
      </div>
    </PublicLayout>
  );
}
