document.addEventListener('DOMContentLoaded', () => {
  // Micro-interaction : Focus visuel
  const inputs = document.querySelectorAll('input');
  inputs.forEach(input => {
    input.addEventListener('focus', () => {
      const label = input.closest('.form-group')?.querySelector('label');
      if (label) label.style.color = 'var(--secondary)';
    });
    input.addEventListener('blur', () => {
      const label = input.closest('.form-group')?.querySelector('label');
      if (label) label.style.color = 'var(--dark)';
    });
  });

  // Toggle visibilité du mot de passe
  const togglePassword = document.querySelector('#togglePassword');
  const password = document.querySelector('#password');
  const togglePasswordIcon = document.querySelector('#togglePasswordIcon');

  if (togglePassword && password) {
    togglePassword.addEventListener('click', function() {
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);

      if (togglePasswordIcon) {
        togglePasswordIcon.classList.toggle('bi-eye', type === 'password');
        togglePasswordIcon.classList.toggle('bi-eye-slash', type !== 'password');
      }

      this.style.transform = 'translateY(-50%) scale(1.2)';
      setTimeout(() => {
        this.style.transform = 'translateY(-50%) scale(1)';
      }, 150);
    });
  }
});
