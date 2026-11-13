// Modal open/close behavior using native <dialog> API.
document.addEventListener('click', (event) => {
  const target = event.target as HTMLElement;

  // Open trigger: [data-modal-open="#modal-id"]
  const openTrigger = target.closest<HTMLElement>('[data-modal-open]');
  if (openTrigger) {
    const selector = openTrigger.dataset.modalOpen;
    if (selector) {
      const dialog = document.querySelector<HTMLDialogElement>(selector);
      dialog?.showModal();
      return;
    }
  }

  // Close trigger: button inside modal with [data-modal-close]
  const closeTrigger = target.closest<HTMLElement>('[data-modal-close]');
  if (closeTrigger) {
    const dialog = closeTrigger.closest<HTMLDialogElement>('dialog.modal');
    dialog?.close();
    return;
  }

  // Backdrop click (click landed on the dialog itself, not its content)
  if (target.tagName === 'DIALOG' && target.classList.contains('modal')) {
    const dialog = target as HTMLDialogElement;
    const rect = dialog.getBoundingClientRect();
    const clickedBackdrop =
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom;
    if (clickedBackdrop) {
      dialog.close();
    }
  }
});
