// Alert dismiss behavior
document.addEventListener('click', (event) => {
  const target = event.target as HTMLElement;
  const dismissButton = target.closest<HTMLElement>('[data-alert-dismiss]');
  if (!dismissButton) return;
  const alert = dismissButton.closest<HTMLElement>('.alert');
  alert?.remove();
});
