import { longDate } from '../../lib/format';
import { Row } from '../ui';

export default function ReviewStep({ form, pkg }) {
  const { data, setData, errors } = form;

  return (
    <div className="space-y-5">
      <div>
        <Row label="Nama">{data.name}</Row>
        <Row label="WhatsApp">{data.whatsapp}</Row>
        <Row label="Email">{data.email}</Row>
        <Row label="Paket">{pkg?.name}</Row>
        <Row label="Jadwal">{data.session_date && `${longDate(data.session_date)}, ${data.starts_at}`}</Row>
        <Row label="Lokasi">{[data.location, data.institution, data.city].filter(Boolean).join(', ')}</Row>
      </div>
      <label className="flex items-start gap-3 text-sm">
        <input type="checkbox" className="mt-0.5 size-4 accent-brick" checked={data.consent} onChange={(e) => setData('consent', e.target.checked)} />
        <span>Saya menyetujui kebijakan privasi dan syarat layanan studio, termasuk penyimpanan data saya untuk keperluan booking.</span>
      </label>
      {errors.consent && <p className="text-xs text-brick">{errors.consent}</p>}
    </div>
  );
}
