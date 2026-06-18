document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('jobiizy-back-btn');
  if (!btn) return;

  btn.addEventListener('click', function() {
    const fallback = 'https://jobiizy.com/emplois-offre-globale/';
    const referrer = document.referrer;

    if (referrer && referrer.includes('jobiizy.com')) {
      window.location.href = referrer;
    } else {
      window.location.href = fallback;
    }
  });
});