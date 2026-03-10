document.addEventListener('DOMContentLoaded', () => {
  const steps = document.querySelectorAll('.step-section');
  const dots = document.querySelectorAll('.step-dot');
  const progressBar = document.getElementById('progressBar');
  let currentStep = 1;

  if (!steps.length || !progressBar) return;

  function updateStep(stepNumber) {
    steps.forEach(step => step.classList.remove('active'));
    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index < stepNumber);
    });

    const activeStep = document.querySelector(`.step-section[data-step="${stepNumber}"]`);
    if (activeStep) activeStep.classList.add('active');

    const progress = (stepNumber / steps.length) * 100;
    progressBar.style.width = `${progress}%`;
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
