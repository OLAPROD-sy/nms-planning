document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('siteSearch');
  if (!input) return;

  input.addEventListener('input', e => {
    const term = e.target.value.trim().toLowerCase();
    const cards = document.querySelectorAll('.site-card');

    cards.forEach(card => {
      const cardText = card.innerText.toLowerCase();
      const searchableAttr = card.getAttribute('data-searchable') || '';

      if (cardText.includes(term) || searchableAttr.includes(term)) {
        card.style.display = '';
        card.style.opacity = '1';
      } else {
        card.style.display = 'none';
        card.style.opacity = '0';
      }
    });
  });
});
