function initUserMenus(): void {
  document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    const trigger = menu.querySelector('[data-user-menu-trigger]') as HTMLButtonElement | null;
    const dropdown = menu.querySelector('.user-menu__dropdown') as HTMLElement | null;
    if (!trigger || !dropdown) return;

    // Remove the hidden attribute so CSS controls visibility
    dropdown.removeAttribute('hidden');

    const menuItems = dropdown.querySelectorAll<HTMLAnchorElement>('.user-menu__item');

    function open() {
      menu.classList.add('user-menu--open');
      trigger!.setAttribute('aria-expanded', 'true');
      menuItems[0]?.focus();
    }

    function close() {
      menu.classList.remove('user-menu--open');
      trigger!.setAttribute('aria-expanded', 'false');
    }

    function isOpen(): boolean {
      return menu.classList.contains('user-menu--open');
    }

    // Click toggle
    trigger.addEventListener('click', () => {
      isOpen() ? close() : open();
    });

    // Close on Escape
    menu.addEventListener('keydown', (e: Event) => {
      const keyEvent = e as KeyboardEvent;
      if (keyEvent.key === 'Escape' && isOpen()) {
        close();
        trigger!.focus();
      }
    });

    // Close when clicking outside
    document.addEventListener('click', (e: Event) => {
      if (isOpen() && !menu.contains(e.target as Node)) {
        close();
      }
    });

    // Keyboard navigation
    dropdown.addEventListener('keydown', (e: Event) => {
      const keyEvent = e as KeyboardEvent;
      const items = Array.from(menuItems);
      const current = document.activeElement as HTMLElement;
      const index = items.indexOf(current as HTMLAnchorElement);

      if (keyEvent.key === 'ArrowDown') {
        keyEvent.preventDefault();
        const next = items[(index + 1) % items.length];
        next?.focus();
      } else if (keyEvent.key === 'ArrowUp') {
        keyEvent.preventDefault();
        const prev = items[(index - 1 + items.length) % items.length];
        prev?.focus();
      }
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initUserMenus);
} else {
  initUserMenus();
}
