// Menu mobile toggle
document.addEventListener('DOMContentLoaded', function() {
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function(e) {
      e.preventDefault();
      navLinks.classList.toggle('open');
    });

    // Fermer le menu si on clique ailleurs
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.nav-toggle') && !e.target.closest('.nav-links')) {
        navLinks.classList.remove('open');
      }
    });

    // Fermer le menu au clic sur un lien
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function() {
        navLinks.classList.remove('open');
      });
    });
  }

  // Animation des flashs messages (auto-fermeture après 5s)
  const flashMessages = document.querySelectorAll('[class*="flash-"]');
  flashMessages.forEach(msg => {
    if (msg.classList.contains('flash-success')) {
      setTimeout(() => { 
        msg.style.transition = 'opacity 0.3s ease';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 300);
      }, 5000);
    }
  });

  // Smooth scroll sur les liens internes
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Notifications + menu mobile (header)
  const bell = document.getElementById('notifBell');
  const dropdown = document.getElementById('notifDropdown');
  const hamburger = document.getElementById('hamburger');
  const navMobile = document.getElementById('navMobile');

  if (bell && dropdown) {
    bell.addEventListener('click', function(e) {
      e.stopPropagation();
      const isActive = dropdown.classList.toggle('active');

      if (isActive) {
        if (navMobile) navMobile.classList.remove('active');
        fetch('/admin/mark_notification_read.php')
          .then(response => response.json())
          .then(() => {
            const badge = document.querySelector('.notif-badge');
            if (badge) badge.style.display = 'none';
          })
          .catch(err => console.error('Erreur SQL:', err));
      }
    });
  }

  if (hamburger && navMobile) {
    hamburger.addEventListener('click', function(e) {
      e.stopPropagation();
      navMobile.classList.toggle('active');
      if (dropdown) dropdown.classList.remove('active');
    });
  }

  document.addEventListener('click', function(e) {
    if (dropdown && !dropdown.contains(e.target)) dropdown.classList.remove('active');
    if (navMobile && !navMobile.contains(e.target) && !hamburger.contains(e.target)) {
      navMobile.classList.remove('active');
    }
  });
});
