import { useEffect, useRef } from 'react';
import { Button } from '../ui';

/** Tampilan besar satu foto: navigasi, pilih, dan catatan untuk editor (SEL-5, SEL-6). */
export default function Lightbox({ photos, index, open, onIndex, onClose, onToggle, onNote }) {
  const photo = photos[index];
  const touchX = useRef(null);
  const go = (step) => { const i = index + step; if (i >= 0 && i < photos.length) onIndex(i); };

  useEffect(() => {
    const onKey = (e) => {
      if (e.target.tagName === 'TEXTAREA') return;
      if (e.key === 'Escape') onClose();
      if (e.key === 'ArrowLeft') go(-1);
      if (e.key === 'ArrowRight') go(1);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  });

  if (!photo) return null;

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/85 p-3 sm:p-6" role="dialog" aria-modal="true" aria-label={photo.filename}
      onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="grid max-h-full w-full max-w-5xl overflow-auto rounded-xl bg-white md:grid-cols-[1fr_18rem]">
        <div className="relative grid min-h-60 place-items-center bg-black"
          onTouchStart={(e) => { touchX.current = e.touches[0].clientX; }}
          onTouchEnd={(e) => { const dx = e.changedTouches[0].clientX - touchX.current; if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1); }}>
          <img src={photo.preview} alt={`Frame ${photo.frame}`} className="max-h-[60vh] max-w-full object-contain md:max-h-[80vh]" />
          <NavButton side="left" disabled={index === 0} onClick={() => go(-1)} />
          <NavButton side="right" disabled={index === photos.length - 1} onClick={() => go(1)} />
        </div>
        <div className="flex flex-col gap-4 p-5">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <p className="text-xs uppercase tracking-wider text-stone-500">Frame {photo.frame} · {index + 1} dari {photos.length}</p>
              <p className="truncate font-mono text-sm">{photo.filename}</p>
            </div>
            <Button variant="ghost" className="px-3 py-1.5" onClick={onClose}>Tutup</Button>
          </div>
          {open ? (
            <Button variant={photo.selected ? 'ghost' : 'primary'} onClick={() => onToggle(photo)}>
              {photo.selected ? 'Batalkan pilihan' : 'Pilih untuk diedit'}
            </Button>
          ) : (
            <p className="text-sm font-medium">{photo.selected ? 'Dipilih untuk diedit' : 'Tidak dipilih'}</p>
          )}
          <label className="block space-y-1.5">
            <span className="text-sm font-medium">Catatan untuk editor</span>
            <textarea rows={4} maxLength={300} readOnly={!open} value={photo.note ?? ''} onChange={(e) => onNote(photo, e.target.value)}
              placeholder="Mis. hapus jerawat, crop portrait, warna lebih hangat"
              className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            {open && <span className="block text-xs text-stone-500">Tersimpan otomatis. Menulis catatan otomatis memilih foto ini.</span>}
          </label>
        </div>
      </div>
    </div>
  );
}

function NavButton({ side, disabled, onClick }) {
  if (disabled) return null;
  return (
    <button type="button" onClick={onClick} aria-label={side === 'left' ? 'Foto sebelumnya' : 'Foto berikutnya'}
      className={`absolute top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 ${side === 'left' ? 'left-3' : 'right-3'}`}>
      {side === 'left' ? '‹' : '›'}
    </button>
  );
}
