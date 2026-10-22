import { useState } from 'react';
import Timeline from '../../Components/Timeline';
import { Button, Card, Row } from '../../Components/ui';
import PublicLayout from '../../Layouts/PublicLayout';
import { rupiah } from '../../lib/format';
import { Link } from '@inertiajs/react';

function DepositCard({ deposit: d }) {
  const [copied, setCopied] = useState(null);
  const copy = (number) => navigator.clipboard?.writeText(number).then(() => setCopied(number));

  return (
    <Card tone="warning" className="mt-4">
      <h2 className="font-semibold">Bayar DP untuk mengunci jadwal</h2>
      <p className="mt-1 text-sm">Minimal <b>{rupiah(d.amount)}</b>, paling lambat <b>{d.dueAt}</b>.</p>
      <ul className="mt-4 divide-y divide-ink/10 rounded-lg border border-ink/10 bg-white">
        {d.accounts.map((a) => (
          <li key={a.number} className="flex items-center justify-between gap-3 px-4 py-3">
            <div className="min-w-0">
              <p className="text-xs font-semibold text-ash">{a.bank}</p>
              <p className="font-mono text-base tracking-wide">{a.number}</p>
              <p className="text-xs text-ash">a.n. {a.name}</p>
            </div>
            <Button variant="secondary" onClick={() => copy(a.number)}>{copied === a.number ? 'Tersalin' : 'Salin'}</Button>
          </li>
        ))}
      </ul>
      <a href={d.whatsappUrl} target="_blank" rel="noreferrer"
        className="mt-4 block rounded-lg bg-amber px-4 py-2.5 text-center text-sm font-semibold text-ink hover:bg-brick hover:text-paper">
        Sudah transfer? Konfirmasi via WhatsApp
      </a>
      <p className="mt-2 text-xs text-ash">Lampirkan foto bukti transfer di chat. Admin mengonfirmasi booking setelah DP diterima.</p>
    </Card>
  );
}

export default function Show({ booking: b }) {
  return (
    <PublicLayout title={`Booking ${b.code}`}>
      <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="font-mono text-xs tracking-wider text-ash">{b.code}</p>
          <h1 className="text-2xl font-bold tracking-tight">Halo, {b.name.split(' ')[0]}</h1>
        </div>
        <span className="rounded-full bg-ink/10 px-3 py-1 text-xs font-semibold">Pembayaran: {b.payment}</span>
      </div>

      {b.cancelled ? (
        <Card tone="danger" className="text-sm">
          <p className="font-semibold">Booking dibatalkan</p>
          {b.cancelReason && <p className="mt-1">Alasan: {b.cancelReason}</p>}
        </Card>
      ) : (
        <Card><Timeline steps={b.steps} /></Card>
      )}

      {b.deposit ? <DepositCard deposit={b.deposit} /> : (
      <Card tone="info" className="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div className="min-w-0">
          <p className="text-sm font-semibold">Langkah berikutnya</p>
          <p className="mt-1 text-sm">{b.next}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {b.resultsUrl && (
            <Link href={b.resultsUrl} className="rounded-lg bg-amber px-4 py-2 text-sm font-semibold text-ink hover:bg-brick hover:text-paper">Lihat hasil edit</Link>
          )}
          {b.gallery && (
            <Link href={b.gallery.url}
              className={`rounded-lg px-4 py-2 text-sm font-semibold ${b.resultsUrl ? 'border border-amber/50 bg-white text-brick' : 'bg-amber text-ink hover:bg-brick hover:text-paper'}`}>
              {b.gallery.open ? 'Pilih foto' : 'Lihat foto pilihan'}
            </Link>
          )}
        </div>
      </Card>
      )}

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
          <div className="mt-2 border-t border-ink/10 pt-2">
            <Row label="Total" strong>{rupiah(b.price.total)}</Row>
            <Row label="Sudah dibayar">{rupiah(b.paid)}</Row>
            <Row label="Sisa tagihan">{rupiah(Math.max(0, b.price.total - b.paid))}</Row>
          </div>
        </Card>
      </div>
    </PublicLayout>
  );
}
