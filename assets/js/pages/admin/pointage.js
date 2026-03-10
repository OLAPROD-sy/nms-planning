document.addEventListener('DOMContentLoaded', () => {
  const config = document.getElementById('pointage-config');
  if (!config) return;

  const siteLat = parseFloat(config.dataset.siteLat || '0');
  const siteLng = parseFloat(config.dataset.siteLng || '0');
  const hasArrived = config.dataset.hasArrived === '1';

  function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const p1 = lat1 * Math.PI / 180;
    const p2 = lat2 * Math.PI / 180;
    const dp = (lat2 - lat1) * Math.PI / 180;
    const dl = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dp / 2) ** 2 + Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function init() {
    setInterval(() => {
      const now = new Date();
      const timeEl = document.getElementById('heure-actuelle');
      const dateEl = document.getElementById('date-actuelle');
      if (timeEl) timeEl.textContent = now.toLocaleTimeString('fr-FR');
      if (dateEl) {
        dateEl.textContent = now.toLocaleDateString('fr-FR', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      }
    }, 1000);

    if (navigator.geolocation) {
      navigator.geolocation.watchPosition(pos => {
        const uLat = pos.coords.latitude;
        const uLng = pos.coords.longitude;
        document.querySelectorAll('.lat_input').forEach(i => (i.value = uLat));
        document.querySelectorAll('.lng_input').forEach(i => (i.value = uLng));

        const dist = calculateDistance(uLat, uLng, siteLat, siteLng);
        const badge = document.getElementById('geo-status');
        const btnArrivee = document.getElementById('btn-arrivee');

        if (!badge) return;

        if (dist < 150000) {
          badge.className = 'status-badge status-working';
          badge.innerHTML = '<i class="bi bi-geo-alt"></i> Sur site';
          if (!hasArrived && btnArrivee) btnArrivee.disabled = false;
        } else {
          badge.className = 'status-badge status-waiting';
          badge.innerHTML = '<i class="bi bi-geo-alt"></i> Trop loin';
        }
      });
    }
  }

  const dateDebut = document.getElementById('date_debut');
  const dateFin = document.getElementById('date_fin');
  if (dateDebut && dateFin) {
    dateDebut.addEventListener('change', function() {
      dateFin.value = this.value;
    });
  }

  init();
});
