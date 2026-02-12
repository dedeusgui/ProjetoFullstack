document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('settingsModalOverlay');
  const themeToggle = document.querySelector('[data-theme-toggle]');
  const root = document.documentElement;
  const THEME_KEY = 'doitly-theme';

  const applyTheme = (theme) => {
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
      themeToggle && (themeToggle.checked = true);
    } else {
      root.removeAttribute('data-theme');
      themeToggle && (themeToggle.checked = false);
    }
    window.dispatchEvent(new CustomEvent('doitly:theme-change', { detail: { theme } }));
  };

  const getPreferredTheme = () => {
    const persisted = localStorage.getItem(THEME_KEY);
    if (persisted === 'dark' || persisted === 'light') return persisted;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  };

  applyTheme(getPreferredTheme());

  themeToggle?.addEventListener('change', (event) => {
    const theme = event.target.checked ? 'dark' : 'light';
    localStorage.setItem(THEME_KEY, theme);
    applyTheme(theme);
  });

  if (!overlay) return;

  const openButtons = document.querySelectorAll('[data-open-settings-modal]');
  const closeButtons = document.querySelectorAll('[data-close-settings-modal]');
  const avatarInput = overlay.querySelector('[data-avatar-input]');
  const avatarPreview = document.getElementById('settingsAvatarPreview');
  const initials = (avatarPreview?.textContent || '').trim();


  const syncPreviewSize = () => {
    if (!avatarPreview) return;

    const sourceAvatar = document.querySelector('.sidebar-user .user-avatar');
    if (!sourceAvatar) return;

    const rect = sourceAvatar.getBoundingClientRect();
    if (rect.width > 0 && rect.height > 0) {
      avatarPreview.style.width = `${Math.round(rect.width)}px`;
      avatarPreview.style.height = `${Math.round(rect.height)}px`;
      avatarPreview.style.minWidth = `${Math.round(rect.width)}px`;
      avatarPreview.style.minHeight = `${Math.round(rect.height)}px`;
      avatarPreview.style.overflow = 'hidden';
    }
  };

  const openModal = () => {
    overlay.style.display = 'block';
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  const updatePreview = (url) => {
    if (!avatarPreview) return;

    const normalized = (url || '').trim();
    if (normalized === '') {
      avatarPreview.innerHTML = initials;
      syncPreviewSize();
      return;
    }

    avatarPreview.innerHTML = `<img src="${normalized}" alt="Pré-visualização do avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />`;
    syncPreviewSize();
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      syncPreviewSize();
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
    if (event.key === 'Escape' && overlay.style.display === 'block') {
      closeModal();
    }
  });

  avatarInput?.addEventListener('input', (event) => {
    updatePreview(event.target.value);
  });

  syncPreviewSize();
  window.addEventListener('resize', syncPreviewSize);

  avatarPreview?.addEventListener('error', (event) => {
    if (event.target.tagName === 'IMG') {
      avatarPreview.innerHTML = initials;
    }
  }, true);
});
