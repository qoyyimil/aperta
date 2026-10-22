import { useForm } from '@inertiajs/react';
import { Button, Card, Field, Input } from '../../Components/ui';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Find() {
  const { data, setData, post, errors, processing } = useForm({ code: '', phone: '' });
  const submit = (e) => { e.preventDefault(); post('/lacak'); };

  return (
    <PublicLayout title="Lacak booking">
      <Card className="mx-auto max-w-md">
        <h1 className="text-xl font-bold">Lacak booking</h1>
        <p className="mt-1 text-sm text-ash">Masukkan kode booking dari email atau WhatsApp kamu.</p>
        <form onSubmit={submit} className="mt-5 space-y-4">
          <Field label="Kode booking" error={errors.code}>
            <Input value={data.code} placeholder="AP-XXXXXX" onChange={(e) => setData('code', e.target.value.toUpperCase())} />
          </Field>
          <Field label="4 digit terakhir nomor WhatsApp" error={errors.phone}>
            <Input value={data.phone} inputMode="numeric" maxLength={4} placeholder="2140" onChange={(e) => setData('phone', e.target.value.replace(/\D/g, ''))} />
          </Field>
          <Button type="submit" className="w-full" disabled={processing || !data.code || data.phone.length !== 4}>Lihat status</Button>
        </form>
      </Card>
    </PublicLayout>
  );
}
