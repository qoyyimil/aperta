import { rupiah } from '../../lib/format';
import { Button, Row } from '../ui';

/** Ringkasan sebelum pilihan dikunci (SEL-8). */
export default function ConfirmDialog({ summary, quota, extraPrice, sending, error, onCancel, onConfirm }) {
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Kirim pilihan">
      <div className="w-full max-w-md space-y-4 rounded-xl bg-white p-6">
        <h2 className="text-xl font-bold">Kirim {summary.selected} foto ke editor?</h2>
        <div className="border-y border-stone-200 py-2">
          <Row label="Termasuk paket">{Math.min(summary.selected, quota)} foto</Row>
          <Row label="Foto tambahan">{summary.extra} × {rupiah(extraPrice)}</Row>
          <Row label="Foto dengan catatan">{summary.notes}</Row>
          <Row label="Biaya tambahan" strong>{rupiah(summary.extraCost)}</Row>
        </div>
        <p className="text-sm text-stone-600">Setelah dikirim, pilihan dikunci dan fotografer mulai mengedit. Hubungi admin jika ingin mengubahnya.</p>
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button variant="ghost" disabled={sending} onClick={onCancel}>Cek lagi</Button>
          <Button disabled={sending} onClick={onConfirm}>{sending ? 'Mengirim…' : 'Kirim pilihan'}</Button>
        </div>
      </div>
    </div>
  );
}
