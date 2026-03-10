document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('userSearch');
  if (!searchInput) return;

  const sections = document.querySelectorAll('.site-section-wrapper');

  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();

    sections.forEach(section => {
      const cards = section.querySelectorAll('.user-card');
      let hasVisibleCards = false;

      cards.forEach(card => {
        const searchText = card.getAttribute('data-search') || '';
        if (searchText.includes(searchTerm)) {
          card.style.display = 'flex';
          hasVisibleCards = true;
        } else {
          card.style.display = 'none';
        }
      });

      section.style.display = hasVisibleCards ? 'block' : 'none';
    });
  });
});
