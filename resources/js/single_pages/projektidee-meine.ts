import { apiFetch } from '../api-client';
import { escapeHtml, sanitizeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/project-idea';
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

interface IdeaData {
  id: string;
  title: string;
  description: string;
  category: string;
  status: string;
  rejectionReason: string | null;
  feedbackNotes: string | null;
  feedbackDeadline: string | null;
  projectSpaceId: string | null;
  attachments: Attachment[];
  headerImageUrl: string;
  createdAt: string;
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

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: { message?: string } };
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string, container?: Element | null): void {
  const target = container ?? document.getElementById('ideas-container');
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
    submitted: { label: 'Eingereicht', cls: 'badge--neutral' },
    under_review: { label: 'In Prüfung', cls: 'badge--info' },
    feedback_requested: { label: 'Rückmeldung angefordert', cls: 'badge--warning' },
    approved: { label: 'Genehmigt', cls: 'badge--success' },
    rejected: { label: 'Abgelehnt', cls: 'badge--error' },
  };
  const s = map[status] ?? { label: status, cls: 'badge--neutral' };
  return `<span class="badge ${s.cls}">${escapeHtml(s.label)}</span>`;
}

let loadedIdeas: IdeaData[] = [];

async function fetchIdeas(): Promise<IdeaData[]> {
  const res = await fetch(`${API_BASE}/mine`);
  const json = (await res.json()) as { data: IdeaData[] };
  return json.data;
}

function renderIdeaStatusInfo(idea: IdeaData): string {
  if (idea.status === 'rejected' && idea.rejectionReason) {
    return `<div class="idea-card__rejection"><strong>Ablehnungsgrund:</strong> ${escapeHtml(idea.rejectionReason)}</div>`;
  }
  if (idea.status === 'feedback_requested' && idea.feedbackNotes) {
    const deadline = idea.feedbackDeadline ? `<br><strong>Frist:</strong> ${formatDateCH(idea.feedbackDeadline)}` : '';
    return `<div class="idea-card__feedback"><strong>Rückmeldung:</strong> ${escapeHtml(idea.feedbackNotes)}${deadline}</div>`;
  }
  if (idea.status === 'approved' && idea.projectSpaceId) {
    return `<a href="/projektraum/view/${escapeHtml(idea.projectSpaceId)}" class="button button--muted button--sm">Zur Innovation \u2192</a>`;
  }
  return '';
}

function renderIdeaActions(idea: IdeaData): string {
  if (idea.status !== 'submitted' && idea.status !== 'feedback_requested') return '';
  const editBtn = `<button type="button" class="button button--muted button--sm" data-edit-idea="${escapeHtml(idea.id)}" data-title="${escapeHtml(idea.title)}" data-description="${escapeHtml(idea.description)}" data-category="${escapeHtml(idea.category)}">Bearbeiten</button>`;
  const withdrawBtn =
    idea.status === 'submitted'
      ? `<button type="button" class="button button--danger button--sm" data-withdraw-idea="${escapeHtml(idea.id)}" data-title="${escapeHtml(idea.title)}">Zurückziehen</button>`
      : '';
  return `<div class="idea-card__actions">${editBtn}${withdrawBtn}</div>`;
}

function renderIdeaCard(idea: IdeaData): string {
  return `
    <div class="idea-card" data-id="${escapeHtml(idea.id)}">
      <div class="idea-card__header">
        <h3 class="idea-card__title">${escapeHtml(idea.title)}</h3>
        <div class="idea-card__badges">
          ${statusBadge(idea.status)}
        </div>
      </div>
      <div class="idea-card__description">${sanitizeHtml(idea.description)}</div>
      <div class="idea-card__meta">
        <span class="idea-card__date">${formatDateCH(idea.createdAt)}</span>
        ${idea.attachments.length > 0 ? `<span class="idea-card__attachments">\u{1F4CE} ${String(idea.attachments.length)} Datei${idea.attachments.length > 1 ? 'en' : ''}</span>` : ''}
      </div>
      ${idea.attachments.length > 0 ? `<div class="idea-card__files">${idea.attachments.map((a) => `<a href="${escapeHtml(a.url)}" class="idea-card__file" target="_blank" rel="noopener">${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</a>`).join('')}</div>` : ''}
      ${renderIdeaStatusInfo(idea)}
      ${renderIdeaActions(idea)}
    </div>`;
}

function renderIdeas(ideas: IdeaData[]): void {
  loadedIdeas = ideas;
  const container = document.getElementById('ideas-container');
  if (!container) return;

  if (ideas.length === 0) {
    container.innerHTML = `
      <div class="projektidee-meine-page__empty">
        <p>Du hast noch keine Projektideen eingereicht.</p>
        <a href="/projektidee/einreichen" class="button button--accent button--lg">Idee einreichen</a>
      </div>`;
    return;
  }

  container.innerHTML = ideas.map(renderIdeaCard).join('');

  initEditButtons();
  initWithdrawButtons();
}

let editEditorInstance: CKEditorInstance | null = null;

function openDialogWithBackdrop(dialog: HTMLDialogElement): void {
  let backdrop = dialog.previousElementSibling;
  if (!backdrop?.classList.contains('modal-backdrop')) {
    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    dialog.insertAdjacentElement('beforebegin', backdrop);
    backdrop.addEventListener('click', () => {
      dialog.close();
    });
  }
  (backdrop as HTMLElement).style.display = 'block';
  dialog.show();

  const onKey = (e: KeyboardEvent): void => {
    if (e.key === 'Escape' && dialog.open) {
      if (!document.querySelector('.cke_dialog_container')) {
        dialog.close();
      }
    }
  };
  dialog.addEventListener('keydown', onKey);
  dialog.addEventListener(
    'close',
    () => {
      (backdrop as HTMLElement).style.display = 'none';
      dialog.removeEventListener('keydown', onKey);
    },
    { once: true },
  );
}

function buildEditFormData(
  title: string,
  description: string,
  category: string,
  filesToRemove: string[],
  newFilesInput: HTMLInputElement | null,
  headerFile: File | null,
  removeHeader: boolean,
): FormData {
  const formData = new FormData();
  formData.append('title', title);
  formData.append('description', description);
  formData.append('category', category);
  filesToRemove.forEach((attId) => {
    formData.append('removeFiles[]', attId);
  });
  const newFiles = newFilesInput?.files;
  if (newFiles) {
    for (let i = 0; i < newFiles.length; i++) {
      formData.append('files[]', newFiles[i]);
    }
  }
  if (headerFile) {
    formData.append('headerImage', headerFile);
  }
  if (removeHeader) {
    formData.append('removeHeaderImage', '1');
  }
  return formData;
}

async function submitEditIdea(
  ideaId: string,
  titleInput: HTMLInputElement,
  categorySelect: HTMLSelectElement,
  newFilesInput: HTMLInputElement | null,
  filesToRemove: string[],
  headerFile: File | null,
  removeHeader: boolean,
  modal: HTMLDialogElement | null,
): Promise<void> {
  const newTitle = titleInput.value.trim();
  const newCategory = categorySelect.value;
  const newDescription = editEditorInstance?.getData() ?? '';
  if (!newTitle || !newCategory || !newDescription) return;

  const formData = buildEditFormData(
    newTitle,
    newDescription,
    newCategory,
    filesToRemove,
    newFilesInput,
    headerFile,
    removeHeader,
  );
  const res = await apiFetch(`${API_BASE}/${ideaId}`, { method: 'POST', body: formData });
  if (res.ok) {
    window.location.reload();
  } else {
    const msg = await extractErrorMessage(res);
    showInlineError(msg, modal);
  }
}

function initEditButtons(): void {
  const modal = document.getElementById('edit-idea-modal') as HTMLDialogElement | null;
  const titleInput = document.getElementById('edit-idea-title') as HTMLInputElement | null;
  const categorySelect = document.getElementById('edit-idea-category') as HTMLSelectElement | null;
  const submitBtn = document.getElementById('edit-idea-submit');
  const existingFilesContainer = document.getElementById('edit-existing-files');
  const newFilesInput = document.getElementById('edit-new-files') as HTMLInputElement | null;
  if (!modal || !titleInput || !categorySelect || !submitBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  let filesToRemove: string[] = [];
  let editHeaderImageFile: File | null = null;
  let removeHeaderImage = false;

  const editHeaderPreview = document.getElementById('edit-header-image-preview');
  const editHeaderPreviewImg = document.getElementById('edit-header-image-preview-img') as HTMLImageElement | null;
  const editHeaderDropzone = document.getElementById('edit-header-image-dropzone');
  const editHeaderInput = document.getElementById('edit-header-image-input') as HTMLInputElement | null;
  const editHeaderRemoveBtn = document.getElementById('edit-header-image-remove');

  if (editHeaderDropzone && editHeaderInput) {
    editHeaderDropzone.addEventListener('click', () => {
      editHeaderInput.click();
    });
    editHeaderDropzone.addEventListener('dragover', (e) => {
      e.preventDefault();
      editHeaderDropzone.classList.add('header-image-upload__dropzone--active');
    });
    editHeaderDropzone.addEventListener('dragleave', () => {
      editHeaderDropzone.classList.remove('header-image-upload__dropzone--active');
    });
    editHeaderDropzone.addEventListener('drop', (e) => {
      e.preventDefault();
      editHeaderDropzone.classList.remove('header-image-upload__dropzone--active');
      const file = e instanceof DragEvent ? e.dataTransfer?.files[0] : undefined;
      if (file) setEditHeaderImage(file);
    });
    editHeaderInput.addEventListener('change', () => {
      const file = editHeaderInput.files?.[0];
      if (file) setEditHeaderImage(file);
    });
  }
  if (editHeaderRemoveBtn) {
    editHeaderRemoveBtn.addEventListener('click', () => {
      editHeaderImageFile = null;
      removeHeaderImage = true;
      if (editHeaderPreview) editHeaderPreview.hidden = true;
      if (editHeaderDropzone) editHeaderDropzone.hidden = false;
    });
  }

  function setEditHeaderImage(file: File): void {
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (allowedTypes.indexOf(file.type) === -1) {
      showInlineError('Titelbild muss JPEG, PNG, GIF oder WebP sein.', modal);
      return;
    }
    if (file.size > 10_000_000) {
      showInlineError('Titelbild darf maximal 10 MB gross sein.', modal);
      return;
    }
    editHeaderImageFile = file;
    removeHeaderImage = false;
    if (editHeaderPreviewImg && editHeaderPreview && editHeaderDropzone) {
      editHeaderPreviewImg.src = URL.createObjectURL(file);
      editHeaderPreview.hidden = false;
      editHeaderDropzone.hidden = true;
    }
  }

  document.querySelectorAll<HTMLElement>('[data-edit-idea]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const ideaId = btn.dataset['editIdea'] ?? '';
      const idea = loadedIdeas.find((i) => i.id === ideaId);
      if (!idea) return;

      filesToRemove = [];
      editHeaderImageFile = null;
      removeHeaderImage = false;
      titleInput.value = idea.title;
      categorySelect.value = idea.category;
      if (newFilesInput) newFilesInput.value = '';

      // Show existing header image or dropzone
      if (editHeaderPreview && editHeaderPreviewImg && editHeaderDropzone) {
        if (idea.headerImageUrl) {
          editHeaderPreviewImg.src = idea.headerImageUrl;
          editHeaderPreview.hidden = false;
          editHeaderDropzone.hidden = true;
        } else {
          editHeaderPreviewImg.src = '';
          editHeaderPreview.hidden = true;
          editHeaderDropzone.hidden = false;
        }
      }

      // Render existing attachments with remove buttons
      if (existingFilesContainer) {
        if (idea.attachments.length === 0) {
          existingFilesContainer.innerHTML =
            '<p style="color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);">Keine Anhänge</p>';
        } else {
          existingFilesContainer.innerHTML = idea.attachments
            .map(
              (a) =>
                `<div class="edit-file-item" data-att-id="${escapeHtml(a.id)}">
              <span>${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</span>
              <button type="button" class="edit-file-item__remove" title="Entfernen">\u00D7</button>
            </div>`,
            )
            .join('');

          existingFilesContainer.querySelectorAll('.edit-file-item__remove').forEach((removeBtn) => {
            removeBtn.addEventListener('click', () => {
              const item = removeBtn.closest('.edit-file-item') as HTMLElement;
              const attId = item.dataset['attId'] ?? '';
              filesToRemove.push(attId);
              item.style.opacity = '0.3';
              item.style.textDecoration = 'line-through';
              (removeBtn as HTMLElement).style.display = 'none';
            });
          });
        }
      }

      openDialogWithBackdrop(modal);

      setTimeout(() => {
        const ckeditor = getCKEditor();
        if (ckeditor && !editEditorInstance) {
          editEditorInstance = ckeditor.replace('edit-idea-description', {
            toolbar: [
              { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
              { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
              { name: 'links', items: ['Link', 'Unlink'] },
            ],
            contentsCss: getCkeContentsCss(),
            removePlugins: 'elementspath',
            resize_enabled: false,
            versionCheck: false,
            height: 150,
          });
        }
        if (editEditorInstance) {
          editEditorInstance.setData(idea.description);
        }
      }, 100);

      const freshSubmit = submitBtn.cloneNode(true) as HTMLElement;
      submitBtn.replaceWith(freshSubmit);
      freshSubmit.addEventListener('click', () => {
        void submitEditIdea(
          ideaId,
          titleInput,
          categorySelect,
          newFilesInput,
          filesToRemove,
          editHeaderImageFile,
          removeHeaderImage,
          modal,
        );
      });
    });
  });
}

function initWithdrawButtons(): void {
  const modal = document.getElementById('withdraw-modal') as HTMLDialogElement | null;
  const nameEl = document.getElementById('withdraw-idea-name');
  const confirmBtn = document.getElementById('withdraw-confirm');
  if (!modal || !confirmBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.querySelectorAll<HTMLElement>('[data-withdraw-idea]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const ideaId = btn.dataset['withdrawIdea'] ?? '';
      const title = btn.dataset['title'] ?? '';
      if (nameEl) nameEl.textContent = `\u00AB${title}\u00BB`;

      const freshConfirm = confirmBtn.cloneNode(true) as HTMLElement;
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', () => {
        void apiFetch(`${API_BASE}/${ideaId}`, { method: 'DELETE' }).then((res) => {
          if (res.ok) window.location.reload();
          else
            void extractErrorMessage(res).then((msg) => {
              showInlineError(msg);
            });
        });
      });

      modal.showModal();
    });
  });
}

async function loadCategories(): Promise<void> {
  const select = document.getElementById('edit-idea-category') as HTMLSelectElement | null;
  if (!select) return;

  try {
    const res = await fetch(CATEGORY_API);
    const json = (await res.json()) as { data: { id: string; name: string }[] };
    json.data.forEach((cat) => {
      const option = document.createElement('option');
      option.value = cat.id;
      option.textContent = cat.name;
      select.appendChild(option);
    });
  } catch {
    // fail silently
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('ideas-container')) return;
  void loadCategories();
  void fetchIdeas()
    .then(renderIdeas)
    .catch(() => {
      showInlineError('Fehler beim Laden der Ideen.');
    });
});
