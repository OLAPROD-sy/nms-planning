window.searchTable = function searchTable() {
  const input = document.getElementById('searchInput');
  const table = document.getElementById('stockTable');
  if (!input || !table) return;

  const filter = input.value.toUpperCase();
  const tr = table.getElementsByTagName('tr');

  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName('td')[0];
    if (td) {
      const textValue = td.textContent || td.innerText;
      tr[i].style.display = textValue.toUpperCase().includes(filter) ? '' : 'none';
    }
  }
};

window.exportExcel = function exportExcel() {
  const debut = document.querySelector('input[name="f_date_debut"]')?.value || '';
  const fin = document.querySelector('input[name="f_date_fin"]')?.value || '';
  const action = document.querySelector('select[name="f_action"]')?.value || '';
  const destination = document.querySelector('select[name="f_destination"]')?.value || '';

  window.location.href = `export_inventaire2.php?f_date_debut=${debut}&f_date_fin=${fin}&f_action=${action}&f_destination=${destination}`;
};

window.toggleSiteSelection = function toggleSiteSelection() {
  const type = document.getElementById('typeMouvement')?.value;
  const siteSelect = document.getElementById('siteSelection');
  if (!siteSelect) return;

  if (type === 'SORTIE') {
    siteSelect.style.display = 'block';
    siteSelect.setAttribute('required', 'required');
  } else {
    siteSelect.style.display = 'none';
    siteSelect.removeAttribute('required');
  }
};

// Initial state for site selection
window.toggleSiteSelection();
