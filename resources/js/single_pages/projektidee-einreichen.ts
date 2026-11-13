import { apiFetch } from '../api-client';
import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/project-idea';
const CATEGORY_API = '/api/moderation/category';

const ERROR_ICON =
  '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';

interface Category {
  id: string;
  name: string;
  slug: string;
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

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: { message?: string } };
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string, container?: Element | null): void {
  const target = container ?? document.getElementById('form-error-container');
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

let editorInstance: CKEditorInstance | null = null;
const selectedFiles: File[] = [];
const MAX_FILES = 5;
const MAX_FILE_SIZE = 10_000_000;
let headerImageFile: File | null = null;

function initCKEditor(): void {
  const ckeditor = getCKEditor();
  if (!ckeditor || editorInstance) return;

  editorInstance = ckeditor.replace('idea-description', {
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

async function loadCategories(): Promise<void> {
  const select = document.getElementById('idea-category') as HTMLSelectElement | null;
  if (!select) return;

  try {
    const res = await fetch(CATEGORY_API);
    const json = (await res.json()) as { data: Category[] };
    json.data.forEach((cat) => {
      const option = document.createElement('option');
      option.value = cat.id;
      option.textContent = cat.name;
      select.appendChild(option);
    });
  } catch {
    // Categories will just be empty
  }
}

function initFileUpload(): void {
  const zone = document.getElementById('file-upload-zone');
  const input = document.getElementById('idea-files') as HTMLInputElement | null;
  const list = document.getElementById('file-list');
  if (!zone || !input || !list) return;

  zone.addEventListener('click', () => {
    input.click();
  });

  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    zone.classList.add('file-upload-zone--dragover');
  });

  zone.addEventListener('dragleave', () => {
    zone.classList.remove('file-upload-zone--dragover');
  });

  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('file-upload-zone--dragover');
    if (e.dataTransfer?.files) {
      addFiles(Array.from(e.dataTransfer.files));
    }
  });

  input.addEventListener('change', () => {
    if (input.files) {
      addFiles(Array.from(input.files));
    }
    input.value = '';
  });
}

function addFiles(files: File[]): void {
  for (const file of files) {
    if (selectedFiles.length >= MAX_FILES) {
      showInlineError(`Maximal ${String(MAX_FILES)} Dateien erlaubt.`);
      break;
    }
    if (file.size > MAX_FILE_SIZE) {
      showInlineError(`Die Datei \u00AB${file.name}\u00BB ist zu gross (max. 10 MB).`);
      continue;
    }
    selectedFiles.push(file);
  }
  renderFileList();
}

function renderFileList(): void {
  const list = document.getElementById('file-list');
  if (!list) return;

  if (selectedFiles.length === 0) {
    list.innerHTML = '';
    return;
  }

  list.innerHTML = selectedFiles
    .map(
      (file, idx) => `
    <div class="file-upload-zone__item">
      <span class="file-upload-zone__name">${escapeHtml(file.name)}</span>
      <span class="file-upload-zone__size">${(file.size / 1_000_000).toFixed(1)} MB</span>
      <button type="button" class="file-upload-zone__remove" data-idx="${String(idx)}" title="Entfernen">\u00D7</button>
    </div>`,
    )
    .join('');

  list.querySelectorAll('.file-upload-zone__remove').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = parseInt((btn as HTMLElement).dataset['idx'] ?? '0', 10);
      selectedFiles.splice(idx, 1);
      renderFileList();
    });
  });
}

function validateHeaderImage(file: File): Promise<string | null> {
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (allowedTypes.indexOf(file.type) === -1) {
    return Promise.resolve('Nur JPEG, PNG, GIF oder WebP erlaubt.');
  }
  if (file.size > 10 * 1024 * 1024) {
    return Promise.resolve('Maximale Dateigrösse: 10 MB.');
  }
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      resolve(img.naturalWidth < 800 ? 'Mindestbreite: 800 Pixel.' : null);
      URL.revokeObjectURL(img.src);
    };
    img.onerror = () => {
      resolve('Bild konnte nicht gelesen werden.');
    };
    img.src = URL.createObjectURL(file);
  });
}

function showHeaderImagePreview(file: File): void {
  const preview = document.getElementById('header-image-preview');
  const previewImg = document.getElementById('header-image-preview-img') as HTMLImageElement | null;
  const dropzone = document.getElementById('header-image-dropzone');
  if (!preview || !previewImg || !dropzone) return;

  previewImg.src = URL.createObjectURL(file);
  preview.hidden = false;
  dropzone.hidden = true;
}

function clearHeaderImagePreview(): void {
  const preview = document.getElementById('header-image-preview');
  const previewImg = document.getElementById('header-image-preview-img') as HTMLImageElement | null;
  const dropzone = document.getElementById('header-image-dropzone');
  if (!preview || !previewImg || !dropzone) return;

  if (previewImg.src.startsWith('blob:')) {
    URL.revokeObjectURL(previewImg.src);
  }
  previewImg.src = '';
  preview.hidden = true;
  dropzone.hidden = false;
  headerImageFile = null;
}

function handleHeaderImageFile(file: File): void {
  void validateHeaderImage(file).then((error) => {
    if (error) {
      showInlineError(error);
      return;
    }
    headerImageFile = file;
    showHeaderImagePreview(file);
  });
}

function initHeaderImageUpload(): void {
  const dropzone = document.getElementById('header-image-dropzone');
  const input = document.getElementById('header-image-input') as HTMLInputElement | null;
  const removeBtn = document.getElementById('header-image-remove');
  if (!dropzone || !input || !removeBtn) return;

  dropzone.addEventListener('click', () => {
    input.click();
  });

  dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('header-image-upload__dropzone--active');
  });

  dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('header-image-upload__dropzone--active');
  });

  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('header-image-upload__dropzone--active');
    const file = e instanceof DragEvent ? e.dataTransfer?.files[0] : undefined;
    if (file) {
      handleHeaderImageFile(file);
    }
  });

  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (file) {
      handleHeaderImageFile(file);
    }
    input.value = '';
  });

  removeBtn.addEventListener('click', () => {
    clearHeaderImagePreview();
  });
}

function handleResponse(res: Response, submitBtn: HTMLButtonElement | null): void {
  if (res.ok) {
    window.location.href = '/projektidee/meine';
  } else {
    void extractErrorMessage(res).then((msg) => {
      showInlineError(msg);
    });
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Idee einreichen';
    }
  }
}

function submitIdea(title: string, description: string, category: string, submitBtn: HTMLButtonElement | null): void {
  if (selectedFiles.length > 0 || headerImageFile !== null) {
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('category', category);
    selectedFiles.forEach((file) => {
      formData.append('files[]', file);
    });
    if (headerImageFile !== null) {
      formData.append('headerImage', headerImageFile);
    }

    void apiFetch(API_BASE, {
      method: 'POST',
      body: formData,
    }).then((res) => {
      handleResponse(res, submitBtn);
    });
  } else {
    void apiFetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title, description, category }),
    }).then((res) => {
      handleResponse(res, submitBtn);
    });
  }
}

function initForm(): void {
  const form = document.getElementById('idea-form') as HTMLFormElement | null;
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const titleInput = document.getElementById('idea-title') as HTMLInputElement;
    const categorySelect = document.getElementById('idea-category') as HTMLSelectElement;

    const title = titleInput.value.trim();
    const category = categorySelect.value;
    const description = editorInstance?.getData() ?? '';

    if (!title || !category || !description) {
      showInlineError('Bitte f\u00FClle alle Pflichtfelder aus.');
      return;
    }

    const submitBtn = document.getElementById('idea-submit') as HTMLButtonElement | null;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Wird eingereicht...';
    }

    submitIdea(title, description, category, submitBtn);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('idea-form')) return;
  void loadCategories();
  initCKEditor();
  initHeaderImageUpload();
  initFileUpload();
  initForm();
});
