import { useEffect, useState } from 'react';

const empty = { applied: null, discount: 0, error: null };

/** Cek voucher ke server; dihitung ulang otomatis jika subtotal berubah. */
export default function useVoucher(amount) {
  const [state, setState] = useState(empty);

  const check = async (code) => {
    if (!code) return setState(empty);
    const res = await fetch(`/voucher?code=${encodeURIComponent(code)}&amount=${amount}`, { headers: { Accept: 'application/json' } });
    const body = await res.json();
    setState(res.ok ? { applied: body.code, discount: body.discount, error: null } : { ...empty, error: body.message });
  };

  useEffect(() => {
    if (state.applied) check(state.applied);
  }, [amount]);

  return { ...state, check, clear: () => setState(empty) };
}
