import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import ClientStep from '../../Components/booking/ClientStep';
import ExtrasStep from '../../Components/booking/ExtrasStep';
import ReviewStep from '../../Components/booking/ReviewStep';
import ScheduleStep from '../../Components/booking/ScheduleStep';
import Summary from '../../Components/booking/Summary';
import { Button, Card } from '../../Components/ui';
import PublicLayout from '../../Layouts/PublicLayout';
import { totals } from '../../lib/pricing';
import useVoucher from '../../lib/useVoucher';

/** Urutan langkah, field yang divalidasi server per langkah, dan field wajib sebelum lanjut (BOOK-1). */
const STEPS = [
  { title: 'Data klien', view: ClientStep, fields: ['name', 'email', 'whatsapp', 'instagram', 'city', 'location', 'institution'], required: ['name', 'email', 'whatsapp', 'city'] },
  { title: 'Paket & jadwal', view: ScheduleStep, fields: ['package_id', 'session_date', 'starts_at'], required: ['package_id', 'session_date', 'starts_at'] },
  { title: 'Add-on & voucher', view: ExtrasStep, fields: ['addons', 'voucher', 'notes'], required: [] },
  { title: 'Konfirmasi', view: ReviewStep, fields: ['consent'], required: ['consent'] },
];

const stepOfError = (errors) =>
  Math.max(0, STEPS.findIndex((s) => Object.keys(errors).some((key) => s.fields.includes(key.split('.')[0]))));

export default function Create({ packages, addons, cities, window: range, depositPercent, fullPaymentDays }) {
  const form = useForm({
    name: '', email: '', whatsapp: '', instagram: '', city: '', location: '', institution: '',
    package_id: '', session_date: '', starts_at: '', addons: [], voucher: '', notes: '', consent: false,
  });
  const [step, setStep] = useState(0);
  const pkg = packages.find((p) => p.id === form.data.package_id);
  const voucher = useVoucher(totals(pkg, addons, form.data.addons).subtotal);
  const price = totals(pkg, addons, form.data.addons, voucher.discount);

  const { view: Step, required } = STEPS[step];
  const last = step === STEPS.length - 1;
  const ready = required.every((field) => Boolean(form.data[field]));

  const submit = () => form.post('/booking', { preserveScroll: true, onError: (errors) => setStep(stepOfError(errors)) });

  return (
    <PublicLayout title="Booking jadwal">
      <h1 className="text-2xl font-bold tracking-tight">Booking jadwal foto</h1>
      <p className="mt-1 text-sm text-stone-600">Isi data singkat, pilih paket dan jam, lalu kirim.</p>

      <ol className="my-6 grid grid-cols-4 gap-2 text-xs">
        {STEPS.map((s, i) => (
          <li key={s.title} className={`border-t-2 pt-2 ${i <= step ? 'border-indigo-600 font-semibold text-stone-900' : 'border-stone-200 text-stone-400'}`}>
            <span className="tabular-nums">{i + 1}.</span> {s.title}
          </li>
        ))}
      </ol>

      <div className="grid gap-6 md:grid-cols-[1fr_17rem]">
        <Card>
          <Step {...{ form, packages, addons, cities, range, pkg, voucher }} />
          <div className="mt-6 flex justify-between border-t border-stone-200 pt-4">
            <Button variant="ghost" disabled={step === 0} onClick={() => setStep(step - 1)}>Kembali</Button>
            {last ? (
              <Button disabled={!ready || form.processing} onClick={submit}>{form.processing ? 'Mengirim…' : 'Kirim booking'}</Button>
            ) : (
              <Button disabled={!ready} onClick={() => setStep(step + 1)}>Lanjut</Button>
            )}
          </div>
        </Card>
        <Summary pkg={pkg} addons={addons} picked={form.data.addons} price={price} depositPercent={depositPercent} fullPaymentDays={fullPaymentDays} />
      </div>
    </PublicLayout>
  );
}
