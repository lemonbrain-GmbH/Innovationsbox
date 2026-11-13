import { type ProjectSpacesResponse, renderCard } from '../../../../../../resources/js/utils/project-card-renderer';

const API_BASE = '/api/project-space';
const LIMIT = 3;

async function loadTeaser(): Promise<void> {
  const grid = document.querySelector<HTMLElement>('[data-innovation-teaser-grid]');
  if (!grid) return;

  try {
    const params = new URLSearchParams({
      page: '1',
      limit: String(LIMIT),
      status: 'active',
    });
    const response = await fetch(`${API_BASE}?${params}`);
    const result = (await response.json()) as ProjectSpacesResponse;

    if (result.data.length === 0) {
      grid.innerHTML = '';
      return;
    }

    grid.innerHTML = result.data.map(renderCard).join('');
  } catch {
    grid.innerHTML = '';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  void loadTeaser();
});
