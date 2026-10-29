import { useEffect, useRef, useState } from 'react';

const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let loading;

const loadScript = () => (loading ??= new Promise((resolve, reject) => {
  const script = document.createElement('script');
  script.src = SCRIPT;
  script.async = true;
  script.onload = resolve;
  script.onerror = () => {
    loading = undefined; // boleh dicoba lagi saat widget dipasang ulang
    script.remove();
    reject();
  };
  document.head.append(script);
}));

/** Widget Cloudflare Turnstile. Ganti `attempt` untuk meminta token baru. */
export default function Captcha({ siteKey, attempt, onToken }) {
  const box = useRef(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let id;
    let gone = false;
    setFailed(false);
    loadScript()
      .then(() => {
        if (!gone) id = window.turnstile.render(box.current, { sitekey: siteKey, callback: onToken, 'expired-callback': () => onToken('') });
      })
      .catch(() => !gone && setFailed(true));

    return () => {
      gone = true;
      if (id !== undefined) window.turnstile.remove(id);
    };
  }, [siteKey, attempt]);

  return failed ? (
    <p role="alert" className="text-xs text-brick">
      Verifikasi keamanan tidak bisa dimuat. Matikan pemblokir iklan atau coba jaringan lain, lalu muat ulang halaman.
    </p>
  ) : (
    <div ref={box} />
  );
}
