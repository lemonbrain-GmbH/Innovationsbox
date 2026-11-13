import { apiFetch } from '../api-client';
import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/expert-request';

const ERROR_ICON =
  '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';

interface ExpertRequestData {
  id: string;
  spaceId: string;
  spaceTitle: string;
  requestedByName: string;
  message: string | null;
  status: string;
  requestedAt: string;
  decidedAt: string | null;
}

function formatDateCH(isoDate: string): string {
  try {
    return new Date(isoDate).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch {
    return isoDate;
  }
}

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: { message?: string } };
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string): void {
  const target = document.getElementById('requests-container');
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

function statusBadge(status: string): string {
  const map: Record<string, { label: string; cls: string }> = {
    pending: { label: 'Ausstehend', cls: 'badge--warning' },
    accepted: { label: 'Angenommen', cls: 'badge--success' },
    declined: { label: 'Abgelehnt', cls: 'badge--neutral' },
  };
  const s = map[status] ?? { label: status, cls: 'badge--neutral' };
  return `<span class="badge ${s.cls}">${escapeHtml(s.label)}</span>`;
}

async function fetchRequests(): Promise<ExpertRequestData[]> {
  const res = await fetch(`${API_BASE}/mine`, { cache: 'no-store' });
  const json = (await res.json()) as { data: ExpertRequestData[] };
  return json.data;
}

function renderRequests(requests: ExpertRequestData[]): void {
  const container = document.getElementById('requests-container');
  if (!container) return;

  if (requests.length === 0) {
    container.innerHTML = `<div class="expert-anfragen-page__empty"><p>Keine offenen Anfragen vorhanden.</p></div>`;
    return;
  }

  container.innerHTML = requests
    .map(
      (req) => `
    <div class="expert-request-card" data-request-id="${escapeHtml(req.id)}" data-space-id="${escapeHtml(req.spaceId)}">
      <div class="expert-request-card__header">
        <a href="/projektraum/view/${escapeHtml(req.spaceId)}" class="expert-request-card__title">${escapeHtml(req.spaceTitle)}</a>
        ${statusBadge(req.status)}
      </div>
      <div class="expert-request-card__meta">
        <span>Angefragt von: <strong>${escapeHtml(req.requestedByName)}</strong></span>
        <span>${formatDateCH(req.requestedAt)}</span>
      </div>
      ${req.message ? `<p class="expert-request-card__message">${escapeHtml(req.message)}</p>` : ''}
      ${
        req.status === 'pending'
          ? `
        <div class="expert-request-card__actions">
          <button type="button" class="button button--accent button--sm" data-accept="true">Annehmen</button>
          <button type="button" class="button button--muted button--sm" data-accept="false">Ablehnen</button>
        </div>`
          : ''
      }
    </div>`,
    )
    .join('');

  initActions();
}

function initActions(): void {
  document.querySelectorAll('.expert-request-card__actions button').forEach((btn) => {
    btn.addEventListener('click', () => {
      const card = (btn as HTMLElement).closest('.expert-request-card') as HTMLElement;
      const requestId = card.dataset['requestId'] ?? '';
      const spaceId = card.dataset['spaceId'] ?? '';
      const accept = (btn as HTMLElement).dataset['accept'] === 'true';

      void apiFetch(
        `/api/project-space/${encodeURIComponent(spaceId)}/expert-request/${encodeURIComponent(requestId)}`,
        {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ accept }),
        },
      ).then((res) => {
        if (res.ok) {
          window.location.reload();
        } else {
          void extractErrorMessage(res).then((msg) => {
            showInlineError(msg);
          });
        }
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('requests-container')) return;
  void fetchRequests()
    .then(renderRequests)
    .catch(() => {
      showInlineError('Fehler beim Laden der Anfragen.');
    });
});
