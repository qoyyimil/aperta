import { rupiah } from '../../lib/format';
import { Button, Field, Input, Textarea } from '../ui';

export default function ExtrasStep({ form, addons, voucher }) {
  const { data, setData, errors } = form;
  const qtyOf = (id) => data.addons.find((a) => a.id === id)?.qty ?? 0;
  const setQty = (addon, qty) => {
    const rest = data.addons.filter((a) => a.id !== addon.id);
    setData('addons', qty > 0 ? [...rest, { id: addon.id, qty: Math.min(qty, addon.max_qty) }] : rest);
  };

  return (
    <div className="space-y-6">
      <div>
        <p className="mb-2 text-sm font-medium">Add-on (opsional)</p>
        <ul className="divide-y divide-stone-200 rounded-lg border border-stone-200">
          {addons.map((a) => (
            <li key={a.id} className="flex items-center justify-between gap-3 px-3 py-2.5">
              <div className="min-w-0">
                <p className="text-sm">{a.name}</p>
                <p className="text-xs text-stone-500">{rupiah(a.price)}</p>
              </div>
              <div className="flex items-center gap-2">
                <Button variant="ghost" className="px-2.5 py-1" aria-label={`Kurangi ${a.name}`} disabled={!qtyOf(a.id)} onClick={() => setQty(a, qtyOf(a.id) - 1)}>−</Button>
                <span className="w-5 text-center text-sm tabular-nums">{qtyOf(a.id)}</span>
                <Button variant="ghost" className="px-2.5 py-1" aria-label={`Tambah ${a.name}`} disabled={qtyOf(a.id) >= a.max_qty} onClick={() => setQty(a, qtyOf(a.id) + 1)}>+</Button>
              </div>
            </li>
          ))}
        </ul>
      </div>

      <Field label="Kode voucher" error={voucher.error ?? errors.voucher} hint={voucher.applied && `Voucher ${voucher.applied} dipakai: hemat ${rupiah(voucher.discount)}.`}>
        <div className="flex gap-2">
          <Input value={data.voucher} placeholder="Opsional" onChange={(e) => { setData('voucher', e.target.value.toUpperCase()); voucher.clear(); }} />
          <Button variant="ghost" disabled={!data.voucher} onClick={() => voucher.check(data.voucher)}>Pakai</Button>
        </div>
      </Field>

      <Field label="Catatan untuk fotografer" error={errors.notes}>
        <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="Mis. jumlah orang, konsep foto, warna toga." />
      </Field>
    </div>
  );
}
