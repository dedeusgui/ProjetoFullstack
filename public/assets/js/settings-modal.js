document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('settingsModalOverlay');
  const themeToggle = document.querySelector('[data-theme-toggle]');
  const themeInput = document.getElementById('settingsThemeInput');
  const primaryColorInput = document.querySelector('[data-primary-color-input]');
  const accentColorInput = document.querySelector('[data-accent-color-input]');
  const textScaleInput = document.querySelector('[data-text-scale-input]');
  const textScaleValue = document.getElementById('settingsTextScaleValue');
  const textScaleDelta = document.getElementById('settingsTextScaleDelta');
  const resetAppearanceButton = document.querySelector('[data-reset-appearance]');
  const DEFAULT_THEME = 'light';
  const DEFAULT_PRIMARY_COLOR = '#4a74ff';
  const DEFAULT_PRIMARY_HOVER_COLOR = '#3d63e6';
  const DEFAULT_ACCENT_COLOR = '#59d186';
  const DEFAULT_TEXT_SCALE = 1.0;
  const root = document.documentElement;

  const applyTheme = (theme) => {
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
      if (themeToggle) themeToggle.checked = true;
    } else {
      root.removeAttribute('data-theme');
      if (themeToggle) themeToggle.checked = false;
    }

    if (themeInput) {
      themeInput.value = theme;
    }

    window.dispatchEvent(new CustomEvent('doitly:theme-change', { detail: { theme } }));
  };

  const applyVisualPreferences = ({ primaryColor, accentColor, textScale }) => {
    if (primaryColor) {
      root.style.setProperty('--accent-blue', primaryColor);
      root.style.setProperty('--accent-blue-hover', DEFAULT_PRIMARY_HOVER_COLOR);
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
      if (textScaleDelta) {
        const delta = Math.round((numericScale - 1) * 100);
        if (delta > 0) {
          textScaleDelta.textContent = `(+${delta}% maior)`;
        } else if (delta < 0) {
          textScaleDelta.textContent = `(${Math.abs(delta)}% menor)`;
        } else {
          textScaleDelta.textContent = '(padrão)';
        }
      }
    }
  };

  const initialTheme = overlay?.dataset.theme === 'dark' ? 'dark' : 'light';
  applyTheme(initialTheme);
  applyVisualPreferences({
    primaryColor: overlay?.dataset.primaryColor,
    accentColor: overlay?.dataset.accentColor,
    textScale: overlay?.dataset.textScale || '1.00'
  });

  themeToggle?.addEventListener('change', (event) => {
    const theme = event.target.checked ? 'dark' : 'light';
    applyTheme(theme);
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

  resetAppearanceButton?.addEventListener('click', () => {
    if (themeToggle) {
      themeToggle.checked = false;
    }
    if (themeInput) {
      themeInput.value = DEFAULT_THEME;
    }
    if (primaryColorInput) {
      primaryColorInput.value = DEFAULT_PRIMARY_COLOR;
    }
    if (accentColorInput) {
      accentColorInput.value = DEFAULT_ACCENT_COLOR;
    }
    if (textScaleInput) {
      textScaleInput.value = DEFAULT_TEXT_SCALE.toFixed(2);
    }

    applyTheme(DEFAULT_THEME);
    applyVisualPreferences({
      primaryColor: DEFAULT_PRIMARY_COLOR,
      accentColor: DEFAULT_ACCENT_COLOR,
      textScale: DEFAULT_TEXT_SCALE
    });
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
