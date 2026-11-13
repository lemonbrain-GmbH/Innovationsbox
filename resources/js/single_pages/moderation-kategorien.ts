import { apiFetch } from '../api-client';
import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/moderation/category';

const ERROR_ICON =
  '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';

interface Category {
  id: string;
  name: string;
  slug: string;
  sortOrder: number;
}

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: string | { message?: string } };
    if (typeof json.error === 'string') return json.error;
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string, container?: Element | null): void {
  const target = container ?? document.getElementById('categories-container');
  if (!target) return;
  target.querySelectorAll('.alert--error[data-inline-error]').forEach((el) => {
    el.remove();
  });
  const alert = document.createElement('div');
  alert.className = 'alert alert--error';
  alert.setAttribute('role', 'alert');
  alert.dataset['inlineError'] = '';
  alert.innerHTML =
    `<span class="alert__icon" aria-hidden="true">${ERROR_ICON}</span>` +
    `<div class="alert__content"><p class="alert__message">${escapeHtml(message)}</p></div>`;
  target.prepend(alert);
}

async function fetchCategories(): Promise<Category[]> {
  const res = await fetch(API_BASE);
  const json = (await res.json()) as { data: Category[] };
  return json.data;
}

function renderCategories(categories: Category[]): void {
  const container = document.getElementById('categories-container');
  if (!container) return;

  if (categories.length === 0) {
    container.innerHTML = '<p class="moderation-kategorien-page__empty">Keine Kategorien vorhanden.</p>';
    return;
  }

  container.innerHTML = `
    <div class="category-list">
      <div class="category-list__header">
        <span class="category-list__col category-list__col--name">Name</span>
        <span class="category-list__col category-list__col--slug">Slug</span>
        <span class="category-list__col category-list__col--sort">Sortierung</span>
        <span class="category-list__col category-list__col--actions">Aktionen</span>
      </div>
      ${categories
        .map(
          (cat) => `
        <div class="category-list__item" data-id="${escapeHtml(cat.id)}">
          <span class="category-list__col category-list__col--name">${escapeHtml(cat.name)}</span>
          <span class="category-list__col category-list__col--slug">${escapeHtml(cat.slug)}</span>
          <span class="category-list__col category-list__col--sort">${String(cat.sortOrder)}</span>
          <span class="category-list__col category-list__col--actions">
            <button type="button" class="button button--muted button--sm" data-edit="${escapeHtml(cat.id)}" data-name="${escapeHtml(cat.name)}" data-slug="${escapeHtml(cat.slug)}" data-sort="${String(cat.sortOrder)}">Bearbeiten</button>
            <button type="button" class="button button--danger button--sm" data-delete="${escapeHtml(cat.id)}" data-name="${escapeHtml(cat.name)}">Löschen</button>
          </span>
        </div>`,
        )
        .join('')}
    </div>`;

  initEditButtons();
  initDeleteButtons();
}

function initAddButton(): void {
  const modal = document.getElementById('category-modal') as HTMLDialogElement | null;
  const nameInput = document.getElementById('category-name') as HTMLInputElement | null;
  const slugInput = document.getElementById('category-slug') as HTMLInputElement | null;
  const sortInput = document.getElementById('category-sort') as HTMLInputElement | null;
  const titleEl = document.getElementById('category-modal-title');
  const submitBtn = document.getElementById('category-submit');
  if (!modal || !nameInput || !submitBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.getElementById('btn-add-category')?.addEventListener('click', () => {
    if (titleEl) titleEl.textContent = 'Neue Kategorie';
    nameInput.value = '';
    if (slugInput) slugInput.value = '';
    if (sortInput) sortInput.value = '0';
    modal.showModal();

    const freshSubmit = submitBtn.cloneNode(true) as HTMLElement;
    submitBtn.replaceWith(freshSubmit);
    freshSubmit.addEventListener('click', () => {
      const name = nameInput.value.trim();
      if (!name) return;

      const formData = new URLSearchParams();
      formData.append('name', name);
      if (slugInput?.value.trim()) formData.append('slug', slugInput.value.trim());
      if (sortInput?.value) formData.append('sort_order', sortInput.value);

      void apiFetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString(),
      }).then((res) => {
        if (res.ok) {
          modal.close();
          window.location.reload();
        } else {
          void extractErrorMessage(res).then((msg) => {
            showInlineError(msg, modal);
          });
        }
      });
    });
  });
}

function initEditButtons(): void {
  const modal = document.getElementById('category-modal') as HTMLDialogElement | null;
  const nameInput = document.getElementById('category-name') as HTMLInputElement | null;
  const slugInput = document.getElementById('category-slug') as HTMLInputElement | null;
  const sortInput = document.getElementById('category-sort') as HTMLInputElement | null;
  const titleEl = document.getElementById('category-modal-title');
  const submitBtn = document.getElementById('category-submit');
  if (!modal || !nameInput || !submitBtn) return;

  document.querySelectorAll<HTMLElement>('[data-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const catId = btn.dataset['edit'] ?? '';
      const name = btn.dataset['name'] ?? '';
      const slug = btn.dataset['slug'] ?? '';
      const sort = btn.dataset['sort'] ?? '0';

      if (titleEl) titleEl.textContent = 'Kategorie bearbeiten';
      nameInput.value = name;
      if (slugInput) slugInput.value = slug;
      if (sortInput) sortInput.value = sort;
      modal.showModal();

      const freshSubmit = submitBtn.cloneNode(true) as HTMLElement;
      submitBtn.replaceWith(freshSubmit);
      freshSubmit.addEventListener('click', () => {
        const newName = nameInput.value.trim();
        if (!newName) return;

        const formData = new URLSearchParams();
        formData.append('name', newName);
        if (slugInput?.value.trim()) formData.append('slug', slugInput.value.trim());
        if (sortInput?.value) formData.append('sort_order', sortInput.value);

        void apiFetch(`${API_BASE}/${encodeURIComponent(catId)}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formData.toString(),
        }).then((res) => {
          if (res.ok) {
            modal.close();
            window.location.reload();
          } else {
            void extractErrorMessage(res).then((msg) => {
              showInlineError(msg, modal);
            });
          }
        });
      });
    });
  });
}

function initDeleteButtons(): void {
  const modal = document.getElementById('delete-category-modal') as HTMLDialogElement | null;
  const nameEl = document.getElementById('delete-category-name');
  const confirmBtn = document.getElementById('delete-category-confirm');
  if (!modal || !confirmBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.querySelectorAll<HTMLElement>('[data-delete]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const catId = btn.dataset['delete'] ?? '';
      const name = btn.dataset['name'] ?? '';
      if (nameEl) nameEl.textContent = `«${name}»`;

      const freshConfirm = confirmBtn.cloneNode(true) as HTMLElement;
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', () => {
        void apiFetch(`${API_BASE}/${encodeURIComponent(catId)}`, { method: 'DELETE' }).then((res) => {
          if (res.ok) {
            modal.close();
            window.location.reload();
          } else {
            void extractErrorMessage(res).then((msg) => {
              showInlineError(msg, modal);
            });
          }
        });
      });

      modal.showModal();
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('categories-container')) return;
  initAddButton();
  void fetchCategories()
    .then(renderCategories)
    .catch(() => {
      showInlineError('Fehler beim Laden der Kategorien.');
    });
});
