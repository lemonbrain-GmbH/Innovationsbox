import { escapeHtml } from './escape-html';

export interface ProjectSpaceCard {
  id: string;
  title: string;
  description: string;
  status: string;
  initiatorId: string;
  initiatorName: string;
  initiatorAvatarUrl: string;
  memberCount: number;
  categoryId: string;
  categoryName: string;
  categorySlug: string;
  latestUpdate: { title: string; createdAt: string } | null;
  createdAt: string;
  userRole: string | null;
  headerImageUrl: string;
}

export interface ProjectSpacesResponse {
  data: ProjectSpaceCard[];
  total: number;
  isAdmin: boolean;
}

export function formatDate(isoDate: string): string {
  try {
    const date = new Date(isoDate);
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear() % 100;
    return `${String(day)}.${String(month)}.${String(year)}`;
  } catch {
    return isoDate;
  }
}

export function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Aktiv',
    completed: 'Abgeschlossen',
    archived: 'Archiviert',
  };
  return labels[status] ?? status;
}

export function getStatusVariant(status: string): string {
  if (status === 'active') return 'success';
  if (status === 'archived') return 'warning';
  return 'neutral';
}

export function getInitials(name: string): string {
  return name
    .split(' ')
    .map((w) => w.charAt(0))
    .join('')
    .substring(0, 2)
    .toUpperCase();
}

export function getActionButton(space: ProjectSpaceCard): string {
  const href = `/projektraum/view/${space.id}`;
  const role = space.userRole;

  if (space.status === 'active') {
    if (role === 'initiator' || role === 'admin') {
      return `<a href="${href}" class="project-card__btn button button--accent button--lg">Verwalten</a>`;
    }
    if (role === 'member') {
      return `<a href="${href}" class="project-card__btn button button--muted button--lg">Eintreten</a>`;
    }
    return `<a href="${href}" class="project-card__btn button button--accent button--lg">Mitmachen</a>`;
  }

  return `<a href="${href}" class="project-card__btn button button--muted button--lg">Mehr erfahren</a>`;
}

export function renderCard(space: ProjectSpaceCard): string {
  const isActive = space.status === 'active';
  const statusLabel = getStatusLabel(space.status);

  return `
    <article class="project-card project-card--${space.status}">
      <div class="project-card__image">${space.headerImageUrl ? `<img src="${escapeHtml(space.headerImageUrl)}" alt="" loading="lazy">` : ''}</div>
      <div class="project-card__body">
        <div class="project-card__header">
          ${space.categoryName ? `<span class="project-card__badge project-card__badge--category">${escapeHtml(space.categoryName)}</span>` : ''}
          <span class="project-card__badge project-card__badge--${getStatusVariant(space.status)}">${statusLabel}</span>
        </div>
        <h3 class="project-card__title">${escapeHtml(space.title)}</h3>
        <div class="project-card__meta">
          <div class="project-card__meta-item">
            <svg class="project-card__meta-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <span><strong>${String(space.memberCount)}</strong> Mitglieder</span>
          </div>
          <span class="project-card__age">Erstellt: ${formatDate(space.createdAt)}</span>
        </div>
        <div class="project-card__footer">
          <div class="project-card__initiator">
            ${space.initiatorAvatarUrl ? `<img class="project-card__avatar-img" src="${escapeHtml(space.initiatorAvatarUrl)}" alt="${escapeHtml(space.initiatorName)}">` : `<div class="project-card__avatar${isActive ? '' : ' project-card__avatar--muted'}">${escapeHtml(getInitials(space.initiatorName))}</div>`}
            <div class="project-card__initiator-info">
              <span class="project-card__initiator-name">${escapeHtml(space.initiatorName)}</span>
              <span class="project-card__initiator-role">Initiator·in</span>
            </div>
          </div>
          ${getActionButton(space)}
        </div>
      </div>
    </article>`;
}
