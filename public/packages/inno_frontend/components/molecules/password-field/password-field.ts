function initPasswordFields(): void {
  document.querySelectorAll('[data-password-field]').forEach((field) => {
    const input = field.querySelector('input[type="password"], input[type="text"]') as HTMLInputElement | null;
    const toggle = field.querySelector('[data-password-toggle]') as HTMLButtonElement | null;
    const showIcon = field.querySelector('.password-field__icon--show') as HTMLElement | null;
    const hideIcon = field.querySelector('.password-field__icon--hide') as HTMLElement | null;
    const strengthFill = field.querySelector('[data-password-strength-fill]') as HTMLElement | null;
    const strengthLabel = field.querySelector('[data-password-strength-label]') as HTMLElement | null;

    if (!input) return;

    // Toggle show/hide password
    if (toggle && showIcon && hideIcon) {
      toggle.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        field.classList.toggle('password-field--visible', isPassword);
        toggle.setAttribute('aria-label', isPassword ? 'Passwort verbergen' : 'Passwort anzeigen');

        if (isPassword) {
          showIcon.setAttribute('hidden', '');
          hideIcon.removeAttribute('hidden');
        } else {
          showIcon.removeAttribute('hidden');
          hideIcon.setAttribute('hidden', '');
        }
      });
    }

    // Password strength indicator
    if (strengthFill && strengthLabel) {
      function calculateStrength(password: string): {
        score: number;
        label: string;
        color: string;
      } {
        if (password.length === 0) return { score: 0, label: '', color: '' };

        let score = 0;

        if (password.length >= 5) score += 20;
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;

        if (/[a-z]/.test(password)) score += 10;
        if (/[A-Z]/.test(password)) score += 10;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^a-zA-Z0-9]/.test(password)) score += 15;

        score = Math.min(100, score);

        let label: string;
        let color: string;
        if (score < 40) {
          label = 'Schwach';
          color = 'var(--color-status-error-500)';
        } else if (score < 70) {
          label = 'Mittel';
          color = 'var(--color-status-warning-500)';
        } else {
          label = 'Stark';
          color = 'var(--color-status-success-500)';
        }

        return { score, label, color };
      }

      input.addEventListener('input', () => {
        const { score, label, color } = calculateStrength(input.value);
        strengthFill!.style.width = `${score}%`;
        strengthFill!.style.setProperty('background-color', color);
        strengthLabel!.textContent = label;
      });
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPasswordFields);
} else {
  initPasswordFields();
}
