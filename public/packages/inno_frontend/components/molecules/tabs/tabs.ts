export function initTabs(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>('[data-tabs]').forEach((wrapper) => {
    if (wrapper.dataset.tabsInit === 'true') return;
    wrapper.dataset.tabsInit = 'true';

    const tabs = Array.from(wrapper.querySelectorAll<HTMLButtonElement>('[role="tab"]'));
    const panels = Array.from(wrapper.querySelectorAll<HTMLElement>('[role="tabpanel"]'));

    const activate = (index: number, focus = true) => {
      tabs.forEach((tab, i) => {
        const selected = i === index;
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.setAttribute('tabindex', selected ? '0' : '-1');
        tab.classList.toggle('tabs__tab--active', selected);
        panels[i].hidden = !selected;
      });
      if (focus) tabs[index].focus();
    };

    const enabled = (i: number) => !tabs[i].hasAttribute('disabled');

    const nextEnabled = (from: number, step: number): number => {
      const n = tabs.length;
      for (let k = 1; k <= n; k++) {
        const i = (((from + step * k) % n) + n) % n;
        if (enabled(i)) return i;
      }
      return from;
    };

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => enabled(i) && activate(i));
      tab.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          activate(nextEnabled(i, 1));
        } else if (e.key === 'ArrowLeft') {
          e.preventDefault();
          activate(nextEnabled(i, -1));
        } else if (e.key === 'Home') {
          e.preventDefault();
          activate(nextEnabled(-1, 1));
        } else if (e.key === 'End') {
          e.preventDefault();
          activate(nextEnabled(tabs.length, -1));
        }
      });
    });
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState !== 'loading') initTabs();
  else document.addEventListener('DOMContentLoaded', () => initTabs());
}
