import { apiFetch } from '../api-client';
import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/moderation/user';
const PAGE_SIZE = 20;

interface UserEntry {
  id: string;
  name: string;
  email: string;
  isExpert: boolean;
}

let allUsers: UserEntry[] = [];
let filteredUsers: UserEntry[] = [];
let currentPage = 1;
let searchQuery = '';

async function fetchUsers(): Promise<UserEntry[]> {
  const res = await fetch(API_BASE);
  if (!res.ok) return [];
  const json = (await res.json()) as { data?: UserEntry[] };
  return json.data || [];
}

function matchesSearch(user: UserEntry, query: string): boolean {
  const q = query.toLowerCase();
  return user.name.toLowerCase().includes(q) || user.email.toLowerCase().includes(q);
}

function applyFilter(): void {
  filteredUsers = searchQuery ? allUsers.filter((u) => matchesSearch(u, searchQuery)) : [...allUsers];
  currentPage = 1;
  render();
}

function render(): void {
  const container = document.getElementById('users-container');
  if (!container) return;

  const totalPages = Math.max(1, Math.ceil(filteredUsers.length / PAGE_SIZE));
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageUsers = filteredUsers.slice(start, start + PAGE_SIZE);

  if (filteredUsers.length === 0 && searchQuery) {
    container.innerHTML = `<p class="moderation-benutzer-page__empty">Keine Benutzer für «${escapeHtml(searchQuery)}» gefunden.</p>`;
    return;
  }

  if (filteredUsers.length === 0) {
    container.innerHTML = '<p class="moderation-benutzer-page__empty">Keine Benutzer vorhanden.</p>';
    return;
  }

  container.innerHTML = `
    <div class="user-list__search">
      <input type="text" id="user-search" class="input" placeholder="Name oder E-Mail suchen…" value="${escapeHtml(searchQuery)}" />
    </div>
    <div class="user-list__info">${String(filteredUsers.length)} Benutzer${searchQuery ? ` für «${escapeHtml(searchQuery)}»` : ''}</div>
    <div class="user-list">
      <div class="user-list__header">
        <span class="user-list__col user-list__col--name">Name</span>
        <span class="user-list__col user-list__col--email">E-Mail</span>
        <span class="user-list__col user-list__col--expert">Expert·in</span>
      </div>
      ${pageUsers
        .map(
          (user) => `
        <div class="user-list__item" data-id="${escapeHtml(user.id)}">
          <span class="user-list__col user-list__col--name">${escapeHtml(user.name)}</span>
          <span class="user-list__col user-list__col--email">${escapeHtml(user.email)}</span>
          <span class="user-list__col user-list__col--expert">
            <button type="button" class="button button--sm ${user.isExpert ? 'button--accent' : 'button--muted'}" data-toggle-expert="${escapeHtml(user.id)}" data-is-expert="${user.isExpert ? 'true' : 'false'}">
              ${user.isExpert ? 'Expert·in ✓' : 'Markieren'}
            </button>
            <button type="button" class="button button--danger button--sm" data-delete-user="${escapeHtml(user.id)}" data-user-name="${escapeHtml(user.name)}">Entfernen</button>
          </span>
        </div>`,
        )
        .join('')}
    </div>
    ${totalPages > 1 ? renderPagination(totalPages) : ''}`;

  attachHandlers(container);
}

function renderPagination(totalPages: number): string {
  const pages: string[] = [];
  for (let i = 1; i <= totalPages; i++) {
    pages.push(
      `<button type="button" class="pagination__btn ${i === currentPage ? 'pagination__btn--active' : ''}" data-page="${String(i)}">${String(i)}</button>`,
    );
  }
  return `<div class="user-list__pagination">${pages.join('')}</div>`;
}

function attachHandlers(container: HTMLElement): void {
  // Search input
  const searchInput = container.querySelector<HTMLInputElement>('#user-search');
  if (searchInput) {
    let debounceTimer: ReturnType<typeof setTimeout>;
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchQuery = searchInput.value.trim();
        applyFilter();
        // Re-focus search input after re-render
        const newInput = document.getElementById('user-search') as HTMLInputElement | null;
        if (newInput) {
          newInput.focus();
          newInput.setSelectionRange(newInput.value.length, newInput.value.length);
        }
      }, 300);
    });
  }

  // Pagination buttons
  container.querySelectorAll('[data-page]').forEach((btn) => {
    btn.addEventListener('click', () => {
      currentPage = parseInt((btn as HTMLElement).dataset.page ?? '1', 10);
      render();
    });
  });

  // Toggle expert handlers
  container.querySelectorAll('[data-toggle-expert]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const userId = (btn as HTMLElement).dataset.toggleExpert ?? '';
      const currentlyExpert = (btn as HTMLElement).dataset.isExpert === 'true';

      void apiFetch(`${API_BASE}/${userId}/expert`, {
        method: 'PUT',
        body: JSON.stringify({ expert: !currentlyExpert }),
      }).then((res) => {
        if (res.ok) {
          // Update local state instead of full page reload
          const user = allUsers.find((u) => u.id === userId);
          if (user) {
            user.isExpert = !currentlyExpert;
          }
          applyFilter();
        }
      });
    });
  });

  // Delete user handler
  container.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const btn = target.closest<HTMLElement>('[data-delete-user]');
    if (!btn) return;

    const userId = btn.dataset.deleteUser ?? '';
    const userName = btn.dataset.userName ?? '';

    const modal = document.getElementById('delete-user-modal') as HTMLDialogElement | null;
    const nameEl = document.getElementById('delete-user-name');
    const reasonInput = document.getElementById('delete-user-reason') as HTMLTextAreaElement | null;
    const confirmBtn = document.getElementById('delete-user-confirm');
    const errorEl = document.getElementById('delete-user-error');
    if (!modal || !reasonInput || !confirmBtn) return;

    if (nameEl) nameEl.textContent = `Benutzer «${userName}» wirklich entfernen?`;
    reasonInput.value = '';
    if (errorEl) errorEl.hidden = true;
    modal.showModal();

    const freshConfirm = confirmBtn.cloneNode(true) as HTMLElement;
    confirmBtn.replaceWith(freshConfirm);

    modal.querySelectorAll('[data-modal-close]').forEach((closeBtn) => {
      closeBtn.addEventListener('click', () => {
        modal.close();
      });
    });

    freshConfirm.addEventListener('click', () => {
      const reason = reasonInput.value.trim();
      if (!reason) {
        reasonInput.focus();
        return;
      }

      void apiFetch(`/api/moderation/user/${encodeURIComponent(userId)}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason }),
      }).then((res) => {
        if (res.ok) {
          modal.close();
          window.location.reload();
        } else {
          void res.json().then((data: { error?: string }) => {
            if (errorEl) {
              errorEl.textContent = data.error ?? 'Ein Fehler ist aufgetreten.';
              errorEl.hidden = false;
            }
          });
        }
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  void fetchUsers().then((users) => {
    allUsers = users;
    filteredUsers = [...users];

    // Render search bar first, then full list
    render();
  });
});
