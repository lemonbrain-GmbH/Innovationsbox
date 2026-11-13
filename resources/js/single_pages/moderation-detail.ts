import { apiFetch } from '../api-client';
import { escapeHtml, sanitizeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/moderation';
const CATEGORY_API = '/api/moderation/category';

const ERROR_ICON =
  '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';

interface Attachment {
  id: string;
  filename: string;
  mimeType: string;
  size: number;
  isImage: boolean;
  url: string;
}

interface ReviewDetail {
  id: string;
  ideaId: string;
  decision: string;
  reviewerId: string | null;
  notes: string;
  createdAt: string;
  reviewedAt: string | null;
  idea?: {
    title: string;
    description: string;
    category: string;
    submitterId: string;
    submitterName: string;
    status: string;
    createdAt: string;
    attachments: Attachment[];
  };
}

interface CKEditorInstance {
  getData: () => string;
  setData: (data: string) => void;
  destroy: () => void;
}

interface CKEditorStatic {
  replace: (elementOrId: string | HTMLElement, config?: Record<string, unknown>) => CKEditorInstance;
  instances: Record<string, CKEditorInstance>;
}

function getCKEditor(): CKEditorStatic | undefined {
  const ckeditor = (window as unknown as Record<string, unknown>)['CKEDITOR'] as
    | (CKEditorStatic & { config?: Record<string, unknown> })
    | undefined;
  if (ckeditor?.config) {
    ckeditor.config['versionCheck'] = false;
  }
  return ckeditor;
}

function getCkeContentsCss(): string[] {
  const ckeditor = (window as unknown as Record<string, unknown>)['CKEDITOR'] as { basePath?: string } | undefined;
  const themePath =
    document.querySelector('meta[name="theme-path"]')?.getAttribute('content') ||
    '/packages/inno_frontend/themes/innovationsbox';
  return [
    (ckeditor?.basePath || '/concrete/js/ckeditor/') + 'contents.css',
    themePath + '/css/ckeditor-contents.css?v=' + String(Date.now()),
  ];
}

function formatDateCH(isoDate: string): string {
  try {
    return new Date(isoDate).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch {
    return isoDate;
  }
}

function formatFileSize(bytes: number): string {
  if (bytes < 1000) return `${String(bytes)} B`;
  if (bytes < 1_000_000) return `${(bytes / 1000).toFixed(1)} KB`;
  return `${(bytes / 1_000_000).toFixed(1)} MB`;
}

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: { message?: string } };
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string, container?: Element | null): void {
  const target = container ?? document.getElementById('review-container');
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
  alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function getReviewId(): string {
  const params = new URLSearchParams(window.location.search);
  return params.get('id') ?? '';
}

async function fetchReview(id: string): Promise<ReviewDetail> {
  const res = await fetch(`${API_BASE}/${encodeURIComponent(id)}`);
  const json = (await res.json()) as { data: ReviewDetail };
  return json.data;
}

let approveEditorInstance: CKEditorInstance | null = null;

async function loadCategories(select: HTMLSelectElement, current: string): Promise<void> {
  try {
    const res = await fetch(CATEGORY_API);
    const json = (await res.json()) as { data: { id: string; name: string }[] };
    json.data.forEach((cat) => {
      const option = document.createElement('option');
      option.value = cat.id;
      option.textContent = cat.name;
      if (cat.id === current) option.selected = true;
      select.appendChild(option);
    });
  } catch {
    // fail silently
  }
}

function getInputValue(id: string): string {
  const el = document.getElementById(id) as HTMLInputElement | null;
  return el?.value.trim() ?? '';
}

function addIfChanged(body: Record<string, string>, key: string, value: string, original: string): void {
  if (value && value !== original) {
    body[key] = value;
  }
}

function buildApproveBody(idea: NonNullable<ReviewDetail['idea']>): Record<string, string> {
  const description = approveEditorInstance?.getData() ?? '';
  const body: Record<string, string> = {};

  addIfChanged(body, 'title', getInputValue('approve-title'), idea.title);
  addIfChanged(body, 'description', description, idea.description);
  addIfChanged(body, 'category', getInputValue('approve-category'), idea.category);

  return body;
}

async function handleApprove(review: ReviewDetail, idea: NonNullable<ReviewDetail['idea']>): Promise<void> {
  const body = buildApproveBody(idea);

  const res = await apiFetch(`${API_BASE}/${encodeURIComponent(review.id)}/approve`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  if (res.ok) {
    window.location.href = '/moderation';
  } else {
    const msg = await extractErrorMessage(res);
    showInlineError(msg);
  }
}

async function handleFeedbackRequest(review: ReviewDetail): Promise<void> {
  const notesEl = document.getElementById('feedback-notes') as HTMLTextAreaElement | null;
  const deadlineEl = document.getElementById('feedback-deadline') as HTMLInputElement | null;
  const notes = notesEl?.value.trim() ?? '';

  if (!notes) {
    showInlineError('Bitte gib eine Rückmeldung ein.');
    return;
  }

  const body: Record<string, string> = { notes };
  const deadline = deadlineEl?.value.trim() ?? '';
  if (deadline) {
    body['deadline'] = deadline;
  }

  const res = await apiFetch(`${API_BASE}/${encodeURIComponent(review.id)}/feedback`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  if (res.ok) {
    window.location.href = '/moderation';
  } else {
    const msg = await extractErrorMessage(res);
    showInlineError(msg);
  }
}

async function handleReject(review: ReviewDetail, rejectModal: HTMLDialogElement | null): Promise<void> {
  const reasonEl = document.getElementById('reject-reason') as HTMLTextAreaElement | null;
  const reason = reasonEl?.value.trim() ?? '';

  if (!reason) {
    showInlineError('Bitte gib einen Ablehnungsgrund an.', rejectModal);
    return;
  }

  const res = await apiFetch(`${API_BASE}/${encodeURIComponent(review.id)}/reject`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ notes: reason }),
  });

  if (res.ok) {
    window.location.href = '/moderation';
  } else {
    const msg = await extractErrorMessage(res);
    showInlineError(msg, rejectModal);
  }
}

function initFileManagement(review: ReviewDetail, idea: NonNullable<ReviewDetail['idea']>): void {
  const filesToRemove: string[] = [];
  document.querySelectorAll('.moderation-detail-page__attachment-remove').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const row = (btn as HTMLElement).closest('.moderation-detail-page__attachment-row') as HTMLElement;
      const attId = row.dataset['attId'] ?? '';
      filesToRemove.push(attId);
      row.style.opacity = '0.3';
      row.style.textDecoration = 'line-through';
      (btn as HTMLElement).style.display = 'none';
    });
  });

  document.getElementById('btn-save-files')?.addEventListener('click', () => {
    const newFilesInput = document.getElementById('mod-new-files') as HTMLInputElement | null;
    if (filesToRemove.length === 0 && (!newFilesInput?.files || newFilesInput.files.length === 0)) {
      return;
    }

    const formData = new FormData();
    formData.append('title', idea.title);
    formData.append('description', idea.description);
    formData.append('category', idea.category);
    filesToRemove.forEach((attId) => {
      formData.append('removeFiles[]', attId);
    });
    if (newFilesInput?.files) {
      for (let i = 0; i < newFilesInput.files.length; i++) {
        formData.append('files[]', newFilesInput.files[i]);
      }
    }

    void apiFetch(`/api/project-idea/${encodeURIComponent(review.ideaId)}`, {
      method: 'POST',
      body: formData,
    }).then((res) => {
      if (res.ok) {
        window.location.reload();
      } else {
        void extractErrorMessage(res).then((msg) => {
          showInlineError(msg);
        });
      }
    });
  });
}

function renderReview(review: ReviewDetail): void {
  const container = document.getElementById('review-container');
  if (!container) return;

  const idea = review.idea;
  if (!idea) {
    container.innerHTML = '<p>Idee nicht gefunden.</p>';
    return;
  }

  const attachmentsHtml = `<div class="moderation-detail-page__attachments">
      <h3>Anhänge</h3>
      <div class="moderation-detail-page__attachment-list" id="mod-attachment-list">
        ${
          idea.attachments.length > 0
            ? idea.attachments
                .map(
                  (a) =>
                    `<div class="moderation-detail-page__attachment-row" data-att-id="${escapeHtml(a.id)}">
                <a href="${escapeHtml(a.url)}" class="moderation-detail-page__attachment" target="_blank" rel="noopener">
                  <span>${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</span>
                  <span class="moderation-detail-page__attachment-size">${formatFileSize(a.size)}</span>
                </a>
                <button type="button" class="moderation-detail-page__attachment-remove" title="Entfernen">\u00D7</button>
              </div>`,
                )
                .join('')
            : '<p style="color: var(--color-text-muted);">Keine Anhänge</p>'
        }
      </div>
      <div class="form-field" style="margin-top: var(--space-3);">
        <label class="label" for="mod-new-files">Neue Anhänge hinzufügen</label>
        <input type="file" id="mod-new-files" multiple accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text">
      </div>
      <button type="button" class="button button--muted button--sm" id="btn-save-files" style="margin-top: var(--space-2);">Anhänge speichern</button>
    </div>`;

  container.innerHTML = `
    <section class="moderation-detail-page__idea">
      <h2>${escapeHtml(idea.title)}</h2>
      <div class="moderation-detail-page__meta">
        <span>Eingereicht von: <strong>${escapeHtml(idea.submitterName)}</strong></span>
        <span>Datum: ${formatDateCH(idea.createdAt)}</span>
      </div>
      <div class="moderation-detail-page__description">${sanitizeHtml(idea.description)}</div>
      ${attachmentsHtml}
    </section>

    <section class="moderation-detail-page__decision">
      <h3>Entscheidung</h3>

      <div class="moderation-detail-page__approve-form">
        <h4>Genehmigen</h4>
        <p style="color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);">Du kannst Titel, Beschreibung und Kategorie vor der Genehmigung anpassen.</p>
        <div class="form-field" style="margin-bottom: var(--space-3);">
          <label class="label" for="approve-title">Titel</label>
          <input type="text" id="approve-title" class="input" value="${escapeHtml(idea.title)}">
        </div>
        <div class="form-field" style="margin-bottom: var(--space-3);">
          <label class="label" for="approve-category">Kategorie</label>
          <select id="approve-category" class="input"></select>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-3);">
          <label class="label" for="approve-description">Beschreibung</label>
          <textarea id="approve-description" class="input" rows="6">${escapeHtml(idea.description)}</textarea>
        </div>
        <button type="button" class="button button--accent button--lg" id="btn-approve">Genehmigen</button>
      </div>

      <hr style="margin: var(--space-6) 0;">

      <div class="moderation-detail-page__feedback-form">
        <h4>Rückmeldung anfordern</h4>
        <p style="color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);">Fordere Änderungen oder weitere Informationen vom Einreicher an.</p>
        <div class="form-field" style="margin-bottom: var(--space-3);">
          <label class="label" for="feedback-notes">Rückmeldung (erforderlich)</label>
          <textarea id="feedback-notes" class="input" rows="3" placeholder="Was soll geändert oder ergänzt werden?"></textarea>
        </div>
        <div class="form-field" style="margin-bottom: var(--space-3);">
          <label class="label" for="feedback-deadline">Frist (optional)</label>
          <input type="date" id="feedback-deadline" class="input">
        </div>
        <button type="button" class="button button--warning button--lg" id="btn-feedback">Rückmeldung senden</button>
      </div>

      <hr style="margin: var(--space-6) 0;">

      <div class="moderation-detail-page__reject-form">
        <h4>Ablehnen</h4>
        <button type="button" class="button button--danger button--lg" id="btn-reject">Ablehnen...</button>
      </div>
    </section>
  `;

  // Load categories into dropdown
  const catSelect = document.getElementById('approve-category') as HTMLSelectElement | null;
  if (catSelect) void loadCategories(catSelect, idea.category);

  // Init CKEditor for description
  setTimeout(() => {
    const ckeditor = getCKEditor();
    if (ckeditor && !approveEditorInstance) {
      approveEditorInstance = ckeditor.replace('approve-description', {
        toolbar: [
          { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
          { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
          { name: 'links', items: ['Link', 'Unlink'] },
        ],
        contentsCss: getCkeContentsCss(),
        removePlugins: 'elementspath',
        resize_enabled: false,
        versionCheck: false,
        height: 200,
      });
    }
  }, 100);

  // Approve handler
  document.getElementById('btn-approve')?.addEventListener('click', () => {
    void handleApprove(review, idea);
  });

  // Feedback request handler
  document.getElementById('btn-feedback')?.addEventListener('click', () => {
    void handleFeedbackRequest(review);
  });

  // Reject handler — opens modal
  const rejectModal = document.getElementById('reject-modal') as HTMLDialogElement | null;
  document.getElementById('btn-reject')?.addEventListener('click', () => {
    rejectModal?.showModal();
  });

  rejectModal?.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      rejectModal.close();
    });
  });

  document.getElementById('reject-confirm')?.addEventListener('click', () => {
    void handleReject(review, rejectModal);
  });

  initFileManagement(review, idea);
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('review-container')) return;
  const reviewId = getReviewId();
  if (!reviewId) {
    const container = document.getElementById('review-container');
    if (container) container.innerHTML = '<p>Kein Review ausgewählt.</p>';
    return;
  }
  void fetchReview(reviewId)
    .then(renderReview)
    .catch(() => {
      showInlineError('Fehler beim Laden des Reviews.');
    });
});
