/** Tahap booking ala "Steps": horizontal (label di bawah) di layar lebar, vertikal di HP (TRK-5). */
export default function Timeline({ steps }) {
  const line = (step) => (step.state === 'done' ? 'bg-indigo-600' : 'bg-stone-200');

  return (
    <>
      <ol className="hidden md:flex">
        {steps.map((step, i) => (
          <li key={step.label} className="relative flex-1 px-1 text-center">
            {i < steps.length - 1 && (
              <span className={`absolute top-3.5 left-[calc(50%+1.25rem)] right-[calc(-50%+1.25rem)] h-px ${line(step)}`} />
            )}
            <Dot step={step} number={i + 1} className="mx-auto" />
            <Text step={step} className="mt-2" />
          </li>
        ))}
      </ol>

      <ol className="md:hidden">
        {steps.map((step, i) => (
          <li key={step.label} className="flex gap-3">
            <div className="flex flex-col items-center">
              <Dot step={step} number={i + 1} />
              {i < steps.length - 1 && <span className={`my-1 min-h-5 w-px flex-1 ${line(step)}`} />}
            </div>
            <Text step={step} className="pb-4" />
          </li>
        ))}
      </ol>
    </>
  );
}

function Dot({ step, number, className = '' }) {
  const look = {
    done: 'border-indigo-600 text-indigo-600',
    current: 'border-indigo-600 bg-indigo-600 text-white',
    todo: 'border-stone-300 bg-white text-stone-400',
  }[step.state];

  return (
    <span className={`relative grid size-7 shrink-0 place-items-center rounded-full border text-xs font-semibold ${look} ${className}`}>
      {step.state === 'done' ? '✓' : number}
    </span>
  );
}

function Text({ step, className = '' }) {
  return (
    <div className={`min-w-0 ${className}`}>
      <p className={`text-sm ${step.state === 'todo' ? 'text-stone-400' : 'font-semibold text-stone-900'}`}>
        {step.label}
        {step.state === 'current' && <span className="sr-only"> (tahap sekarang)</span>}
      </p>
      <p className="mt-0.5 text-xs text-stone-500">{step.content}</p>
    </div>
  );
}