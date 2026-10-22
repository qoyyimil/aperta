import { useEffect, useRef, useState } from 'react';
import { Button } from '../ui';

/** Tampilan besar hasil edit dengan perbandingan sebelum/sesudah (DLV-7). */
export default function ResultLightbox({ results, index, downloadUrl, onIndex, onClose }) {
  const result = results[index];
  const [before, setBefore] = useState(false);
  const touchX = useRef(null);
  const go = (step) => { const i = index + step; if (i >= 0 && i < results.length) { setBefore(false); onIndex(i); } };

  useEffect(() => {
    const onKey = (e) => ({ Escape: onClose, ArrowLeft: () => go(-1), ArrowRight: () => go(1) })[e.key]?.();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  });

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-ink/85 p-3 sm:p-6" role="dialog" aria-modal="true" aria-label={result.filename}
      onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="grid max-h-full w-full max-w-5xl overflow-auto rounded-xl bg-white md:grid-cols-[1fr_18rem]">
        <div className="relative grid min-h-60 place-items-center bg-ink"
          onTouchStart={(e) => { touchX.current = e.touches[0].clientX; }}
          onTouchEnd={(e) => { const dx = e.changedTouches[0].clientX - touchX.current; if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1); }}>
          <img src={before ? result.before : result.preview} alt={result.filename} className="max-h-[60vh] max-w-full object-contain md:max-h-[80vh]" />
          {before && <span className="absolute left-3 top-3 rounded-full bg-ink/70 px-3 py-1 text-xs font-semibold text-paper">Sebelum diedit</span>}
          {index > 0 && <Nav side="left" onClick={() => go(-1)} />}
          {index < results.length - 1 && <Nav side="right" onClick={() => go(1)} />}
        </div>
        <div className="flex flex-col gap-4 p-5">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <p className="text-xs uppercase tracking-wider text-ash">Hasil {index + 1} dari {results.length}</p>
              <p className="truncate font-mono text-sm">{result.filename}</p>
            </div>
            <Button variant="ghost" className="px-3 py-1.5" onClick={onClose}>Tutup</Button>
          </div>
          {result.before && (
            <Button variant="ghost" onClick={() => setBefore(!before)}>{before ? 'Lihat hasil edit' : 'Bandingkan dengan sebelum diedit'}</Button>
          )}
          {result.note && (
            <div>
              <p className="text-xs uppercase tracking-wider text-ash">Catatanmu untuk editor</p>
              <p className="mt-1 text-sm">{result.note}</p>
            </div>
          )}
          {downloadUrl && (
            <a href={downloadUrl(result)} className="mt-auto rounded-lg bg-amber px-4 py-2 text-center text-sm font-semibold text-ink hover:bg-brick hover:text-paper">
              Unduh resolusi penuh
            </a>
          )}
        </div>
      </div>
    </div>
  );
}

function Nav({ side, onClick }) {
  return (
    <button type="button" onClick={onClick} aria-label={side === 'left' ? 'Hasil sebelumnya' : 'Hasil berikutnya'}
      className={`absolute top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-paper/15 text-2xl text-paper hover:bg-paper/25 ${side === 'left' ? 'left-3' : 'right-3'}`}>
      {side === 'left' ? '‹' : '›'}
    </button>
  );
}
