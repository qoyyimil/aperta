import { Field, Input, Select } from '../ui';

export default function ClientStep({ form, cities }) {
  const { data, setData, errors } = form;
  const bind = (name) => ({ name, value: data[name], onChange: (e) => setData(name, e.target.value) });

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <Field label="Nama lengkap *" error={errors.name}><Input {...bind('name')} autoComplete="name" /></Field>
      <Field label="Email *" error={errors.email} hint="Ringkasan booking dikirim ke email ini.">
        <Input type="email" {...bind('email')} autoComplete="email" />
      </Field>
      <Field label="WhatsApp *" error={errors.whatsapp}><Input type="tel" placeholder="081234567890" {...bind('whatsapp')} autoComplete="tel" /></Field>
      <Field label="Instagram" error={errors.instagram}><Input placeholder="@username" {...bind('instagram')} /></Field>
      <Field label="Kota / area *" error={errors.city}>
        <Select {...bind('city')}>
          <option value="">Pilih kota</option>
          {cities.map((c) => <option key={c}>{c}</option>)}
        </Select>
      </Field>
      <Field label="Kampus / instansi" error={errors.institution}><Input {...bind('institution')} /></Field>
      <div className="sm:col-span-2">
        <Field label="Lokasi sesi" error={errors.location} hint="Mis. Gedung Rektorat ITS, pintu utara.">
          <Input {...bind('location')} />
        </Field>
      </div>
    </div>
  );
}
