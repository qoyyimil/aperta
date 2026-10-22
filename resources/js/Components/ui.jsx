const control =
  'w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm placeholder:text-stone-400 ' +
  'focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20';

const tones = {
  default: 'border-stone-200 bg-white',
  info: 'border-indigo-100 bg-indigo-50',
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  danger: 'border-red-200 bg-red-50 text-red-800',
};

export function Field({ label, error, hint, children }) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-medium">{label}</span>
      {children}
      {error ? <span className="block text-xs text-red-600">{error}</span> : hint && <span className="block text-xs text-stone-500">{hint}</span>}
    </label>
  );
}

export const Input = (props) => <input {...props} className={control} />;
export const Textarea = (props) => <textarea rows={3} {...props} className={control} />;
export const Select = ({ children, ...props }) => <select {...props} className={control}>{children}</select>;

export function Button({ variant = 'primary', className = '', ...props }) {
  const look = variant === 'primary'
    ? 'bg-indigo-600 text-white hover:bg-indigo-700'
    : 'border border-stone-300 bg-white text-stone-700 hover:bg-stone-50';
  return <button type="button" {...props} className={`rounded-lg px-4 py-2 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50 ${look} ${className}`} />;
}

export const Card = ({ tone = 'default', className = '', ...props }) => (
  <div {...props} className={`rounded-xl border p-5 ${tones[tone]} ${className}`} />
);

export function Row({ label, children, strong }) {
  return (
    <div className={`flex justify-between gap-4 py-1.5 text-sm ${strong ? 'font-semibold' : ''}`}>
      <span className={strong ? '' : 'text-stone-500'}>{label}</span>
      <span className="text-right tabular-nums">{children}</span>
    </div>
  );
}
