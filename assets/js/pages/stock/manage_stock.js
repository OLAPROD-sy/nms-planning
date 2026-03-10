function getSiteId() {
  const wrapper = document.querySelector('.stock-wrapper');
  return wrapper ? wrapper.getAttribute('data-site-id') : null;
}

window.exportHistorique = function exportHistorique() {
  const dateStart = document.querySelector('input[name="date_start"]')?.value || '';
  const dateEnd = document.querySelector('input[name="date_end"]')?.value || '';
  const fType = document.querySelector('select[name="f_type"]')?.value || '';
  const idSite = getSiteId();

  if (!idSite) return;

  let url = `export_inventaire.php?id_site=${idSite}`;
  if (dateStart) url += `&date_start=${dateStart}`;
  if (dateEnd) url += `&date_end=${dateEnd}`;
  if (fType) url += `&f_type=${fType}`;

  window.location.href = url;
};

window.exportStockActuel = function exportStockActuel() {
  const idSite = getSiteId();
  if (!idSite) return;
  window.location.href = `export_current_history.php?id_site=${idSite}`;
};

window.filterStock = function filterStock() {
  const input = document.getElementById('searchStock');
  const container = document.getElementById('stockList');
  if (!input || !container) return;

  const filter = input.value.toLowerCase();
  const items = container.getElementsByClassName('product-item');

  for (let i = 0; i < items.length; i++) {
    const nameEl = items[i].querySelector('.product-name');
    const name = nameEl ? nameEl.innerText.toLowerCase() : '';
    items[i].style.display = name.includes(filter) ? '' : 'none';
  }
};
