import { escapeHtml } from '../utils/escape-html';
import { type ProjectSpacesResponse, renderCard } from '../utils/project-card-renderer';

export {};

const API_BASE = '/api/project-space';
const LIMIT = 12;

let currentPage = 1;
let currentStatus = '';
let currentCategory = '';
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let currentSearch = '';
const currentMine = new URLSearchParams(window.location.search).get('mine') === 'true';

async function fetchSpaces(
  status: string,
  page: number,
  category: string,
  search: string,
): Promise<ProjectSpacesResponse> {
  const params = new URLSearchParams({ page: String(page), limit: String(LIMIT) });
  if (status) params.set('status', status);
  if (category) params.set('category', category);
  if (search) params.set('search', search);
  if (currentMine) params.set('mine', 'true');
  const response = await fetch(`${API_BASE}?${params}`);
  return response.json() as Promise<ProjectSpacesResponse>;
}

async function loadSpaces(): Promise<void> {
  const grid = document.getElementById('project-spaces-grid');
  if (!grid) return;

  grid.innerHTML = '<div class="innovationen-page__loading"><div class="spinner"></div></div>';

  const result = await fetchSpaces(currentStatus, currentPage, currentCategory, currentSearch);

  // Show archived filter for admins
  if (result.isAdmin) {
    const archivedBtn = document.getElementById('filter-archived');
    if (archivedBtn) archivedBtn.hidden = false;
  }

  if (result.data.length === 0) {
    grid.innerHTML = '<p class="innovationen-page__empty">Keine Innovationen gefunden.</p>';
    return;
  }

  grid.innerHTML = result.data.map(renderCard).join('');
}

function initFilters(): void {
  const filterContainer = document.getElementById('project-filters');
  if (!filterContainer) return;

  filterContainer.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const btn = target.closest<HTMLElement>('[data-value]');
    if (!btn) return;

    currentStatus = btn.dataset['value'] ?? '';
    currentPage = 1;

    filterContainer.querySelectorAll('[data-value]').forEach((el) => {
      el.classList.remove('innovationen-page__filter--active');
    });
    btn.classList.add('innovationen-page__filter--active');

    void loadSpaces();
  });
}

interface CategoryData {
  id: string;
  name: string;
  slug: string;
}

async function fetchCategories(): Promise<CategoryData[]> {
  const response = await fetch(`${API_BASE}/categories`);
  const json = (await response.json()) as { data: CategoryData[] };
  return json.data;
}

async function initCategoryFilters(): Promise<void> {
  const container = document.getElementById('category-filters');
  if (!container) return;

  const categories = await fetchCategories();
  const buttons = categories
    .map(
      (c) =>
        `<button class="unstyled-button innovationen-page__filter" data-category="${escapeHtml(c.slug)}" type="button">${escapeHtml(c.name)}</button>`,
    )
    .join('');
  container.insertAdjacentHTML('beforeend', buttons);

  container.addEventListener('click', (e) => {
    const btn = (e.target as HTMLElement).closest<HTMLElement>('[data-category]');
    if (!btn) return;

    currentCategory = btn.dataset['category'] ?? '';
    currentPage = 1;
    container.querySelectorAll('[data-category]').forEach((el) => {
      el.classList.remove('innovationen-page__filter--active');
    });
    btn.classList.add('innovationen-page__filter--active');
    void loadSpaces();
  });
}

function initSearch(): void {
  const input = document.getElementById('project-search') as HTMLInputElement | null;
  if (!input) return;

  input.addEventListener('input', () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      currentSearch = input.value.trim();
      currentPage = 1;
      void loadSpaces();
    }, 300);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initSearch();
  void initCategoryFilters();
  void loadSpaces();
});
