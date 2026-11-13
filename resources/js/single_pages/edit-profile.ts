// resources/js/single_pages/edit-profile.ts
import { apiFetch } from '../api-client';

export {};

document.addEventListener('DOMContentLoaded', () => {
  const deleteBtn = document.getElementById('btn-delete-account');
  const modal = document.getElementById('delete-account-modal') as HTMLDialogElement | null;
  const passwordInput = document.getElementById('delete-account-password') as HTMLInputElement | null;
  const confirmBtn = document.getElementById('delete-account-confirm');
  const errorEl = document.getElementById('delete-account-error');

  if (!deleteBtn || !modal || !passwordInput || !confirmBtn) return;

  // Close button
  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  deleteBtn.addEventListener('click', () => {
    passwordInput.value = '';
    if (errorEl) errorEl.hidden = true;
    modal.showModal();
  });

  confirmBtn.addEventListener('click', () => {
    const password = passwordInput.value.trim();
    if (!password) {
      passwordInput.focus();
      return;
    }

    confirmBtn.setAttribute('disabled', '');
    confirmBtn.textContent = 'Wird gelöscht...';

    void apiFetch('/api/user/account', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password }),
    }).then((res) => {
      if (res.ok) {
        modal.close();
        window.location.href = '/';
      } else {
        void res.json().then((data: { error?: string }) => {
          if (errorEl) {
            errorEl.textContent = data.error ?? 'Ein Fehler ist aufgetreten.';
            errorEl.hidden = false;
          }
          confirmBtn.removeAttribute('disabled');
          confirmBtn.textContent = 'Endgültig löschen';
        });
      }
    });
  });
});
