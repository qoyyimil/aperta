/** Ringkasan harga di sisi klien, hanya untuk tampilan. Server menghitung ulang saat booking dikirim. */
export function totals(pkg, catalog, picked, discount = 0) {
  const addons = picked.reduce((sum, { id, qty }) => sum + (catalog.find((a) => a.id === id)?.price ?? 0) * qty, 0);
  const subtotal = (pkg?.price ?? 0) + addons;

  return { package: pkg?.price ?? 0, addons, subtotal, discount, total: Math.max(0, subtotal - discount) };
}

export const duration = (minutes) => (minutes % 60 ? `${minutes} menit` : `${minutes / 60} jam`);
