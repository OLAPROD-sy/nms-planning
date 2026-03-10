document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('searchInput');
  if (input) {
    input.addEventListener('input', e => {
      const val = e.target.value.toLowerCase();
      document.querySelectorAll('.poste-item').forEach(item => {
        const name = item.getAttribute('data-name') || '';
        item.style.display = name.includes(val) ? 'flex' : 'none';
      });
    });
  }
});
