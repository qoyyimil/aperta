import { rupiah } from '../../lib/format';
import { Button } from '../ui';

/** Penghitung pilihan vs kuota, selalu terlihat di bawah layar (SEL-3). */
export default function SelectionBar({ summary, quota, onSubmit }) {
  const pct = Math.min(100, (summary.selected / Math.max(quota, 1)) * 100);

  return (
    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3">
          <p className="text-2xl font-bold tabular-nums">{summary.selected}<span className="text-base font-normal text-stone-500"> / {quota}</span></p>
          <span className="h-1.5 w-32 overflow-hidden rounded-full bg-stone-200">
            <span className={`block h-full ${summary.extra ? 'bg-red-500' : 'bg-stone-900'}`} style={{ width: `${pct}%` }} />
          </span>
          <span className={`text-sm ${summary.extra ? 'font-semibold text-red-600' : 'text-stone-500'}`}>
            {summary.extra ? `+${summary.extra} foto tambahan · ${rupiah(summary.extraCost)}` : `${quota - summary.selected} slot edit tersisa`}
          </span>
        </div>
        <Button disabled={!summary.selected} onClick={onSubmit}>Kirim pilihan</Button>
      </div>
    </div>
  );
}
