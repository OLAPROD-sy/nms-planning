document.addEventListener('DOMContentLoaded', () => {
  const steps = document.querySelectorAll('.step-section');
  const dots = document.querySelectorAll('.step-dot');
  const progressBar = document.getElementById('progressBar');
  let currentStep = 1;

  if (!steps.length || !progressBar) return;

  function updateStep(stepNumber) {
    steps.forEach(s => s.classList.remove('active'));
    dots.forEach((d, i) => d.classList.toggle('active', i < stepNumber));
    const activeStep = document.querySelector(`.step-section[data-step="${stepNumber}"]`);
    if (activeStep) activeStep.classList.add('active');
    progressBar.style.width = `${(stepNumber / steps.length) * 100}%`;
  }

  document.querySelectorAll('.next-step').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep < steps.length) {
        currentStep++;
        updateStep(currentStep);
      }
    });
  });

  document.querySelectorAll('.prev-step').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 1) {
        currentStep--;
        updateStep(currentStep);
      }
    });
  });

  const roleSelect = document.querySelector('select[name="role"]');
  const siteSingleGroup = document.getElementById('siteSingleGroup');
  const siteMultiGroup = document.getElementById('siteMultiGroup');
  const assignmentsWrapper = document.getElementById('assignmentsWrapper');
  const addAssignmentBtn = document.getElementById('addAssignmentBtn');
  const assignmentTemplate = document.getElementById('assignmentTemplate');

  function bindRemoveButtons(scope) {
    scope.querySelectorAll('.btn-remove-assignment').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('.assignment-row');
        if (row && assignmentsWrapper && assignmentsWrapper.children.length > 1) {
          row.remove();
        }
      });
    });
  }

  function updateSiteFields() {
    const isSupervisor = roleSelect && roleSelect.value === 'SUPERVISEUR';
    if (siteSingleGroup) {
      siteSingleGroup.style.display = isSupervisor ? 'none' : '';
    }
    if (siteMultiGroup) {
      siteMultiGroup.style.display = isSupervisor ? '' : 'none';
    }
    if (assignmentsWrapper) {
      assignmentsWrapper.querySelectorAll('select[name="site_ids[]"]').forEach(select => {
        select.required = isSupervisor;
      });
      assignmentsWrapper.querySelectorAll('input[name="date_debut[]"]').forEach(input => {
        input.required = isSupervisor;
      });
    }
  }

  if (roleSelect) {
    roleSelect.addEventListener('change', updateSiteFields);
  }
  if (addAssignmentBtn && assignmentTemplate && assignmentsWrapper) {
    addAssignmentBtn.addEventListener('click', () => {
      const clone = assignmentTemplate.cloneNode(true);
      clone.removeAttribute('id');
      clone.style.display = '';
      clone.classList.remove('assignment-template');
      clone.querySelectorAll('input').forEach(input => { input.value = ''; });
      clone.querySelectorAll('select').forEach(select => { select.value = ''; });
      assignmentsWrapper.appendChild(clone);
      bindRemoveButtons(clone);
      updateSiteFields();
    });
  }
  if (assignmentsWrapper) {
    bindRemoveButtons(assignmentsWrapper);
  }
  updateSiteFields();
});
