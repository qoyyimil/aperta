import { useEffect, useRef } from 'react';

const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let loading;

const loadScript = () => (loading ??= new Promise((resolve) => {
  const script = document.createElement('script');
  script.src = SCRIPT;
  script.async = true;
  script.onload = resolve;
  document.head.append(script);
}));

/** Widget Cloudflare Turnstile. Ganti `attempt` untuk meminta token baru. */
export default function Captcha({ siteKey, attempt, onToken }) {
  const box = useRef(null);

  useEffect(() => {
    let id;
    let gone = false;
    loadScript().then(() => {
      if (!gone) id = window.turnstile.render(box.current, { sitekey: siteKey, callback: onToken, 'expired-callback': () => onToken('') });
    });

    return () => {
      gone = true;
      if (id !== undefined) window.turnstile.remove(id);
    };
  }, [siteKey, attempt]);

  return <div ref={box} />;
}
