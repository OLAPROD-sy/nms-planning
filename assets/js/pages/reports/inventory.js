document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', () => {
      const form = document.querySelector('.filter-grid');
      if (form) form.submit();
    });
  });
});

window.exportToExcel = function exportToExcel() {
  const start = document.querySelector('input[name="date_start"]')?.value || '';
  const end = document.querySelector('input[name="date_end"]')?.value || '';
  const site = document.querySelector('select[name="id_site"]')?.value || '';
  const type = document.querySelector('select[name="f_type"]')?.value || '';

  let url = `export_inventory.php?date_start=${start}&date_end=${end}`;
  if (site) url += `&id_site=${site}`;
  if (type) url += `&f_type=${type}`;

  window.location.href = url;
};
