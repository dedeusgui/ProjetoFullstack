(function () {
  const config = window.PAGE_LAYOUT_CONFIG || null;
  if (!config || config.pageKey !== 'landing') {
    return;
  }

  const mainContent = document.querySelector('main.main-content');
  if (!mainContent) {
    return;
  }

  const sections = Array.from(mainContent.querySelectorAll(':scope > section'));
  if (!sections.length) {
    return;
  }

  const STORAGE_KEY = `doitly-layout-${config.pageKey}`;
  const fallbackDelay = 150;
  let editMode = false;
  let sortable = null;

  const getSectionKey = (section, index) => {
    if (section.dataset.blockId) {
      return section.dataset.blockId;
    }

    const sectionClass = Array.from(section.classList).find((className) => className.endsWith('-section'));
    const derivedKey = sectionClass || `section-${index + 1}`;
    section.dataset.blockId = derivedKey;
    section.classList.add('layout-section-item');
    return derivedKey;
  };

  const sectionByKey = new Map();
  sections.forEach((section, index) => {
    sectionByKey.set(getSectionKey(section, index), section);
  });

  const getCurrentOrder = () => {
    return Array.from(mainContent.querySelectorAll(':scope > section')).map((section, index) => getSectionKey(section, index));
  };

  const applyOrder = (order) => {
    if (!Array.isArray(order) || !order.length) {
      return;
    }

    const uniqueOrder = [...new Set(order)];
    const fragment = document.createDocumentFragment();

    uniqueOrder.forEach((key) => {
      const section = sectionByKey.get(key);
      if (section) {
        fragment.appendChild(section);
      }
    });

    sections.forEach((section) => {
      if (!fragment.contains(section)) {
        fragment.appendChild(section);
      }
    });

    mainContent.appendChild(fragment);
    refreshAos();
  };

  const persistLocalOrder = (order) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(order));
  };

  const loadLocalOrder = () => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return [];
      }
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string') : [];
    } catch (_) {
      return [];
    }
  };

  const saveOrderToServer = async (order) => {
    const payload = {
      action: 'save',
      page_key: config.pageKey,
      order
    };

    const response = await fetch(config.saveEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      throw new Error('Não foi possível salvar no servidor.');
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || 'Falha ao salvar layout.');
    }
  };

  const resetOrderOnServer = async () => {
    const payload = {
      action: 'reset',
      page_key: config.pageKey
    };

    const response = await fetch(config.saveEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      throw new Error('Não foi possível resetar no servidor.');
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.message || 'Falha ao resetar layout.');
    }
  };

  const showFeedback = (message, type) => {
    toolbarMessage.textContent = message;
    toolbarMessage.classList.remove('is-error', 'is-success');
    toolbarMessage.classList.add(type === 'error' ? 'is-error' : 'is-success');
    toolbarMessage.classList.add('is-visible');

    clearTimeout(showFeedback.timeoutId);
    showFeedback.timeoutId = setTimeout(() => {
      toolbarMessage.classList.remove('is-visible');
    }, 2400);
  };

  const refreshAos = () => {
    if (window.AOS && typeof window.AOS.refreshHard === 'function') {
      setTimeout(() => window.AOS.refreshHard(), fallbackDelay);
    }
  };

  const toolbar = document.createElement('div');
  toolbar.className = 'layout-editor-toolbar';
  toolbar.innerHTML = `
    <button type="button" class="doitly-btn doitly-btn-sm" data-layout-action="toggle">
      <i class="bi bi-arrows-move"></i>
      <span>Modo de edição</span>
    </button>
    <button type="button" class="doitly-btn doitly-btn-sm" data-layout-action="save" disabled>
      <i class="bi bi-save"></i>
      <span>Salvar layout</span>
    </button>
    <button type="button" class="doitly-btn doitly-btn-secondary doitly-btn-sm" data-layout-action="reset">
      <i class="bi bi-arrow-counterclockwise"></i>
      <span>Voltar ao padrão</span>
    </button>
    <span class="layout-editor-message" aria-live="polite"></span>
  `;

  const [toggleButton, saveButton, resetButton] = toolbar.querySelectorAll('button');
  const toolbarMessage = toolbar.querySelector('.layout-editor-message');
  document.body.appendChild(toolbar);

  const updateEditVisualState = () => {
    document.body.classList.toggle('layout-edit-mode', editMode);
    saveButton.disabled = !editMode;
    toggleButton.innerHTML = editMode
      ? '<i class="bi bi-check2-circle"></i><span>Concluir edição</span>'
      : '<i class="bi bi-arrows-move"></i><span>Modo de edição</span>';

    if (!window.Sortable) {
      showFeedback('Biblioteca de drag and drop não carregada.', 'error');
      return;
    }

    if (editMode && !sortable) {
      sortable = new window.Sortable(mainContent, {
        animation: 220,
        draggable: ':scope > section',
        ghostClass: 'layout-sortable-ghost',
        chosenClass: 'layout-sortable-chosen',
        dragClass: 'layout-sortable-drag',
        handle: '.layout-drag-handle',
        delay: 70,
        delayOnTouchOnly: true,
        onEnd: () => {
          persistLocalOrder(getCurrentOrder());
        }
      });
    }

    if (!editMode && sortable) {
      sortable.destroy();
      sortable = null;
    }
  };

  const buildHandles = () => {
    sections.forEach((section) => {
      const handle = document.createElement('button');
      handle.type = 'button';
      handle.className = 'layout-drag-handle';
      handle.setAttribute('aria-label', 'Arrastar bloco');
      handle.innerHTML = '<i class="bi bi-grip-vertical"></i>';
      section.prepend(handle);
    });
  };

  const setDefaultOrder = () => {
    sections.forEach((section) => {
      mainContent.appendChild(section);
    });
  };

  document.addEventListener('click', (event) => {
    if (!editMode) {
      return;
    }

    const targetLink = event.target.closest('a');
    if (targetLink && mainContent.contains(targetLink)) {
      event.preventDefault();
    }
  });

  toggleButton.addEventListener('click', () => {
    editMode = !editMode;
    updateEditVisualState();
  });

  saveButton.addEventListener('click', async () => {
    const order = getCurrentOrder();
    persistLocalOrder(order);

    if (!config.isLoggedIn) {
      showFeedback('Layout salvo apenas neste navegador. Faça login para sincronizar.', 'success');
      return;
    }

    try {
      await saveOrderToServer(order);
      showFeedback('Layout salvo com sucesso.', 'success');
    } catch (error) {
      showFeedback(error.message, 'error');
    }
  });

  resetButton.addEventListener('click', async () => {
    setDefaultOrder();
    localStorage.removeItem(STORAGE_KEY);

    if (config.isLoggedIn) {
      try {
        await resetOrderOnServer();
      } catch (error) {
        showFeedback(error.message, 'error');
        return;
      }
    }

    showFeedback('Layout restaurado para o padrão.', 'success');
    refreshAos();
  });

  const serverOrder = Array.isArray(config.initialOrder) ? config.initialOrder : [];
  const localOrder = loadLocalOrder();
  const initialOrder = localOrder.length ? localOrder : serverOrder;

  buildHandles();
  applyOrder(initialOrder);
  updateEditVisualState();
})();
