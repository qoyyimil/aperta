/** Grid foto ala contact sheet; foto terpilih dilingkari seperti tanda spidol. */
export default function ContactSheet({ photos, open, onOpen, onToggle }) {
  if (!photos.length) {
    return <p className="rounded-lg bg-ink px-4 py-12 text-center text-sm text-paper/60">Tidak ada foto di filter ini.</p>;
  }

  return (
    <div className="grid grid-cols-2 gap-x-3 gap-y-5 rounded-lg bg-ink p-3 sm:grid-cols-3 sm:p-4 lg:grid-cols-4">
      {photos.map((p) => (
        <figure key={p.id} className="relative min-w-0">
          <button type="button" onClick={() => onOpen(p)} aria-label={`Lihat ${p.filename}`}
            className={`relative block aspect-[3/2] w-full overflow-hidden rounded bg-ink focus-visible:outline-2 focus-visible:outline-amber ${p.selected ? 'ring-2 ring-amber' : ''}`}>
            <img src={p.thumb} alt={`Frame ${p.frame}`} loading="lazy" className="h-full w-full object-contain" />
            {p.note && <span className="absolute bottom-1.5 left-1.5 rounded bg-ink/70 px-1.5 py-0.5 text-[11px] text-paper">catatan</span>}
          </button>
          {open && (
            <button type="button" onClick={() => onToggle(p)} aria-pressed={p.selected}
              aria-label={`${p.selected ? 'Batalkan' : 'Pilih'} ${p.filename}`}
              className={`absolute right-1.5 top-1.5 grid size-7 place-items-center rounded-full text-sm leading-none ${p.selected ? 'bg-amber text-ink' : 'border border-paper/70 bg-ink/30 text-paper'}`}>
              {p.selected ? '✓' : ''}
            </button>
          )}
          <figcaption className="mt-1.5 flex justify-between gap-2 font-mono text-[11px] text-paper/60">
            <b className={p.selected ? 'text-paper' : ''}>{String(p.frame).padStart(2, '0')}</b>
            <span className="truncate">{p.filename}</span>
          </figcaption>
        </figure>
      ))}
    </div>
  );
}
