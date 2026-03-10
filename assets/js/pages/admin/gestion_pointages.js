window.exportExcel = function exportExcel() {
  const form = document.getElementById('filterForm');
  if (!form) return;
  const params = new URLSearchParams(new FormData(form)).toString();
  window.location.href = `export_pointages_excel.php?${params}`;
};
