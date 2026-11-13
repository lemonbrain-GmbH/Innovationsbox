import { escapeHtml } from '../../../../../../resources/js/utils/escape-html';

interface StatusUpdateTeaser {
  updateId: string;
  title: string;
  bodyPreview: string;
  createdAt: string;
  spaceId: string;
  spaceTitle: string;
}

interface LatestUpdatesResponse {
  data: StatusUpdateTeaser[];
}

function formatDateTime(isoDate: string): string {
  try {
    const date = new Date(isoDate);
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const h = date.getHours();
    const m = date.getMinutes();
    const hours = h < 10 ? '0' + String(h) : String(h);
    const minutes = m < 10 ? '0' + String(m) : String(m);
    return `${String(day)}.${String(month)}.${String(year)}, ${hours}.${minutes} Uhr`;
  } catch {
    return isoDate;
  }
}

function renderUpdateEntry(update: StatusUpdateTeaser): string {
  const href = `/projektraum/view/${update.spaceId}`;

  return `
    <article class="update-teaser__entry">
      <div class="update-teaser__meta">
        ${escapeHtml(update.spaceTitle)} | ${formatDateTime(update.createdAt)}
      </div>
      <div class="update-teaser__content">
        <div class="update-teaser__text">
          <h3 class="update-teaser__entry-title">${escapeHtml(update.title)}</h3>
          <p class="update-teaser__preview">${escapeHtml(update.bodyPreview)}</p>
        </div>
        <a href="${href}" class="update-teaser__link button button--accent button--lg">Zur Innovation</a>
      </div>
    </article>
    <hr class="update-teaser__divider">`;
}

async function loadUpdates(): Promise<void> {
  const list = document.querySelector<HTMLElement>('[data-update-teaser-list]');
  if (!list) return;

  try {
    const response = await fetch('/api/project-space/latest-updates?limit=2');
    const result = (await response.json()) as LatestUpdatesResponse;

    if (result.data.length === 0) {
      list.innerHTML = '';
      return;
    }

    list.innerHTML = result.data.map((update) => renderUpdateEntry(update)).join('');
  } catch {
    list.innerHTML = '';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  void loadUpdates();
});
