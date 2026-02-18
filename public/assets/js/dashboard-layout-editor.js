(function () {
  const config = window.DASHBOARD_LAYOUT_CONFIG || null;
  if (!config || config.pageKey !== 'dashboard') return;

  const statsContainer = document.querySelector('[data-layout-group="stats"]');
  const widgetsContainer = document.querySelector('[data-layout-group="widgets"]');
  if (!statsContainer || !widgetsContainer) return;

  const STORAGE_KEY = `doitly-layout-${config.pageKey}`;
  const sortables = [];
  let editMode = false;

  const groups = {
    stats: {
      container: statsContainer,
      selector: ':scope > [data-widget-id]'
    },
    widgets: {
      container: widgetsContainer,
      selector: ':scope > [data-widget-id]'
    }
  };

  const getOrder = (groupName) => {
    const group = groups[groupName];
    return Array.from(group.container.querySelectorAll(group.selector))
      .map((item) => item.dataset.widgetId)
      .filter(Boolean);
  };

  const applyOrder = (groupName, order) => {
    if (!Array.isArray(order) || !order.length) return;

    const group = groups[groupName];
    const items = Array.from(group.container.querySelectorAll(group.selector));
    const map = new Map(items.map((item) => [item.dataset.widgetId, item]));
    const uniqueOrder = [...new Set(order)];
    const fragment = document.createDocumentFragment();

    uniqueOrder.forEach((widgetId) => {
      const item = map.get(widgetId);
      if (item) fragment.appendChild(item);
    });

    items.forEach((item) => {
      if (!fragment.contains(item)) fragment.appendChild(item);
    });

    group.container.appendChild(fragment);
  };

  const getLayoutPayload = () => ({
    stats: getOrder('stats'),
    widgets: getOrder('widgets')
  });

  const persistLocal = (layout) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(layout));
  };

  const loadLocal = () => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (_) {
      return null;
    }
  };

  const showMessage = (text, type) => {
    feedback.textContent = text;
    feedback.classList.remove('is-error', 'is-success');
    feedback.classList.add(type === 'error' ? 'is-error' : 'is-success');
    feedback.classList.add('is-visible');

    clearTimeout(showMessage.timer);
    showMessage.timer = setTimeout(() => {
      feedback.classList.remove('is-visible');
    }, 2600);
  };

  const createHandles = () => {
    Object.values(groups).forEach((group) => {
      group.container.querySelectorAll(group.selector).forEach((item) => {
        if (item.querySelector('.dashboard-layout-handle')) return;

        const handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'dashboard-layout-handle';
        handle.setAttribute('aria-label', 'Arrastar bloco');
        handle.innerHTML = '<i class="bi bi-grip-vertical"></i>';
        item.prepend(handle);
      });
    });
  };

  const buildSortable = () => {
    if (!window.Sortable) {
      showMessage('Não foi possível carregar o drag and drop.', 'error');
      return;
    }

    Object.entries(groups).forEach(([_, group]) => {
      const sortable = new window.Sortable(group.container, {
        animation: 180,
        draggable: group.selector,
        handle: '.dashboard-layout-handle',
        ghostClass: 'dashboard-layout-ghost',
        chosenClass: 'dashboard-layout-chosen',
        dragClass: 'dashboard-layout-drag',
        delay: 70,
        delayOnTouchOnly: true,
        onEnd: () => persistLocal(getLayoutPayload())
      });

      sortables.push(sortable);
    });
  };

  const destroySortable = () => {
    while (sortables.length) {
      const sortable = sortables.pop();
      sortable.destroy();
    }
  };

  const setEditMode = (enabled) => {
    editMode = enabled;
    document.body.classList.toggle('dashboard-layout-edit-mode', editMode);
    saveButton.disabled = !editMode;

    toggleButton.innerHTML = editMode
      ? '<i class="bi bi-check2-circle"></i><span>Concluir edição</span>'
      : '<i class="bi bi-grid-3x3-gap"></i><span>Personalizar layout</span>';

    if (editMode) {
      buildSortable();
    } else {
      destroySortable();
    }
  };

  const saveToServer = async (layout) => {
    const response = await fetch(config.saveEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save',
        page_key: config.pageKey,
        layout
      })
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Falha ao salvar layout personalizado.');
    }
  };

  const resetOnServer = async () => {
    const response = await fetch(config.saveEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'reset',
        page_key: config.pageKey
      })
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Falha ao resetar layout.');
    }
  };

  const blockClickableDuringEdit = (event) => {
    if (!editMode) return;

    const clickable = event.target.closest('a,button');
    if (!clickable) return;

    if (clickable.closest('.dashboard-layout-toolbar')) return;
    if (clickable.classList.contains('dashboard-layout-handle')) return;

    event.preventDefault();
  };

  const toolbar = document.createElement('div');
  toolbar.className = 'dashboard-layout-toolbar';
  toolbar.innerHTML = `
    <button type="button" class="doitly-btn doitly-btn-sm" data-layout-action="toggle">
      <i class="bi bi-grid-3x3-gap"></i>
      <span>Personalizar layout</span>
    </button>
    <button type="button" class="doitly-btn doitly-btn-sm" data-layout-action="save" disabled>
      <i class="bi bi-save"></i>
      <span>Salvar</span>
    </button>
    <button type="button" class="doitly-btn doitly-btn-secondary doitly-btn-sm" data-layout-action="reset">
      <i class="bi bi-arrow-counterclockwise"></i>
      <span>Padrão</span>
    </button>
    <span class="dashboard-layout-feedback" aria-live="polite"></span>
  `;

  const toggleButton = toolbar.querySelector('[data-layout-action="toggle"]');
  const saveButton = toolbar.querySelector('[data-layout-action="save"]');
  const resetButton = toolbar.querySelector('[data-layout-action="reset"]');
  const feedback = toolbar.querySelector('.dashboard-layout-feedback');

  document.body.appendChild(toolbar);
  createHandles();

  const initialLayout = loadLocal() || config.initialLayout || {};
  applyOrder('stats', initialLayout.stats || []);
  applyOrder('widgets', initialLayout.widgets || []);

  document.addEventListener('click', blockClickableDuringEdit, true);

  toggleButton.addEventListener('click', () => {
    setEditMode(!editMode);
  });

  saveButton.addEventListener('click', async () => {
    const layout = getLayoutPayload();
    persistLocal(layout);

    try {
      await saveToServer(layout);
      showMessage('Layout salvo com sucesso.', 'success');
    } catch (error) {
      showMessage(error.message, 'error');
    }
  });

  resetButton.addEventListener('click', async () => {
    const defaults = {
      stats: Array.from(statsContainer.querySelectorAll('[data-widget-id]')).map((item) => item.dataset.widgetId),
      widgets: Array.from(widgetsContainer.querySelectorAll('[data-widget-id]')).map((item) => item.dataset.widgetId)
    };

    applyOrder('stats', defaults.stats);
    applyOrder('widgets', defaults.widgets);
    localStorage.removeItem(STORAGE_KEY);

    try {
      await resetOnServer();
      showMessage('Layout restaurado para o padrão.', 'success');
    } catch (error) {
      showMessage(error.message, 'error');
    }
  });
})();
