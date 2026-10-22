import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '../../Components/gallery/ConfirmDialog';
import ContactSheet from '../../Components/gallery/ContactSheet';
import Lightbox from '../../Components/gallery/Lightbox';
import SelectionBar from '../../Components/gallery/SelectionBar';
import PublicLayout from '../../Layouts/PublicLayout';
import { rupiah } from '../../lib/format';
import useSelection from '../../lib/useSelection';

const FILTERS = {
  all: { label: 'Semua', test: () => true },
  selected: { label: 'Dipilih', test: (p) => p.selected },
  notes: { label: 'Dengan catatan', test: (p) => Boolean(p.note) },
};

export default function Select({ booking, photos: initialPhotos }) {
  const { errors } = usePage().props;
  const { photos, summary, error, toggle, writeNote, flush } = useSelection(booking, initialPhotos);
  const [filter, setFilter] = useState(booking.open ? 'all' : 'selected');
  const [viewing, setViewing] = useState(null);
  const [confirming, setConfirming] = useState(false);
  const [sending, setSending] = useState(false);

  const shown = photos.filter(FILTERS[filter].test);
  const submit = async () => {
    setSending(true);
    await flush();
    router.post(`/lacak/${booking.token}/foto/kirim`, {}, { onFinish: () => setSending(false) });
  };

  return (
    <PublicLayout title={`Pilih foto · ${booking.code}`} wide>
      <div className={booking.open ? 'pb-24' : ''}>
        <Link href={`/lacak/${booking.token}`} className="text-sm text-stone-500 hover:text-stone-900">← Kembali ke status booking</Link>
        <h1 className="mt-2 text-2xl font-bold tracking-tight">{booking.open ? 'Pilih foto untuk diedit' : 'Foto pilihanmu'}</h1>
        <p className="mt-1 max-w-2xl text-sm text-stone-600">
          {booking.open
            ? `Paketmu termasuk edit ${booking.quota} foto. Foto tambahan ${rupiah(booking.extraPrice)} per foto. Ketuk foto untuk melihat lebih besar dan menulis catatan.`
            : `Pilihan dikirim ${booking.submittedAt}. Fotografer sedang mengerjakan foto-foto ini.`}
        </p>
        {booking.open && booking.deadline && (
          <p className={`mt-2 text-sm ${booking.overdue ? 'font-semibold text-red-600' : 'text-stone-600'}`}>
            {booking.overdue ? `Batas memilih sudah lewat (${booking.deadline}). Segera kirim pilihanmu.` : `Batas memilih: ${booking.deadline}`}
          </p>
        )}
        {error && <p role="alert" className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}

        <div className="my-4 flex flex-wrap gap-2">
          {Object.entries(FILTERS).map(([key, f]) => (
            <button key={key} type="button" aria-pressed={filter === key} onClick={() => setFilter(key)}
              className={`rounded-full border px-3 py-1 text-sm ${filter === key ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-white text-stone-600'}`}>
              {f.label} ({photos.filter(f.test).length})
            </button>
          ))}
        </div>

        {/* Daftar dibekukan saat tampilan besar dibuka, agar tidak melompat ketika filter berubah isi. */}
        <ContactSheet photos={shown} open={booking.open} onToggle={toggle}
          onOpen={(p) => setViewing({ ids: shown.map((x) => x.id), index: shown.indexOf(p) })} />
      </div>

      {viewing && (
        <Lightbox photos={viewing.ids.map((id) => photos.find((p) => p.id === id))} index={viewing.index} open={booking.open}
          onIndex={(index) => setViewing({ ...viewing, index })} onClose={() => setViewing(null)} onToggle={toggle} onNote={writeNote} />
      )}
      {booking.open && <SelectionBar summary={summary} quota={booking.quota} onSubmit={() => setConfirming(true)} />}
      {confirming && (
        <ConfirmDialog summary={summary} quota={booking.quota} extraPrice={booking.extraPrice} sending={sending}
          error={errors.selection} onCancel={() => setConfirming(false)} onConfirm={submit} />
      )}
    </PublicLayout>
  );
}
