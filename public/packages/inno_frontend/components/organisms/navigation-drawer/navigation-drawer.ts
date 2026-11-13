enum NavSelectors {
  NAV_CONTAINER = '.nav-container',
  NAV_DRAWER = '.nav-drawer',
  NAV_OPENER = '.nav-toggle',
  NAV_CLOSER = '.nav-close',
  NAV_OVERLAY = '.nav-overlay',
}

class NavigationDrawer {
  #opener: HTMLButtonElement | null;
  #closer: HTMLButtonElement | null;
  #drawer: HTMLElement | null;
  #overlay: HTMLElement | null;

  constructor(container: HTMLElement) {
    this.#opener = container.querySelector(NavSelectors.NAV_OPENER);
    this.#closer = container.querySelector(NavSelectors.NAV_CLOSER);
    this.#drawer = container.querySelector(NavSelectors.NAV_DRAWER);
    this.#overlay = container.querySelector(NavSelectors.NAV_OVERLAY);
    this.bindEvents();
  }

  private bindEvents() {
    this.#opener?.addEventListener('click', () => {
      this.open();
    });
    this.#closer?.addEventListener('click', () => {
      this.close();
    });
    this.#overlay?.addEventListener('click', () => {
      this.close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.close();
    });
  }

  private open() {
    this.#drawer?.classList.add('active');
    this.#drawer?.setAttribute('aria-hidden', 'false');
    this.#overlay?.classList.add('active');
    this.#opener?.setAttribute('aria-expanded', 'true');
  }

  private close() {
    this.#drawer?.classList.remove('active');
    this.#drawer?.setAttribute('aria-hidden', 'true');
    this.#overlay?.classList.remove('active');
    this.#opener?.setAttribute('aria-expanded', 'false');
  }
}

function initNavigation() {
  document.querySelectorAll<HTMLElement>(NavSelectors.NAV_CONTAINER).forEach((el) => new NavigationDrawer(el));

  // Move auth buttons from header into nav drawer
  const headerAuth = document.querySelector('[data-header-auth]');
  const navAuth = document.querySelector('[data-nav-auth]');
  if (headerAuth && navAuth) {
    while (headerAuth.firstChild) {
      navAuth.appendChild(headerAuth.firstChild);
    }
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNavigation);
} else {
  initNavigation();
}
