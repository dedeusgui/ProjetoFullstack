document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('settingsModalOverlay');
  if (!overlay) return;

  const themeToggle = document.querySelector('[data-theme-toggle]');
  const themeInput = document.getElementById('settingsThemeInput');
  const primaryColorInput = document.querySelector('[data-primary-color-input]');
  const accentColorInput = document.querySelector('[data-accent-color-input]');
  const textScaleInput = document.querySelector('[data-text-scale-input]');
  const textScaleValue = document.getElementById('settingsTextScaleValue');
  const settingsForm = document.getElementById('settingsForm');
  const resetAppearanceForm = document.getElementById('resetAppearanceForm');
  const THEME_KEY = 'doitly-theme';
  const root = document.documentElement;
  let isSubmitting = false;

  const normalizeTheme = (theme) => (theme === 'dark' ? 'dark' : 'light');

  const getPersistedAppearance = () => ({
    theme: normalizeTheme(overlay.dataset.theme),
    primaryColor: overlay.dataset.primaryColor || primaryColorInput?.defaultValue || '#4a74ff',
    accentColor: overlay.dataset.accentColor || accentColorInput?.defaultValue || '#59d186',
    textScale: overlay.dataset.textScale || textScaleInput?.defaultValue || '1.00'
  });

  const persistedAppearance = getPersistedAppearance();

  const applyTheme = (theme) => {
    const normalizedTheme = normalizeTheme(theme);

    if (normalizedTheme === 'dark') {
      root.setAttribute('data-theme', 'dark');
      if (themeToggle) themeToggle.checked = true;
    } else {
      root.removeAttribute('data-theme');
      if (themeToggle) themeToggle.checked = false;
    }

    if (themeInput) {
      themeInput.value = normalizedTheme;
    }

    window.dispatchEvent(new CustomEvent('doitly:theme-change', { detail: { theme: normalizedTheme } }));
  };

  const applyVisualPreferences = ({ primaryColor, accentColor, textScale }) => {
    if (primaryColor) {
      root.style.setProperty('--accent-blue', primaryColor);
      root.style.setProperty('--accent-blue-hover', '#3d63e6');
    }

    if (accentColor) {
      root.style.setProperty('--accent-green', accentColor);
    }

    if (textScale) {
      const numericScale = Number(textScale);
      if (Number.isNaN(numericScale)) return;
      root.style.fontSize = `${Math.round(numericScale * 100)}%`;
      if (textScaleValue) {
        textScaleValue.textContent = `${Math.round(numericScale * 100)}%`;
      }
    }
  };

  const applyAppearance = ({ theme, primaryColor, accentColor, textScale }) => {
    applyTheme(theme);
    applyVisualPreferences({ primaryColor, accentColor, textScale });
  };

  const syncThemeStorage = (theme) => {
    localStorage.setItem(THEME_KEY, normalizeTheme(theme));
  };

  const restorePersistedAppearance = () => {
    if (settingsForm) {
      settingsForm.reset();
    }

    if (primaryColorInput) {
      primaryColorInput.value = persistedAppearance.primaryColor;
    }

    if (accentColorInput) {
      accentColorInput.value = persistedAppearance.accentColor;
    }

    if (textScaleInput) {
      textScaleInput.value = persistedAppearance.textScale;
    }

    applyAppearance(persistedAppearance);
    syncThemeStorage(persistedAppearance.theme);
  };

  restorePersistedAppearance();

  themeToggle?.addEventListener('change', (event) => {
    const theme = event.target.checked ? 'dark' : 'light';
    applyTheme(theme);
  });

  textScaleInput?.addEventListener('change', (event) => {
    applyVisualPreferences({ textScale: event.target.value });
  });

  primaryColorInput?.addEventListener('input', (event) => {
    applyVisualPreferences({ primaryColor: event.target.value });
  });

  accentColorInput?.addEventListener('input', (event) => {
    applyVisualPreferences({ accentColor: event.target.value });
  });

  textScaleInput?.addEventListener('input', (event) => {
    applyVisualPreferences({ textScale: event.target.value });
  });

  const openButtons = document.querySelectorAll('[data-open-settings-modal]');
  const closeButtons = document.querySelectorAll('[data-close-settings-modal]');
  const avatarInput = overlay.querySelector('[data-avatar-input]');
  const avatarPreview = document.getElementById('settingsAvatarPreview');
  const initialAvatarPreviewHtml = avatarPreview?.innerHTML || '';
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
    isSubmitting = false;
    restorePersistedAppearance();
    if (avatarPreview) {
      avatarPreview.innerHTML = initialAvatarPreviewHtml;
      syncPreviewSize();
    }
    overlay.style.display = 'block';
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!isSubmitting) {
      restorePersistedAppearance();
      if (avatarPreview) {
        avatarPreview.innerHTML = initialAvatarPreviewHtml;
        syncPreviewSize();
      }
    }

    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    isSubmitting = false;
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

  settingsForm?.addEventListener('submit', () => {
    isSubmitting = true;
    syncThemeStorage(themeInput?.value || (themeToggle?.checked ? 'dark' : 'light'));
  });

  resetAppearanceForm?.addEventListener('submit', () => {
    isSubmitting = true;
    syncThemeStorage('light');
  });

  syncPreviewSize();
  window.addEventListener('resize', syncPreviewSize);

  avatarPreview?.addEventListener('error', (event) => {
    if (event.target.tagName === 'IMG') {
      avatarPreview.innerHTML = initials;
    }
  }, true);
});
