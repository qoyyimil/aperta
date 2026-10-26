import { useMemo, useRef, useState } from 'react';
import { sendJson } from './http';

/**
 * State pilihan foto klien. Perubahan tampil langsung, disimpan ke server,
 * dan dikembalikan jika server menolak. Catatan disimpan setelah klien berhenti mengetik.
 */
export default function useSelection(booking, initialPhotos) {
  const [photos, setPhotos] = useState(initialPhotos);
  const [error, setError] = useState(null);
  const pending = useRef({});

  const patch = (id, changes) => setPhotos((list) => list.map((p) => (p.id === id ? { ...p, ...changes } : p)));

  const save = (id, body, undo) =>
    sendJson(`/lacak/${booking.token}/foto/${id}`, 'PATCH', body)
      .then(({ photo }) => {
        // Hanya catatan yang bisa mengubah status pilih di server; jawaban toggle bisa datang tidak urut.
        if ('note' in body) patch(id, { selected: photo.selected });
        setError(null);
      })
      .catch((e) => { if (undo) patch(id, undo); setError(e.message); });

  const toggle = (photo) => {
    patch(photo.id, { selected: !photo.selected });
    save(photo.id, { selected: !photo.selected }, { selected: photo.selected });
  };

  const writeNote = (photo, note) => {
    patch(photo.id, { note, selected: photo.selected || note.trim() !== '' });
    clearTimeout(pending.current[photo.id]?.timer);
    const run = () => { delete pending.current[photo.id]; return save(photo.id, { note }); };
    pending.current[photo.id] = { run, timer: setTimeout(run, 600) };
  };

  /** Simpan semua catatan yang belum terkirim, dipanggil sebelum mengirim pilihan. */
  const flush = () => Promise.all(Object.values(pending.current).map(({ timer, run }) => { clearTimeout(timer); return run(); }));

  const summary = useMemo(() => {
    const selected = photos.filter((p) => p.selected).length;
    const extra = Math.max(0, selected - booking.quota);
    return { selected, extra, extraCost: extra * booking.extraPrice, notes: photos.filter((p) => p.selected && p.note).length };
  }, [photos, booking]);

  return { photos, summary, error, toggle, writeNote, flush };
}
