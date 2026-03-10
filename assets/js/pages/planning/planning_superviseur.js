document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('semSelect');
  if (!select) return;

  select.addEventListener('change', function() {
    const container = document.getElementById('daysContainer');
    const startStr = this.options[this.selectedIndex].getAttribute('data-start');
    if (!container || !startStr) return;

    container.innerHTML = '';
    const startDate = new Date(startStr);
    const joursFr = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    for (let i = 0; i < 7; i++) {
      const current = new Date(startDate);
      current.setDate(startDate.getDate() + i);
      const iso = current.toISOString().split('T')[0];

      const div = document.createElement('div');
      div.className = 'day-option';
      div.innerHTML = `<input type="checkbox" name="dates_planning[]" value="${iso}">
        <span>${joursFr[i]}</span><br><b>${current.getDate()}/${current.getMonth() + 1}</b>`;

      div.onclick = function() {
        const cb = this.querySelector('input');
        cb.checked = !cb.checked;
        this.classList.toggle('selected', cb.checked);
      };

      container.appendChild(div);
    }
  });
});
