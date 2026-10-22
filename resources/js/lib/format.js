export const rupiah = (n) => `Rp${Number(n || 0).toLocaleString('id-ID')}`;

export const longDate = (iso) =>
  new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
