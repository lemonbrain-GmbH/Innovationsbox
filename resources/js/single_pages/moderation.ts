import { escapeHtml } from '../utils/escape-html';

export {};

const API_BASE = '/api/moderation';

interface ReviewSummary {
  id: string;
  ideaId: string;
  ideaTitle: string;
  submitterName: string;
  category: string;
  createdAt: string;
}

function formatDateCH(isoDate: string): string {
  try {
    return new Date(isoDate).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch {
    return isoDate;
  }
}

async function fetchReviews(): Promise<ReviewSummary[]> {
  const res = await fetch(API_BASE);
  const json = (await res.json()) as { data: ReviewSummary[] };
  return json.data;
}

function renderReviews(reviews: ReviewSummary[]): void {
  const container = document.getElementById('reviews-container');
  if (!container) return;

  if (reviews.length === 0) {
    container.innerHTML = `<div class="moderation-page__empty"><p>Keine offenen Reviews vorhanden.</p></div>`;
    return;
  }

  container.innerHTML = `
    <div class="moderation-list">
      <div class="moderation-list__header">
        <span class="moderation-list__col moderation-list__col--title">Titel</span>
        <span class="moderation-list__col moderation-list__col--submitter">Eingereicht von</span>
        <span class="moderation-list__col moderation-list__col--category">Kategorie</span>
        <span class="moderation-list__col moderation-list__col--date">Datum</span>
      </div>
      ${reviews
        .map(
          (r) => `
        <a href="/moderation/detail?id=${encodeURIComponent(r.id)}" class="moderation-list__item">
          <span class="moderation-list__col moderation-list__col--title">${escapeHtml(r.ideaTitle)}</span>
          <span class="moderation-list__col moderation-list__col--submitter">${escapeHtml(r.submitterName)}</span>
          <span class="moderation-list__col moderation-list__col--category">${escapeHtml(r.category)}</span>
          <span class="moderation-list__col moderation-list__col--date">${formatDateCH(r.createdAt)}</span>
        </a>`,
        )
        .join('')}
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('reviews-container')) return;
  void fetchReviews()
    .then(renderReviews)
    .catch(() => {
      const container = document.getElementById('reviews-container');
      if (container) container.innerHTML = '<p class="alert alert--error">Fehler beim Laden der Reviews.</p>';
    });
});
