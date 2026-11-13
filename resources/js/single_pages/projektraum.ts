import { apiFetch } from '../api-client';
import { escapeHtml, sanitizeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/project-space';

interface CollaboardLink {
  id: string;
  title: string;
  url: string;
}

interface UpdateAttachment {
  id: string;
  filename: string;
  mimeType: string;
  size: number;
  isImage: boolean;
  url: string;
}

interface StatusUpdateData {
  id: string;
  title: string;
  body: string;
  authorId: string;
  createdAt: string;
  attachments: UpdateAttachment[];
}

interface MemberData {
  id: string;
  name: string;
  role: string;
  avatarUrl: string;
}

interface MembershipRequestData {
  id: string;
  userId: string;
  userName: string;
  status: string;
}

interface IdeaAttachment {
  id: string;
  filename: string;
  mimeType: string;
  size: number;
  isImage: boolean;
  url: string;
}

interface ExpertRequestData {
  id: string;
  expertUserId: string;
  expertName: string;
  expertExpertise: string;
  message: string | null;
  status: string;
  requestedAt: string;
  decidedAt: string | null;
}

interface SpaceDetail {
  id: string;
  title: string;
  description: string;
  status: string;
  initiatorId: string;
  initiatorName: string;
  memberCount: number;
  categoryId: string;
  categoryName: string;
  categorySlug: string;
  collaboardLinks?: CollaboardLink[];
  statusUpdates?: StatusUpdateData[];
  members?: MemberData[];
  membershipRequests?: MembershipRequestData[];
  ideaAttachments?: IdeaAttachment[];
  expertRequests?: ExpertRequestData[];
  createdAt: string;
  userRole: string | null;
  currentUserId: string | null;
  headerImageUrl?: string | null;
  priority?: number;
}

const ERROR_ICON =
  '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';

async function extractErrorMessage(res: Response): Promise<string> {
  try {
    const json = (await res.json()) as { error?: { message?: string } };
    return json.error?.message ?? 'Ein unbekannter Fehler ist aufgetreten.';
  } catch {
    return 'Ein unbekannter Fehler ist aufgetreten.';
  }
}

function showInlineError(message: string, container?: Element | null): void {
  const target = container ?? document.querySelector('.projektraum-page__content');
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

  // Inside a modal, place above the footer; otherwise prepend to container
  const footer = target.querySelector('.modal__footer');
  if (footer) {
    footer.before(alert);
  } else {
    target.prepend(alert);
  }
  alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function handleApiError(res: Response, container?: Element | null): Promise<void> {
  const message = await extractErrorMessage(res);
  showInlineError(message, container);
}

function getSpaceId(): string {
  const parts = window.location.pathname.split('/');
  const viewIdx = parts.indexOf('view');
  return viewIdx >= 0 && parts[viewIdx + 1] ? parts[viewIdx + 1] : '';
}

function formatDateCH(isoDate: string): string {
  try {
    const date = new Date(isoDate);
    return date.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch {
    return isoDate;
  }
}

function formatDateTimeCH(isoDate: string): string {
  try {
    const date = new Date(isoDate);
    return (
      date.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
      ', ' +
      date.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })
    );
  } catch {
    return isoDate;
  }
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map((w) => w.charAt(0))
    .join('')
    .substring(0, 2)
    .toUpperCase();
}

async function fetchSpace(id: string): Promise<SpaceDetail> {
  const response = await fetch(`${API_BASE}/${id}`);
  const json = (await response.json()) as { data: SpaceDetail };
  return json.data;
}

function canManage(space: SpaceDetail): boolean {
  return space.userRole === 'initiator' || space.userRole === 'admin';
}

// --- Render functions ---

interface StatusAction {
  label: string;
  newStatus: string;
  modalTitle: string;
  modalMessage: string;
  confirmLabel: string;
}

function getStatusActions(space: SpaceDetail): StatusAction[] {
  const actions: StatusAction[] = [];
  const isAdmin = space.userRole === 'admin';

  if (space.status === 'draft') {
    actions.push({
      label: 'Aktivieren',
      newStatus: 'active',
      modalTitle: 'Innovation aktivieren',
      modalMessage: 'Die Innovation wird öffentlich sichtbar und Mitglieder können beitreten.',
      confirmLabel: 'Aktivieren',
    });
  }
  if (space.status === 'active') {
    actions.push({
      label: 'Abschliessen',
      newStatus: 'completed',
      modalTitle: 'Innovation abschliessen',
      modalMessage: 'Die Innovation wird als abgeschlossen markiert. Du kannst ihn später wieder öffnen.',
      confirmLabel: 'Abschliessen',
    });
  }
  if (space.status === 'completed') {
    actions.push({
      label: 'Wieder öffnen',
      newStatus: 'active',
      modalTitle: 'Innovation wieder öffnen',
      modalMessage: 'Die Innovation wird wieder aktiv und Mitglieder können erneut beitreten.',
      confirmLabel: 'Wieder öffnen',
    });
    if (isAdmin) {
      actions.push({
        label: 'Archivieren',
        newStatus: 'archived',
        modalTitle: 'Innovation archivieren',
        modalMessage: 'Die Innovation wird archiviert und ist nur noch für Administratoren und Moderatoren sichtbar.',
        confirmLabel: 'Archivieren',
      });
    }
  }
  if (space.status === 'archived' && isAdmin) {
    actions.push({
      label: 'Dearchivieren',
      newStatus: 'completed',
      modalTitle: 'Innovation dearchivieren',
      modalMessage:
        'Die Innovation wird wieder als abgeschlossen sichtbar. Er kann danach auch wieder geöffnet werden.',
      confirmLabel: 'Dearchivieren',
    });
  }
  return actions;
}

function canShowStatusActions(space: SpaceDetail): boolean {
  if (canManage(space)) return true;
  return space.userRole === 'admin' && getStatusActions(space).length > 0;
}

function isEditableStatus(status: string): boolean {
  return status === 'active' || status === 'draft';
}

function renderManageActions(space: SpaceDetail, actionsEl: HTMLElement): void {
  const manage = canManage(space);
  const actions = getStatusActions(space);
  const statusButtons = actions
    .map(
      (a, i) => `<button class="button button--muted button--sm" data-status-action="${String(i)}">${a.label}</button>`,
    )
    .join('');
  const writeUpdateBtn =
    manage && isEditableStatus(space.status)
      ? '<button class="button button--accent button--sm" id="btn-write-update">+ Update schreiben</button>'
      : '';
  const deleteBtn =
    space.status === 'archived' && space.userRole === 'admin'
      ? '<button class="button button--danger button--sm" id="btn-delete-space">Löschen</button>'
      : '';

  actionsEl.innerHTML = `${statusButtons}${writeUpdateBtn}${deleteBtn}`;
  initStatusButtons(space);
  if (manage && isEditableStatus(space.status)) initWriteUpdateButton(space);
  if (space.status === 'archived' && space.userRole === 'admin') initDeleteSpaceButton(space);
}

function renderHeaderActions(space: SpaceDetail, actionsEl: HTMLElement): void {
  if (canShowStatusActions(space)) {
    renderManageActions(space, actionsEl);
  } else if (space.userRole === 'visitor' && space.status === 'active') {
    actionsEl.innerHTML = '<button class="button button--accent button--sm" id="btn-join-request">Mitmachen</button>';
    initJoinButton(space);
  } else if (space.userRole === null && space.status === 'active') {
    actionsEl.innerHTML = '<a href="/login" class="button button--accent button--sm">Anmelden um mitzumachen</a>';
  }
}

function renderHeader(space: SpaceDetail): void {
  const titleEl = document.getElementById('space-title');
  const descEl = document.getElementById('space-description');
  const statusEl = document.getElementById('space-status-badge');
  const actionsEl = document.getElementById('space-actions');

  if (titleEl) titleEl.textContent = space.title;
  if (descEl) descEl.innerHTML = sanitizeHtml(space.description);

  const statusLabels: Record<string, string> = {
    draft: 'Entwurf',
    active: 'Aktiv',
    completed: 'Abgeschlossen',
    archived: 'Archiviert',
  };
  if (statusEl) {
    const variant = space.status === 'active' ? 'success' : 'neutral';
    const categoryPill = space.categoryName
      ? `<span class="project-card__badge project-card__badge--category">${escapeHtml(space.categoryName)}</span> `
      : '';
    statusEl.innerHTML =
      categoryPill +
      `<span class="project-card__badge project-card__badge--${variant}">${statusLabels[space.status] ?? escapeHtml(space.status)}</span>`;
  }

  if (actionsEl) renderHeaderActions(space, actionsEl);
}

function renderCollaboards(space: SpaceDetail): void {
  const container = document.getElementById('collaboard-panel-container');
  if (!container) return;

  const links = space.collaboardLinks ?? [];
  const manage = canManage(space);

  if (!space.collaboardLinks) {
    if (space.userRole === 'visitor') {
      container.innerHTML =
        '<p class="projektraum-page__notice">Tritt der Innovation bei, um die Collaboards zu sehen.</p>';
    } else {
      container.innerHTML = '';
    }
    return;
  }

  let linksHtml: string;
  if (links.length === 0) {
    linksHtml = '<p class="projektraum-page__empty">Keine Collaboards vorhanden.</p>';
  } else {
    linksHtml = links
      .map(
        (link) => `
      <div class="collaboard-item" data-id="${escapeHtml(link.id)}" data-url="${escapeHtml(link.url)}">
        <div class="collaboard-item__icon"><span class="collaboard-item__icon-text">C</span></div>
        <div class="collaboard-item__info">
          <span class="collaboard-item__title">${escapeHtml(link.title)}</span>
          <span class="collaboard-item__url">${escapeHtml(link.url)}</span>
        </div>
        <button type="button" class="button button--muted button--sm collaboard-item__action">Öffnen</button>
        ${manage ? `<button type="button" class="button button--danger button--sm collaboard-item__remove" data-link-id="${escapeHtml(link.id)}" title="Board «${escapeHtml(link.title)}» entfernen">Entfernen</button>` : ''}
      </div>`,
      )
      .join('');
  }

  container.innerHTML = `
    <section class="collaboard-panel">
      <div class="collaboard-panel__header">
        <h3 class="collaboard-panel__title">Collaboards</h3>
        ${manage ? '<button class="button button--muted button--sm" id="btn-add-collaboard">+ Board hinzufügen</button>' : ''}
      </div>
      <div class="collaboard-panel__list">${linksHtml}</div>
      <div class="collaboard-panel__iframe-container" id="collaboard-iframe-container" hidden>
        <div class="collaboard-panel__iframe-toolbar">
          <span class="collaboard-panel__iframe-title" id="collaboard-iframe-title"></span>
          <div class="collaboard-panel__iframe-actions">
            <a class="button button--muted button--sm collaboard-panel__iframe-newtab" id="collaboard-iframe-newtab" href="#" target="_blank" rel="noopener">In neuem Tab öffnen ↑</a>
            <button class="button button--muted button--sm collaboard-panel__iframe-close" id="collaboard-iframe-close">Schliessen ×</button>
          </div>
        </div>
        <iframe class="collaboard-panel__iframe" id="collaboard-iframe" src="" title="Collaboard"></iframe>
      </div>
    </section>`;

  initCollaboardClicks();
  if (manage) {
    initAddCollaboardButton(space);
    initRemoveCollaboardButtons(space);
  }
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return String(bytes) + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function renderUpdateAttachments(attachments: UpdateAttachment[]): string {
  if (attachments.length === 0) return '';
  return `<div class="status-update-entry__attachments">
    ${attachments
      .map(
        (a) =>
          `<a href="${escapeHtml(a.url)}" target="_blank" rel="noopener" class="status-update-entry__attachment">
          <span>${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</span>
          <span class="status-update-entry__attachment-size">${formatFileSize(a.size)}</span>
        </a>`,
      )
      .join('')}
  </div>`;
}

function renderUpdates(space: SpaceDetail): void {
  const container = document.getElementById('status-update-timeline-container');
  if (!container) return;

  const updates = space.statusUpdates ?? [];
  const manage = canManage(space);

  let updatesHtml: string;
  if (updates.length === 0) {
    updatesHtml = '<p class="projektraum-page__empty">Keine Updates vorhanden.</p>';
  } else {
    updatesHtml = `<div class="status-update-timeline__list">
      ${updates
        .map(
          (u, i) => `
        <div class="status-update-entry${i === 0 ? ' status-update-entry--latest status-update-entry--open' : ''}" data-update-id="${escapeHtml(u.id)}">
          <div class="status-update-entry__dot"></div>
          <div class="status-update-entry__content">
            <span class="status-update-entry__meta">${formatDateTimeCH(u.createdAt)}</span>
            <div class="status-update-entry__header" data-toggle-update>
              <h4 class="status-update-entry__title">${escapeHtml(u.title)}</h4>
              <span class="status-update-entry__chevron"></span>
            </div>
            <div class="status-update-entry__collapsible">
              <div class="status-update-entry__body">${sanitizeHtml(u.body)}</div>
              ${renderUpdateAttachments(u.attachments)}
              ${
                manage
                  ? `<div class="status-update-entry__actions">
                <button type="button" class="button button--muted button--sm" data-edit-update="${escapeHtml(u.id)}" data-title="${escapeHtml(u.title)}" data-body="${escapeHtml(u.body)}">Bearbeiten</button>
                <button type="button" class="button button--danger button--sm" data-delete-update="${escapeHtml(u.id)}" data-title="${escapeHtml(u.title)}">Löschen</button>
              </div>`
                  : ''
              }
            </div>
          </div>
        </div>`,
        )
        .join('')}
    </div>`;
  }

  container.innerHTML = `
    <section class="status-update-timeline">
      <div class="status-update-timeline__header">
        <h3 class="status-update-timeline__title">Projekt-Updates</h3>
      </div>
      ${updatesHtml}
    </section>`;

  container.querySelectorAll<HTMLElement>('[data-toggle-update]').forEach((header) => {
    header.addEventListener('click', () => {
      header.closest('.status-update-entry')?.classList.toggle('status-update-entry--open');
    });
  });

  if (manage) {
    initEditUpdateButtons(space);
    initDeleteUpdateButtons(space);
  }
}

function renderIdeaAttachments(space: SpaceDetail): void {
  const container = document.getElementById('idea-attachments-container');
  if (!container) return;

  const attachments = space.ideaAttachments ?? [];
  if (attachments.length === 0) return;

  container.innerHTML = `
    <section class="idea-attachments-panel">
      <h3 class="idea-attachments-panel__title">Ursprüngliche Anhänge</h3>
      <div class="idea-attachments-panel__list">
        ${attachments
          .map(
            (a) =>
              `<a href="${escapeHtml(a.url)}" class="idea-attachments-panel__item" target="_blank" rel="noopener">
              <span>${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</span>
              <span class="idea-attachments-panel__size">${a.size < 1000000 ? String(Math.round(a.size / 1000)) + ' KB' : (a.size / 1000000).toFixed(1) + ' MB'}</span>
            </a>`,
          )
          .join('')}
      </div>
    </section>`;
}

// --- Header image ---

const HEADER_IMAGE_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const HEADER_IMAGE_MAX_BYTES = 10 * 1024 * 1024;
const HEADER_IMAGE_MIN_WIDTH = 800;

function validateHeaderImage(file: File): Promise<string | null> {
  if (HEADER_IMAGE_ALLOWED_TYPES.indexOf(file.type) === -1) {
    return Promise.resolve('Nur JPEG, PNG, GIF oder WebP erlaubt.');
  }
  if (file.size > HEADER_IMAGE_MAX_BYTES) {
    return Promise.resolve('Maximale Dateigrösse: 10 MB.');
  }
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      resolve(img.naturalWidth < HEADER_IMAGE_MIN_WIDTH ? 'Mindestbreite: 800 Pixel.' : null);
      URL.revokeObjectURL(img.src);
    };
    img.onerror = () => {
      resolve('Bild konnte nicht gelesen werden.');
    };
    img.src = URL.createObjectURL(file);
  });
}

function renderHero(space: SpaceDetail): void {
  const container = document.getElementById('projektraum-hero-container');
  if (!container) return;

  const manage = canManage(space);
  const hasImage = Boolean(space.headerImageUrl);

  if (hasImage) {
    container.innerHTML = `
      <div class="projektraum-hero">
        <img src="${escapeHtml(space.headerImageUrl ?? '')}" alt="" class="projektraum-hero__image" id="hero-image">
        ${
          manage
            ? `<div class="projektraum-hero__overlay" id="hero-overlay">
                <button class="projektraum-hero__edit button button--accent button--sm" id="hero-edit-btn" aria-label="Titelbild ändern">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  Titelbild ändern
                </button>
                <button class="projektraum-hero__remove button button--muted button--sm" id="hero-remove-btn" aria-label="Titelbild entfernen">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                  Entfernen
                </button>
              </div>`
            : ''
        }
      </div>`;
  } else if (manage) {
    container.innerHTML = `
      <button class="projektraum-hero__add button button--muted button--lg" id="hero-add-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
        Titelbild hinzufügen
      </button>`;
  } else {
    container.innerHTML = '';
    return;
  }

  // Hidden file input (shared for both add and edit)
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/jpeg,image/png,image/gif,image/webp';
  input.style.display = 'none';
  input.id = 'hero-file-input';
  container.appendChild(input);

  const triggerPicker = (): void => {
    input.value = '';
    input.click();
  };

  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) return;
    void validateHeaderImage(file).then((error) => {
      if (error) {
        showInlineError(error);
        return;
      }
      const formData = new FormData();
      formData.append('headerImage', file);
      void apiFetch(`${API_BASE}/${space.id}/header-image`, {
        method: 'POST',
        body: formData,
      }).then((res) => {
        if (res.ok) {
          void res.json().then((json: unknown) => {
            const data = json as { headerImageUrl?: string };
            const newUrl = data.headerImageUrl ?? URL.createObjectURL(file);
            // Update the hero image src if it exists, otherwise re-render
            const heroImg = document.getElementById('hero-image') as HTMLImageElement | null;
            if (heroImg) {
              heroImg.src = newUrl;
            } else {
              space.headerImageUrl = newUrl;
              renderHero(space);
            }
          });
        } else {
          void handleApiError(res);
        }
      });
    });
  });

  document.getElementById('hero-add-btn')?.addEventListener('click', triggerPicker);
  document.getElementById('hero-edit-btn')?.addEventListener('click', triggerPicker);

  document.getElementById('hero-remove-btn')?.addEventListener('click', () => {
    if (!confirm('Titelbild wirklich entfernen?')) return;
    void apiFetch(`${API_BASE}/${space.id}/header-image`, { method: 'DELETE' }).then((res) => {
      if (res.ok) {
        space.headerImageUrl = null;
        renderHero(space);
      } else {
        void handleApiError(res);
      }
    });
  });
}

function renderMemberRow(member: MemberData, space: SpaceDetail): string {
  const isInitiator = member.role === 'initiator';
  const isSelf = space.currentUserId !== null && member.id === space.currentUserId;
  const canManageSpace = canManage(space) || space.userRole === 'initiator';

  let actionButton = '';
  if (!isInitiator) {
    if (isSelf) {
      actionButton = `<button type="button" class="button button--danger button--sm" data-leave-space="${escapeHtml(space.id)}" title="Innovation verlassen">Verlassen</button>`;
    } else if (canManageSpace) {
      actionButton = `<button type="button" class="button button--danger button--sm" data-remove-member="${escapeHtml(member.id)}" data-member-name="${escapeHtml(member.name)}" title="${escapeHtml(member.name)} entfernen">Entfernen</button>`;
    }
  }

  return `
    <div class="project-space-sidebar__member-row">
      ${member.avatarUrl ? `<img class="project-space-sidebar__avatar-img" src="${escapeHtml(member.avatarUrl)}" alt="${escapeHtml(member.name)}">` : `<div class="project-space-sidebar__avatar">${escapeHtml(getInitials(member.name))}</div>`}
      <span class="project-space-sidebar__member-name">${escapeHtml(member.name)}${isInitiator ? ' (Initiator)' : ''}</span>
      ${actionButton}
    </div>`;
}

function renderSidebar(space: SpaceDetail): void {
  const container = document.getElementById('sidebar-container');
  if (!container) return;

  const members = space.members ?? [];
  const requests = (space.membershipRequests ?? []).filter((r) => r.status === 'pending');
  const manage = canManage(space);

  const membersHtml = members.map((member) => renderMemberRow(member, space)).join('');

  const requestsHtml =
    manage && requests.length > 0
      ? `
    <div class="project-space-sidebar__requests">
      <span class="project-space-sidebar__label">Anfragen (${String(requests.length)})</span>
      ${requests
        .map(
          (req) => `
        <div class="join-request-card" data-request-id="${escapeHtml(req.id)}">
          <div class="join-request-card__user">
            <span class="join-request-card__name">${escapeHtml(req.userName)}</span>
          </div>
          <div class="join-request-card__actions">
            <button class="unstyled-button join-request-card__btn join-request-card__btn--accept" data-action="accept" title="Anfrage von ${escapeHtml(req.userName)} annehmen">✓</button>
            <button class="unstyled-button join-request-card__btn join-request-card__btn--reject" data-action="reject" title="Anfrage von ${escapeHtml(req.userName)} ablehnen">✕</button>
          </div>
        </div>`,
        )
        .join('')}
    </div>`
      : '';

  container.innerHTML = `
    <aside class="project-space-sidebar">
      <h3 class="project-space-sidebar__section-title">Team</h3>
      ${
        members.length > 0
          ? `
        <div class="project-space-sidebar__members">
          <span class="project-space-sidebar__label">Mitglieder (${String(members.length)})</span>
          ${membersHtml}
        </div>`
          : ''
      }
      ${requestsHtml}
      <div class="project-space-sidebar__stats">
        <span class="project-space-sidebar__label">Statistik</span>
        <div class="project-space-sidebar__stat-row"><span>Mitglieder</span><span>${String(space.memberCount)}</span></div>
        <div class="project-space-sidebar__stat-row"><span>Updates</span><span>${String((space.statusUpdates ?? []).length)}</span></div>
        <div class="project-space-sidebar__stat-row"><span>Erstellt</span><span>${formatDateCH(space.createdAt)}</span></div>
      </div>
    </aside>`;

  // Leave space buttons (self, non-initiator members)
  container.querySelectorAll<HTMLElement>('[data-leave-space]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!confirm('Möchtest du die Innovation wirklich verlassen?')) return;
      void apiFetch(`${API_BASE}/${space.id}/membership`, { method: 'DELETE' }).then((res) => {
        if (res.ok) {
          window.location.href = '/innovationen';
        } else {
          void handleApiError(res);
        }
      });
    });
  });

  // Remove member buttons (admin/initiator action)
  container.querySelectorAll<HTMLElement>('[data-remove-member]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const memberId = btn.dataset['removeMember'] ?? '';
      const memberName = btn.dataset['memberName'] ?? '';
      if (!confirm(`Möchtest du ${memberName} wirklich aus der Innovation entfernen?`)) return;
      void apiFetch(`${API_BASE}/${space.id}/member/${memberId}`, { method: 'DELETE' }).then((res) => {
        if (res.ok) {
          window.location.reload();
        } else {
          void handleApiError(res);
        }
      });
    });
  });

  if (manage) {
    initJoinRequestActions(space.id);
    renderExpertSection(space);
  }
  if (space.userRole === 'admin') {
    renderPrioritySection(space);
  }
}

function renderExpertSection(space: SpaceDetail): void {
  const sidebar = document.querySelector('.project-space-sidebar');
  if (!sidebar) return;

  const pendingRequests = (space.expertRequests ?? []).filter((r) => r.status === 'pending');

  const pendingHtml =
    pendingRequests.length > 0
      ? `<div class="project-space-sidebar__expert-requests">
          <span class="project-space-sidebar__label">Ausstehende Anfragen (${String(pendingRequests.length)})</span>
          ${pendingRequests
            .map(
              (req) => `
            <div class="project-space-sidebar__expert-request-row">
              <div>
                <span class="project-space-sidebar__member-name">${escapeHtml(req.expertName)}</span>
                <span class="project-space-sidebar__expert-expertise">${escapeHtml(req.expertExpertise)}</span>
              </div>
              <span class="badge badge--neutral badge--sm">Ausstehend</span>
            </div>`,
            )
            .join('')}
        </div>`
      : '';

  const sectionEl = document.createElement('div');
  sectionEl.className = 'project-space-sidebar__expert-section';
  sectionEl.innerHTML = `
    <h3 class="project-space-sidebar__section-title">Experten anfragen</h3>
    <div class="project-space-sidebar__expert-form">
      <div class="form-field" style="margin-bottom: var(--space-3);">
        <label class="project-space-sidebar__label" for="expert-select">Experte auswählen</label>
        <select id="expert-select" class="input">
          <option value="">Wird geladen…</option>
        </select>
      </div>
      <div class="form-field" style="margin-bottom: var(--space-3);">
        <label class="project-space-sidebar__label" for="expert-message">Nachricht (optional)</label>
        <textarea id="expert-message" class="textarea" rows="3" placeholder="Nachricht an den Experten…"></textarea>
      </div>
      <button class="button button--accent button--sm" id="btn-request-expert">Anfragen</button>
    </div>
    ${pendingHtml}`;

  sidebar.appendChild(sectionEl);

  // Load available experts
  void fetch(`${API_BASE}/${space.id}/experts`)
    .then((res) =>
      res.ok
        ? (res.json() as Promise<{ data: { userId: string; name: string; expertise: string }[] }>)
        : Promise.reject(new Error('Failed to load experts')),
    )
    .then((json) => {
      const select = document.getElementById('expert-select') as HTMLSelectElement | null;
      if (!select) return;
      const experts = json.data;
      if (experts.length === 0) {
        select.innerHTML = '<option value="">Keine Experten verfügbar</option>';
        return;
      }
      select.innerHTML =
        '<option value="">Bitte auswählen…</option>' +
        experts
          .map(
            (e) =>
              `<option value="${escapeHtml(e.userId)}">${escapeHtml(e.name)} – ${escapeHtml(e.expertise)}</option>`,
          )
          .join('');
    })
    .catch(() => {
      const select = document.getElementById('expert-select') as HTMLSelectElement | null;
      if (select) select.innerHTML = '<option value="">Fehler beim Laden</option>';
    });

  // Handle request button
  document.getElementById('btn-request-expert')?.addEventListener('click', () => {
    const select = document.getElementById('expert-select') as HTMLSelectElement | null;
    const messageEl = document.getElementById('expert-message') as HTMLTextAreaElement | null;
    const expertUserId = select?.value ?? '';
    if (!expertUserId) return;

    const message = messageEl?.value.trim() || null;

    void apiFetch(`${API_BASE}/${space.id}/expert-request`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ expertUserId, message }),
    }).then((res) => {
      if (res.ok) window.location.reload();
      else void handleApiError(res);
    });
  });
}

// --- Interaction handlers ---

function initCollaboardClicks(): void {
  const container = document.getElementById('collaboard-iframe-container');
  const iframe = document.getElementById('collaboard-iframe') as HTMLIFrameElement | null;
  const titleEl = document.getElementById('collaboard-iframe-title');
  const newtabEl = document.getElementById('collaboard-iframe-newtab') as HTMLAnchorElement | null;
  const closeBtn = document.getElementById('collaboard-iframe-close');

  document.querySelectorAll('.collaboard-item').forEach((item) => {
    item.addEventListener('click', (e) => {
      if ((e.target as HTMLElement).closest('.collaboard-item__remove')) return;
      const el = item as HTMLElement;
      const url = el.dataset['url'] ?? '';
      const title = el.querySelector('.collaboard-item__title')?.textContent ?? '';
      if (container && iframe && titleEl && newtabEl) {
        container.hidden = false;
        iframe.src = url;
        titleEl.textContent = title;
        newtabEl.href = url;
      }
      document.querySelectorAll('.collaboard-item').forEach((i) => {
        i.classList.remove('collaboard-item--active');
      });
      el.classList.add('collaboard-item--active');
    });
  });

  closeBtn?.addEventListener('click', () => {
    if (container && iframe) {
      container.hidden = true;
      iframe.src = '';
    }
    document.querySelectorAll('.collaboard-item').forEach((i) => {
      i.classList.remove('collaboard-item--active');
    });
  });
}

function initAddCollaboardButton(space: SpaceDetail): void {
  const modal = document.getElementById('collaboard-modal') as HTMLDialogElement | null;
  const submitBtn = document.getElementById('collaboard-submit');
  const titleInput = document.getElementById('collaboard-title') as HTMLInputElement | null;
  const urlInput = document.getElementById('collaboard-url') as HTMLInputElement | null;

  document.getElementById('btn-add-collaboard')?.addEventListener('click', () => {
    if (!modal) return;
    if (titleInput) titleInput.value = '';
    if (urlInput) urlInput.value = '';
    modal.showModal();
  });

  modal?.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  submitBtn?.addEventListener('click', () => {
    const title = titleInput?.value.trim() ?? '';
    const url = urlInput?.value.trim() ?? '';
    if (!title || !url) return;

    void apiFetch(`${API_BASE}/${space.id}/collaboard`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title, url }),
    }).then((res) => {
      if (res.ok) window.location.reload();
      else void handleApiError(res, modal);
    });
  });
}

function initRemoveCollaboardButtons(space: SpaceDetail): void {
  document.querySelectorAll('.collaboard-item__remove').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const linkId = (btn as HTMLElement).dataset['linkId'] ?? '';
      if (!confirm('Board wirklich entfernen?')) return;
      void apiFetch(`${API_BASE}/${space.id}/collaboard/${linkId}`, { method: 'DELETE' }).then((res) => {
        if (res.ok) window.location.reload();
        else void handleApiError(res);
      });
    });
  });
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
    | (CKEditorStatic & { config?: Record<string, unknown>; basePath?: string })
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

let editorInstance: CKEditorInstance | null = null;

function initCKEditor(): void {
  const ckeditor = getCKEditor();
  if (!ckeditor || editorInstance) return;

  editorInstance = ckeditor.replace('update-body', {
    toolbar: [
      { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline'] },
      { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
      { name: 'links', items: ['Link', 'Unlink'] },
      { name: 'styles', items: ['Format'] },
      { name: 'tools', items: ['Maximize'] },
    ],
    contentsCss: getCkeContentsCss(),
    removePlugins: 'elementspath',
    resize_enabled: false,
    versionCheck: false,
    height: 200,
  });
}

/**
 * Open a <dialog> with .show() instead of .showModal() so that CKEditor
 * dialogs (appended to <body>) are not trapped behind the top-layer.
 * Adds a manual backdrop and Escape-key listener.
 */
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
      // Only close if no CKEditor dialog is open
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

function getCkeditorContent(): string {
  if (editorInstance) {
    return editorInstance.getData();
  }
  const textarea = document.getElementById('update-body') as HTMLTextAreaElement | null;
  return textarea?.value ?? '';
}

const selectedUpdateFiles: File[] = [];
const MAX_UPDATE_FILES = 5;
const MAX_UPDATE_FILE_SIZE = 10_000_000;

function resetUpdateModal(): void {
  const existingContainer = document.getElementById('update-existing-files-container');
  const existingFiles = document.getElementById('update-existing-files');
  if (existingContainer) existingContainer.hidden = true;
  if (existingFiles) existingFiles.innerHTML = '';
  selectedUpdateFiles.length = 0;
  renderUpdateFileList();
}

function addUpdateFiles(files: File[]): void {
  for (const file of files) {
    if (selectedUpdateFiles.length >= MAX_UPDATE_FILES) {
      break;
    }
    if (file.size > MAX_UPDATE_FILE_SIZE) {
      continue;
    }
    selectedUpdateFiles.push(file);
  }
  renderUpdateFileList();
}

function renderUpdateFileList(): void {
  const list = document.getElementById('update-file-list');
  if (!list) return;

  if (selectedUpdateFiles.length === 0) {
    list.innerHTML = '';
    return;
  }

  list.innerHTML = selectedUpdateFiles
    .map(
      (file, idx) => `
    <div class="file-upload-zone__item">
      <span class="file-upload-zone__name">${escapeHtml(file.name)}</span>
      <span class="file-upload-zone__size">${(file.size / 1_000_000).toFixed(1)} MB</span>
      <button type="button" class="unstyled-button file-upload-zone__remove" data-idx="${String(idx)}" title="Entfernen">\u00D7</button>
    </div>`,
    )
    .join('');

  list.querySelectorAll('.file-upload-zone__remove').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = parseInt((btn as HTMLElement).dataset['idx'] ?? '0', 10);
      selectedUpdateFiles.splice(idx, 1);
      renderUpdateFileList();
    });
  });
}

function initUpdateFileZone(): void {
  const zone = document.getElementById('update-file-zone');
  const input = document.getElementById('update-files') as HTMLInputElement | null;
  if (!zone || !input) return;

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
      addUpdateFiles(Array.from(e.dataTransfer.files));
    }
  });
  input.addEventListener('change', () => {
    if (input.files) {
      addUpdateFiles(Array.from(input.files));
    }
    input.value = '';
  });
}

function initWriteUpdateButton(space: SpaceDetail): void {
  const modal = document.getElementById('update-modal') as HTMLDialogElement | null;
  const submitBtn = document.getElementById('update-submit');
  const titleInput = document.getElementById('update-title') as HTMLInputElement | null;

  initUpdateFileZone();

  document.getElementById('btn-write-update')?.addEventListener('click', () => {
    if (!modal) return;
    if (titleInput) titleInput.value = '';
    if (editorInstance) editorInstance.setData('');
    resetUpdateModal();
    openDialogWithBackdrop(modal);
    setTimeout(() => {
      initCKEditor();
    }, 100);
  });

  modal?.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  submitBtn?.addEventListener('click', () => {
    const title = titleInput?.value.trim() ?? '';
    const body = getCkeditorContent();
    if (!title || !body) return;

    const formData = buildUpdateFormData(title, body, []);
    void apiFetch(`${API_BASE}/${space.id}/status-update`, {
      method: 'POST',
      body: formData,
    }).then((res) => {
      if (res.ok) window.location.reload();
      else void handleApiError(res, modal);
    });
  });
}

function buildUpdateFormData(title: string, body: string, filesToRemove: string[]): FormData {
  const formData = new FormData();
  formData.append('title', title);
  formData.append('body', body);
  filesToRemove.forEach((attId) => {
    formData.append('removeFiles[]', attId);
  });
  selectedUpdateFiles.forEach((file) => {
    formData.append('files[]', file);
  });
  return formData;
}

function renderExistingUpdateAttachments(
  attachments: UpdateAttachment[],
  container: HTMLElement,
  listEl: HTMLElement,
  filesToRemove: string[],
): void {
  if (attachments.length === 0) return;
  container.hidden = false;
  listEl.innerHTML = attachments
    .map(
      (a) =>
        `<div class="edit-file-item" data-att-id="${escapeHtml(a.id)}">
        <span>${a.isImage ? '\u{1F5BC}' : '\u{1F4C4}'} ${escapeHtml(a.filename)}</span>
        <button type="button" class="edit-file-item__remove" title="Entfernen">\u00D7</button>
      </div>`,
    )
    .join('');

  listEl.querySelectorAll('.edit-file-item__remove').forEach((removeBtn) => {
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

function initEditUpdateButtons(space: SpaceDetail): void {
  const modal = document.getElementById('update-modal') as HTMLDialogElement | null;
  const titleInput = document.getElementById('update-title') as HTMLInputElement | null;
  const submitBtn = document.getElementById('update-submit');
  const existingContainer = document.getElementById('update-existing-files-container');
  const existingFilesEl = document.getElementById('update-existing-files');

  document.querySelectorAll<HTMLElement>('[data-edit-update]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!modal || !titleInput || !submitBtn) return;
      handleEditUpdateClick(btn, space, modal, titleInput, submitBtn, existingContainer, existingFilesEl);
    });
  });
}

function handleEditUpdateClick(
  btn: HTMLElement,
  space: SpaceDetail,
  modal: HTMLDialogElement,
  titleInput: HTMLInputElement,
  submitBtn: HTMLElement,
  existingContainer: HTMLElement | null,
  existingFilesEl: HTMLElement | null,
): void {
  const updateId = btn.dataset['editUpdate'] ?? '';
  const title = btn.dataset['title'] ?? '';
  const body = btn.dataset['body'] ?? '';
  const updateData = (space.statusUpdates ?? []).find((u) => u.id === updateId);
  const attachments = updateData?.attachments ?? [];
  const filesToRemove: string[] = [];

  titleInput.value = title;
  resetUpdateModal();

  if (existingContainer && existingFilesEl) {
    renderExistingUpdateAttachments(attachments, existingContainer, existingFilesEl, filesToRemove);
  }

  openDialogWithBackdrop(modal);

  setTimeout(() => {
    initCKEditor();
    if (editorInstance) editorInstance.setData(body);
  }, 100);

  const freshSubmit = submitBtn.cloneNode(true) as HTMLElement;
  freshSubmit.textContent = 'Speichern';
  submitBtn.replaceWith(freshSubmit);

  freshSubmit.addEventListener('click', () => {
    const newTitle = titleInput.value.trim();
    const newBody = getCkeditorContent();
    if (!newTitle || !newBody) return;

    const formData = buildUpdateFormData(newTitle, newBody, filesToRemove);
    void apiFetch(`${API_BASE}/${space.id}/status-update/${updateId}/edit`, {
      method: 'POST',
      body: formData,
    }).then((res) => {
      if (res.ok) window.location.reload();
      else void handleApiError(res, modal);
    });
  });
}

function initDeleteUpdateButtons(space: SpaceDetail): void {
  const modal = document.getElementById('delete-update-modal') as HTMLDialogElement | null;
  const nameEl = document.getElementById('delete-update-name');
  const confirmBtn = document.getElementById('delete-update-confirm');
  if (!modal || !confirmBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.querySelectorAll<HTMLElement>('[data-delete-update]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const updateId = btn.dataset['deleteUpdate'] ?? '';
      const title = btn.dataset['title'] ?? '';

      if (nameEl) nameEl.textContent = `«${title}»`;

      const freshConfirm = confirmBtn.cloneNode(true) as HTMLElement;
      confirmBtn.replaceWith(freshConfirm);

      freshConfirm.addEventListener('click', () => {
        void apiFetch(`${API_BASE}/${space.id}/status-update/${updateId}`, {
          method: 'DELETE',
        }).then((res) => {
          if (res.ok) window.location.reload();
          else void handleApiError(res);
        });
      });

      modal.showModal();
    });
  });
}

function initStatusButtons(space: SpaceDetail): void {
  const modal = document.getElementById('status-modal') as HTMLDialogElement | null;
  const titleEl = document.getElementById('status-modal-title');
  const messageEl = document.getElementById('status-modal-message');
  const confirmBtn = document.getElementById('status-modal-confirm');
  if (!modal || !confirmBtn) return;

  const actions = getStatusActions(space);

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.querySelectorAll<HTMLElement>('[data-status-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = parseInt(btn.dataset['statusAction'] ?? '0', 10);
      const action = actions[idx] as StatusAction | undefined;
      if (!action) return;

      if (titleEl) titleEl.textContent = action.modalTitle;
      if (messageEl) messageEl.textContent = action.modalMessage;

      const freshConfirm = confirmBtn.cloneNode(true) as HTMLElement;
      freshConfirm.textContent = action.confirmLabel;
      confirmBtn.replaceWith(freshConfirm);

      freshConfirm.addEventListener('click', () => {
        void apiFetch(`${API_BASE}/${space.id}/status`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ status: action.newStatus }),
        }).then((res) => {
          if (res.ok) window.location.reload();
          else void handleApiError(res, modal);
        });
      });

      modal.showModal();
    });
  });
}

function initDeleteSpaceButton(space: SpaceDetail): void {
  const modal = document.getElementById('delete-space-modal') as HTMLDialogElement | null;
  const confirmBtn = document.getElementById('delete-space-confirm');
  if (!modal || !confirmBtn) return;

  modal.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      modal.close();
    });
  });

  document.getElementById('btn-delete-space')?.addEventListener('click', () => {
    modal.showModal();
  });

  confirmBtn.addEventListener('click', () => {
    void apiFetch(`${API_BASE}/${space.id}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
    }).then((res) => {
      if (res.ok) {
        window.location.href = '/innovationen';
      } else {
        void handleApiError(res, modal);
      }
    });
  });
}

function initJoinButton(space: SpaceDetail): void {
  const btn = document.getElementById('btn-join-request') as HTMLButtonElement | null;
  if (!btn) return;

  btn.addEventListener('click', () => {
    void apiFetch(`${API_BASE}/${space.id}/join-request`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    }).then((res) => {
      if (res.ok) {
        btn.textContent = 'Anfrage gesendet ✓';
        btn.classList.add('btn--disabled');
        btn.disabled = true;
        const header = document.querySelector('.projektraum-page__header');
        if (header) {
          header.insertAdjacentHTML(
            'afterend',
            '<div class="projektraum-page__join-notice">' +
              '<p>Deine Anfrage wurde an die Initiatorin gesendet. Du erhältst Zugang, sobald sie bestätigt wurde.</p></div>',
          );
        }
      } else {
        void handleApiError(res);
      }
    });
  });
}

function initJoinRequestActions(spaceId: string): void {
  document.querySelectorAll<HTMLElement>('.join-request-card__btn').forEach((button) => {
    button.addEventListener('click', () => {
      const card = button.closest<HTMLElement>('.join-request-card');
      if (!card) return;
      const requestId = card.dataset['requestId'];
      if (!requestId) return;
      const accept = button.dataset['action'] === 'accept';
      void apiFetch(`${API_BASE}/${spaceId}/join-request/${requestId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accept }),
      }).then((res) => {
        if (res.ok) card.remove();
      });
    });
  });
}

// --- Priority ---

function renderPrioritySection(space: SpaceDetail): void {
  const sidebar = document.querySelector('.project-space-sidebar');
  if (!sidebar) return;

  const current = space.priority ?? 0;
  const sectionEl = document.createElement('div');
  sectionEl.className = 'project-space-sidebar__priority-section';
  sectionEl.innerHTML = `
    <h3 class="project-space-sidebar__section-title">Priorität (Startseite)</h3>
    <div class="form-field" style="margin-bottom: var(--space-3);">
      <label class="project-space-sidebar__label" for="priority-select">Position im Teaser</label>
      <select id="priority-select" class="input">
        <option value="0"${current === 0 ? ' selected' : ''}>Keine Priorität</option>
        <option value="1"${current === 1 ? ' selected' : ''}>Position 1</option>
        <option value="2"${current === 2 ? ' selected' : ''}>Position 2</option>
        <option value="3"${current === 3 ? ' selected' : ''}>Position 3</option>
      </select>
    </div>
    <button class="button button--accent button--sm" id="btn-save-priority">Speichern</button>
    <span id="priority-feedback" class="project-space-sidebar__priority-feedback"></span>`;

  sidebar.appendChild(sectionEl);

  document.getElementById('btn-save-priority')?.addEventListener('click', () => {
    const select = document.getElementById('priority-select') as HTMLSelectElement | null;
    const priority = parseInt(select?.value ?? '0', 10);
    const feedbackEl = document.getElementById('priority-feedback');

    void apiFetch(`${API_BASE}/${space.id}/priority`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ priority }),
    }).then((res) => {
      if (res.ok) {
        space.priority = priority;
        if (feedbackEl) {
          feedbackEl.textContent = 'Gespeichert ✓';
          setTimeout(() => {
            feedbackEl.textContent = '';
          }, 3000);
        }
      } else {
        void handleApiError(res);
      }
    });
  });
}

// --- Main ---

document.addEventListener('DOMContentLoaded', () => {
  const spaceId = getSpaceId();
  if (!spaceId) return;
  void fetchSpace(spaceId).then((space) => {
    renderHero(space);
    renderHeader(space);
    renderCollaboards(space);
    renderIdeaAttachments(space);
    renderUpdates(space);
    renderSidebar(space);
  });
});
