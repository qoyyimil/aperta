import { rupiah } from '../../lib/format';
import { Card, Row } from '../ui';

export default function Summary({ pkg, addons, picked, price, depositPercent, fullPaymentDays }) {
  return (
    <Card className="h-fit md:sticky md:top-6">
      <h2 className="mb-3 font-semibold">Ringkasan</h2>
      {pkg ? <Row label={pkg.name}>{rupiah(pkg.price)}</Row> : <p className="text-sm text-stone-500">Belum memilih paket.</p>}
      {picked.map(({ id, qty }) => {
        const addon = addons.find((a) => a.id === id);
        return <Row key={id} label={`${addon.name} × ${qty}`}>{rupiah(addon.price * qty)}</Row>;
      })}
      {price.discount > 0 && <Row label="Diskon voucher">−{rupiah(price.discount)}</Row>}
      <div className="mt-2 border-t border-stone-200 pt-2">
        <Row label="Total" strong>{rupiah(price.total)}</Row>
      </div>
      {pkg && <p className="mt-3 text-xs text-stone-500">DP minimal {depositPercent}% ({rupiah(Math.floor((price.total * depositPercent) / 100))}) dibayar setelah booking. Pelunasan paling lambat H-{fullPaymentDays} sebelum sesi.</p>}
    </Card>
  );
}
