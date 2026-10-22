const control =
  'w-full rounded-lg border border-ink/20 bg-white px-3 py-2 text-sm placeholder:text-ash/70 ' +
  'focus:border-amber focus:outline-none focus:ring-2 focus:ring-amber/25';

const tones = {
  default: 'border-ink/10 bg-white',
  info: 'border-ink/10 bg-paper',
  warning: 'border-amber/40 bg-amber/10 text-ink',
  danger: 'border-brick/25 bg-brick/10 text-brick',
};

export function Field({ label, error, hint, children }) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-medium">{label}</span>
      {children}
      {error ? <span className="block text-xs text-brick">{error}</span> : hint && <span className="block text-xs text-ash">{hint}</span>}
    </label>
  );
}

export const Input = (props) => <input {...props} className={control} />;
export const Textarea = (props) => <textarea rows={3} {...props} className={control} />;
export const Select = ({ children, ...props }) => <select {...props} className={control}>{children}</select>;

export function Button({ variant = 'primary', className = '', ...props }) {
  const look = variant === 'primary'
    ? 'bg-amber text-ink hover:bg-brick hover:text-paper'
    : 'border border-ink/20 bg-white text-ink hover:bg-paper';
  return <button type="button" {...props} className={`rounded-lg px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50 ${look} ${className}`} />;
}

export const Card = ({ tone = 'default', className = '', ...props }) => (
  <div {...props} className={`rounded-xl border p-5 ${tones[tone]} ${className}`} />
);

export function Row({ label, children, strong }) {
  return (
    <div className={`flex justify-between gap-4 py-1.5 text-sm ${strong ? 'font-semibold' : ''}`}>
      <span className={strong ? '' : 'text-ash'}>{label}</span>
      <span className="text-right tabular-nums">{children}</span>
    </div>
  );
}
