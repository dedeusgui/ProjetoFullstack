document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('settingsModalOverlay');
  if (!overlay) return;

  const openButtons = document.querySelectorAll('[data-open-settings-modal]');
  const closeButtons = document.querySelectorAll('[data-close-settings-modal]');
  const avatarInput = overlay.querySelector('[data-avatar-input]');
  const avatarPreview = document.getElementById('settingsAvatarPreview');
  const initials = (avatarPreview?.textContent || '').trim();

  const openModal = () => {
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('settings-modal-open');
  };

  const closeModal = () => {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('settings-modal-open');
  };

  const updatePreview = (url) => {
    if (!avatarPreview) return;

    const normalized = (url || '').trim();
    if (normalized === '') {
      avatarPreview.innerHTML = initials;
      return;
    }

    avatarPreview.innerHTML = `<img src="${normalized}" alt="Pré-visualização do avatar" />`;
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      openModal();
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
      closeModal();
    }
  });

  avatarInput?.addEventListener('input', (event) => {
    updatePreview(event.target.value);
  });

  avatarPreview?.addEventListener('error', (event) => {
    if (event.target.tagName === 'IMG') {
      avatarPreview.innerHTML = initials;
    }
  }, true);
});
