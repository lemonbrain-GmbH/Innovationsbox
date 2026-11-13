export function initDropdowns(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>('[data-dropdown]').forEach((dropdown) => {
    if (dropdown.dataset.dropdownInit === 'true') return;
    dropdown.dataset.dropdownInit = 'true';

    const trigger = dropdown.querySelector<HTMLButtonElement>('[data-dropdown-trigger]');
    const menu = dropdown.querySelector<HTMLElement>('.dropdown__menu');
    if (!trigger || !menu) return;

    const items = () => Array.from(menu.querySelectorAll<HTMLElement>('[role="menuitem"]:not([aria-disabled="true"])'));

    const open = () => {
      menu.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      const first = items()[0];
      first?.focus();
      document.addEventListener('click', onOutside);
    };

    const close = (restoreFocus = true) => {
      menu.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', onOutside);
      if (restoreFocus) trigger.focus();
    };

    const onOutside = (e: MouseEvent) => {
      if (!dropdown.contains(e.target as Node)) close(false);
    };

    trigger.addEventListener('click', () => {
      trigger.getAttribute('aria-expanded') === 'true' ? close() : open();
    });

    dropdown.addEventListener('keydown', (e: Event) => {
      const ke = e as KeyboardEvent;
      const list = items();
      const idx = list.indexOf(document.activeElement as HTMLElement);
      if (ke.key === 'Escape') {
        e.preventDefault();
        close();
      } else if (ke.key === 'ArrowDown') {
        e.preventDefault();
        if (menu.hidden) open();
        else list[(idx + 1) % list.length]?.focus();
      } else if (ke.key === 'ArrowUp') {
        e.preventDefault();
        list[(idx - 1 + list.length) % list.length]?.focus();
      } else if (ke.key === 'Home') {
        e.preventDefault();
        list[0]?.focus();
      } else if (ke.key === 'End') {
        e.preventDefault();
        list[list.length - 1]?.focus();
      }
    });
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState !== 'loading') initDropdowns();
  else document.addEventListener('DOMContentLoaded', () => initDropdowns());
}
