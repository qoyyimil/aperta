import { useEffect, useState } from 'react';
import { rupiah } from '../../lib/format';
import { duration } from '../../lib/pricing';
import { Field, Input } from '../ui';

export default function ScheduleStep({ form, packages, range }) {
  const { data, setData, errors } = form;
  const [times, setTimes] = useState([]);
  const [loading, setLoading] = useState(false);

  // Ambil jam yang tersedia setiap kali paket atau tanggal berubah (BOOK-4, BOOK-5).
  useEffect(() => {
    if (!data.package_id || !data.session_date) return;
    const ctrl = new AbortController();
    setLoading(true);
    fetch(`/availability?date=${data.session_date}&package=${data.package_id}`, { signal: ctrl.signal, headers: { Accept: 'application/json' } })
      .then((r) => r.json())
      .then(({ times = [] }) => {
        setTimes(times);
        if (!times.includes(data.starts_at)) setData('starts_at', '');
      })
      .catch(() => {})
      .finally(() => setLoading(false));
    return () => ctrl.abort();
  }, [data.package_id, data.session_date]);

  return (
    <div className="space-y-6">
      <fieldset>
        <legend className="mb-2 text-sm font-medium">Paket *</legend>
        <div className="grid gap-2 sm:grid-cols-2">
          {packages.map((p) => (
            <label key={p.id} className={`cursor-pointer rounded-lg border p-3 ${data.package_id === p.id ? 'border-amber ring-2 ring-amber/25' : 'border-ink/20 hover:border-ink/30'}`}>
              <input type="radio" name="package_id" className="sr-only" checked={data.package_id === p.id} onChange={() => setData('package_id', p.id)} />
              <span className="block text-xs text-ash">{p.service} · {duration(p.duration_minutes)}</span>
              <span className="block font-medium">{p.name}</span>
              <span className="block text-sm text-ash">{rupiah(p.price)} · edit {p.edit_quota} foto</span>
            </label>
          ))}
        </div>
        {errors.package_id && <p className="mt-2 text-xs text-brick">{errors.package_id}</p>}
      </fieldset>

      <Field label="Tanggal sesi *" error={errors.session_date} hint="Jika tanggal acara belum pasti, pilih perkiraan. Admin akan menghubungi kamu.">
        <Input type="date" min={range.min} max={range.max} value={data.session_date} onChange={(e) => setData('session_date', e.target.value)} />
      </Field>

      {data.package_id && data.session_date && (
        <div>
          <p className="mb-2 text-sm font-medium">Jam mulai *</p>
          {loading ? (
            <p className="text-sm text-ash">Memuat jadwal…</p>
          ) : times.length ? (
            <div className="grid grid-cols-4 gap-2 sm:grid-cols-6">
              {times.map((t) => (
                <button key={t} type="button" onClick={() => setData('starts_at', t)}
                  className={`rounded-lg border py-2 text-sm tabular-nums ${data.starts_at === t ? 'border-amber bg-amber text-ink' : 'border-ink/20 hover:border-ink/40'}`}>
                  {t}
                </button>
              ))}
            </div>
          ) : (
            <p className="text-sm text-ash">Tidak ada jam tersedia di tanggal ini. Coba tanggal lain.</p>
          )}
          {errors.starts_at && <p className="mt-2 text-xs text-brick">{errors.starts_at}</p>}
        </div>
      )}
    </div>
  );
}
