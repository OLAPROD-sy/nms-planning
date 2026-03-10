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
});
