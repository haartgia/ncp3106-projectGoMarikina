// Modal functionality
const modal = document.getElementById('announcementsModal');

function openAnnouncementsModal(e) {
    if (e) {
        e.preventDefault(); // Prevent any default navigation
    }
    if (modal) {
        document.body.classList.add('modal-open');
        modal.hidden = false;
        setTimeout(() => modal.setAttribute('open', ''), 10);
        return false; // Prevent any default behavior
    }
}

function closeAnnouncementsModal() {
    if (modal) {
        modal.removeAttribute('open');
        document.body.classList.remove('modal-open');
        // Wait for animation to complete before hiding
        setTimeout(() => modal.hidden = true, 300);
    }
}

// Close modal when clicking outside or on close button
if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAnnouncementsModal();
        }
    });

    const closeButton = modal.querySelector('.modal-close');
    if (closeButton) {
        closeButton.addEventListener('click', closeAnnouncementsModal);
    }

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            closeAnnouncementsModal();
        }
    });
}

/* --- Stylized toasts and confirm dialog (global helpers) --- */
(function initGlobalUIHelpers(){
  // Toast container
  let toastContainer = document.querySelector('.gomk-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'gomk-toast-container';
    document.body.appendChild(toastContainer);
  }

  // Confirm overlay
  let confirmOverlay = document.querySelector('.gomk-confirm-overlay');
  if (!confirmOverlay) {
    confirmOverlay = document.createElement('div');
    confirmOverlay.className = 'gomk-confirm-overlay';
    confirmOverlay.innerHTML = `
      <div class="gomk-confirm" role="dialog" aria-modal="true">
        <div class="confirm-body"><p class="confirm-message"></p></div>
        <div class="confirm-actions">
          <button class="btn ghost cancel-btn">Cancel</button>
          <button class="btn danger ok-btn">Delete</button>
        </div>
      </div>`;
    document.body.appendChild(confirmOverlay);
  }

  const showToast = (text, { type = 'info', duration = 3500 } = {}) => {
    const t = document.createElement('div');
    t.className = 'gomk-toast';
    if (type === 'success') t.style.background = 'linear-gradient(180deg,#0b5b27,#0d7a3a)';
    if (type === 'error') t.style.background = 'linear-gradient(180deg,#5b0b0b,#7a0d0d)';
    t.innerHTML = `<div class="toast-icon">${type === 'error' ? '!' : (type === 'success' ? '✓' : 'i')}</div><div class="toast-text">${text}</div>`;
    toastContainer.appendChild(t);
    // show
    requestAnimationFrame(() => t.classList.add('show'));
    const id = setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 260);
    }, duration);
    return { dismiss: () => { clearTimeout(id); t.classList.remove('show'); setTimeout(() => t.remove(), 200); } };
  };

  const confirmDialog = (message, { okText = 'Delete', cancelText = 'Cancel' } = {}) => {
    return new Promise((resolve) => {
      confirmOverlay.querySelector('.confirm-message').textContent = message;
      confirmOverlay.querySelector('.ok-btn').textContent = okText;
      confirmOverlay.querySelector('.cancel-btn').textContent = cancelText;
      confirmOverlay.classList.add('open');

      const cleanup = () => {
        confirmOverlay.classList.remove('open');
        confirmOverlay.querySelector('.ok-btn').removeEventListener('click', onOk);
        confirmOverlay.querySelector('.cancel-btn').removeEventListener('click', onCancel);
      };

      const onOk = () => { cleanup(); resolve(true); };
      const onCancel = () => { cleanup(); resolve(false); };

      confirmOverlay.querySelector('.ok-btn').addEventListener('click', onOk);
      confirmOverlay.querySelector('.cancel-btn').addEventListener('click', onCancel);
    });
  };

  // Expose globally
  window.GOMK = window.GOMK || {};
  window.GOMK.showToast = showToast;
  window.GOMK.confirmDialog = confirmDialog;
})();

// Intercept forms that have data-confirm-message and show stylized confirm dialog
document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement)) return;
  const msg = form.dataset.confirmMessage;
  if (!msg) return;
  e.preventDefault();
  let ok = true;
  if (window.GOMK && window.GOMK.confirmDialog) {
    ok = await window.GOMK.confirmDialog(msg, { okText: 'Yes', cancelText: 'Cancel' });
  } else {
    ok = window.confirm(msg);
  }
  if (ok) form.submit();
});

// Add bottom 'View all activity' bar
// (Removed bottom activity bar per user request)

// Global background data service: polls sensor data and shares history across pages
(function(){
  if (window.GoMKData) return; // singleton
  const STORAGE_KEY = 'gomk.history.v1';
  const SELECT_KEY = 'gomk.selectedBarangay';
  const MAX_POINTS = 720; // same as dashboard
  let currentBrgy = localStorage.getItem(SELECT_KEY) || '';
  let pollInterval = null;
  let roundRobinIndex = 0;
  let trackedBarangays = (function(){
    try { const s = JSON.parse(localStorage.getItem('gomk.trackedBarangays')||'[]'); return Array.isArray(s)?s:[]; } catch { return []; }
  })();
  const listeners = new Set();

  const loadStore = () => { try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch { return {}; } };
  const saveStore = (o) => { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(o)); } catch { /* ignore */ } };
  const norm = (s) => (s||'').trim().replace(/\s+/g,' ').toLowerCase();

  function loadHistoryFor(b){
    const s = loadStore(); const key = norm(b);
    return s[key] || { water:[], air:[], temp:[], humid:[], times:[] };
  }
  function saveHistoryFor(b,h){
    const s = loadStore(); const key = norm(b);
    const trim = a => (a.length > MAX_POINTS ? a.slice(-MAX_POINTS) : a);
    s[key] = { water:trim(h.water||[]), air:trim(h.air||[]), temp:trim(h.temp||[]), humid:trim(h.humid||[]), times:trim(h.times||[]) };
    saveStore(s);
  }

  function computeWaterAlertLevel(v){ if (!Number.isFinite(v) || v <= 0) return { level: 0 }; if (v === 100) return { level: 1 }; if (v <= 33) return { level: 1 }; if (v <= 66) return { level: 2 }; return { level: 3 }; }

  function dispatchUpdate(payload){
    const ev = new CustomEvent('gomk:data', { detail: payload });
    window.dispatchEvent(ev);
    listeners.forEach(fn => { try { fn(payload); } catch {} });
  }

  async function poll(){
    if (!currentBrgy) return;
    try {
      const r = await fetch(`api/get_sensor_data.php?barangay=${encodeURIComponent(currentBrgy)}`);
      const data = await r.json();
      if (data.error || (data.status !== 'online' && data.status !== 'degraded')) return;

      const wl = computeWaterAlertLevel(Number(data.waterLevel)).level;
      const aqi = Number(data.airQuality);
      const t   = Number(data.temperature);
      const h   = Number(data.humidity);
      const ts  = data.timestamp || new Date().toISOString();

      const hist = loadHistoryFor(currentBrgy);
      const add = (arr, v) => { arr.push(Number.isFinite(v) ? v : null); if (arr.length > MAX_POINTS) arr.shift(); };
      const addTime = (arr, v) => { arr.push(v); if (arr.length > MAX_POINTS) arr.shift(); };

      add(hist.water, wl); add(hist.air, aqi); add(hist.temp, t); add(hist.humid, h); addTime(hist.times, ts);
      saveHistoryFor(currentBrgy, hist);

      dispatchUpdate({ barangay: currentBrgy, latest: { wl, aqi, t, h, ts }, history: hist });
    } catch { /* ignore network errors */ }
  }

  function start(){
    if (pollInterval) clearInterval(pollInterval);
    poll();
    pollInterval = setInterval(poll, 5000);
  }

  function setBarangay(b){ currentBrgy = b || ''; localStorage.setItem(SELECT_KEY, currentBrgy); start(); }
  function on(fn){ if (typeof fn === 'function') listeners.add(fn); }
  function off(fn){ listeners.delete(fn); }

  window.GoMKData = { setBarangay, on, off, loadHistoryFor, MAX_POINTS, getBarangay: () => currentBrgy };
  // Always start polling in the background
  start();
})();

document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.querySelector('.dashboard-search');
  const searchInput = document.querySelector('#reportSearch');
  const reportsList = document.querySelector('.reports-list');
  const reportCards = Array.from(document.querySelectorAll('.report-card'));
  const reportsSection = document.getElementById('reports');

  const filterToggle = document.querySelector('.filter-toggle');
  const filterMenu = document.getElementById('reportFilterMenu');
  const filterOptions = filterMenu ? Array.from(filterMenu.querySelectorAll('.filter-option')) : [];
  const filterLabel = filterToggle?.querySelector('span');
  const statusLabels = {
    all: 'Filter',
    unresolved: 'Unresolved',
    in_progress: 'In progress',
    solved: 'Solved',
  };

  let activeStatus = 'all';
  let lastManualStatus = 'all';
  let noResultsMessage = null;

  // Cache searchable text and normalize status attributes once on load.
  reportCards.forEach((card) => {
    if (!card.dataset.searchText) {
      card.dataset.searchText = card.textContent.toLowerCase();
    }
    if (!card.dataset.status) {
      card.dataset.status = (card.getAttribute('data-status') || '').toLowerCase();
    }
  });

  // Lazily insert the "no results" element when needed.
  const ensureNoResultsMessage = () => {
    if (!reportsList) return null;
    if (!noResultsMessage) {
      noResultsMessage = document.createElement('div');
      noResultsMessage.className = 'reports-empty-state';
      noResultsMessage.textContent = reportsList.dataset.emptyMessage || 'No reports match your filters yet.';
      noResultsMessage.style.display = 'none';
      reportsList.appendChild(noResultsMessage);
    }
    return noResultsMessage;
  };

  const setNoResultsVisible = (visible) => {
    const message = ensureNoResultsMessage();
    if (message) {
      message.style.display = visible ? 'flex' : 'none';
    }
  };

  // Detect keywords like "resolved" in the search box and map them to a status filter.
  const mapQueryToStatus = (query) => {
    if (!query) return null;
    const q = query.toLowerCase();
    if (/\b(solved|resolved)\b/.test(q)) return 'solved';
    if (/\b(unresolved|unsolved|pending)\b/.test(q)) return 'unresolved';
    if (/\bin[-\s]?progress\b/.test(q)) return 'in_progress';
    return null;
  };

  // Remove status-related words so the remaining text can be used for free-text search.
  const removeStatusWords = (query) => {
    if (!query) return '';
    return query
      .replace(/\b(solved|resolved|unsolved|unresolved|pending)\b/gi, ' ')
      .replace(/\bin[-\s]?progress\b/gi, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  };

  // Sync the filter pill UI and aria state with the active status.
  const updateFilterUI = (status, { inferred } = { inferred: false }) => {
    if (filterOptions.length) {
      filterOptions.forEach((option) => {
        const isActive = option.dataset.status === status;
        option.classList.toggle('active', isActive);
        option.setAttribute('aria-checked', isActive ? 'true' : 'false');
      });
    }

    if (filterLabel) {
      const labelText = statusLabels[status] || `${status.charAt(0).toUpperCase()}${status.slice(1).replace(/_/g, ' ')}`;
      filterLabel.textContent = status === 'all' ? 'Filter' : `Filter: ${labelText}`;
      filterLabel.dataset.inferred = inferred ? 'true' : 'false';
    }
  };

  const closeFilterMenu = () => {
    if (!filterMenu || !filterToggle) return;
    filterMenu.hidden = true;
    filterToggle.setAttribute('aria-expanded', 'false');
  };

  // Core filter pipeline: apply status + search text and toggle cards.
  const applyFilters = () => {
    const rawQuery = (searchInput?.value || '').trim();
    const normalizedQuery = rawQuery.toLowerCase();
    const statusFromQuery = mapQueryToStatus(normalizedQuery);

    if (statusFromQuery && statusFromQuery !== activeStatus) {
      activeStatus = statusFromQuery;
      updateFilterUI(activeStatus, { inferred: true });
    } else if (!statusFromQuery) {
      updateFilterUI(activeStatus, { inferred: false });
    }

    const textQuery = removeStatusWords(normalizedQuery);
    let visibleCount = 0;

    reportCards.forEach((card) => {
      const cardStatus = card.dataset.status || 'all';
      const matchesStatus = activeStatus === 'all' || cardStatus === activeStatus;
      const matchesSearch = !textQuery || (card.dataset.searchText || '').includes(textQuery);

      if (matchesStatus && matchesSearch) {
        card.style.removeProperty('display');
        visibleCount += 1;
      } else {
        card.style.display = 'none';
      }
    });

    setNoResultsVisible(visibleCount === 0);
    // If Masonry is active, relayout after filtering so items reposition correctly.
    try {
      if (typeof window.__gomkScheduleMasonryLayout === 'function') {
        window.__gomkScheduleMasonryLayout();
      } else if (window.__gomkMasonry && typeof window.__gomkMasonry.layout === 'function') {
        window.__gomkMasonry.layout();
      }
    } catch (e) { /* ignore */ }
  };

  // Toggle filter popover visibility.
  filterToggle?.addEventListener('click', (event) => {
    event.preventDefault();
    if (!filterMenu) return;
    const expanded = filterToggle.getAttribute('aria-expanded') === 'true';
    filterMenu.hidden = expanded;
    filterToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
  });

  // Clicking an option locks the status until the user clears it.
  filterOptions.forEach((option) => {
    option.addEventListener('click', () => {
      const { status } = option.dataset;
      if (!status || status === activeStatus) {
        closeFilterMenu();
        return;
      }

      activeStatus = status;
      lastManualStatus = status;
      updateFilterUI(activeStatus, { inferred: false });
      applyFilters();
      closeFilterMenu();
    });
  });

  // Close the filter menu when clicking outside of it.
  document.addEventListener('click', (event) => {
    if (!filterMenu || filterMenu.hidden) return;
    const withinMenu = filterMenu.contains(event.target);
    const withinToggle = filterToggle?.contains(event.target);
    if (!withinMenu && !withinToggle) {
      closeFilterMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeFilterMenu();
    }
  });

  // Typing triggers live filtering and restores manual status when cleared.
  searchInput?.addEventListener('input', () => {
    if (!searchInput.value.trim() && lastManualStatus !== activeStatus) {
      activeStatus = lastManualStatus;
      updateFilterUI(activeStatus, { inferred: false });
    }
    applyFilters();
  });

  const scrollToReports = () => {
    if (!reportsSection) return;
    const { top } = reportsSection.getBoundingClientRect();
    if (Math.abs(top) < 32) return;
    searchInput?.blur?.();
    window.requestAnimationFrame(() => {
      reportsSection.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
    });
  };

  /* Masonry-like row-major layout (JS Masonry) to ensure expanding a card only
     moves items beneath it, not the entire row. We enable Masonry at a
     configurable breakpoint and destroy it for small screens so the native
     responsive grid is used there. */
  (function initMasonryResponsive(){
    if (!reportsList) return;
  const DESIRED_COLS = 3; // always try to keep 3 columns
  const MIN_CARD_WIDTH = 210; // allow a bit narrower to avoid dropping to 2 cols
  const WIDTH_FUDGE = 24; // extra slack so scrollbars/rounding don't drop a column
    let masonry = null;
    let imagesLoadedLib = null;

    const loadScript = (src) => new Promise((resolve, reject) => {
      if (document.querySelector(`script[src="${src}"]`)) return resolve();
      const s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('Failed to load ' + src));
      document.head.appendChild(s);
    });

    const ensureLibs = async () => {
      // imagesLoaded then Masonry (unpkg CDN)
      if (typeof imagesLoaded === 'undefined') {
        await loadScript('https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js');
      }
      if (typeof Masonry === 'undefined') {
        await loadScript('https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js');
      }
      imagesLoadedLib = window.imagesLoaded;
    };

    const enable = async () => {
      if (!reportsList) return;
      if (masonry) return; // already enabled
      try {
        await ensureLibs();
      } catch (e) {
        // gracefully fail: keep grid layout
        return;
      }
      // Helpers to compute and apply a near-3-column layout with min width
      const computeColMetrics = () => {
        const gap = parseInt(getComputedStyle(reportsList).gap || 24, 10) || 24;
        const containerWidth = Math.max(320, reportsList.clientWidth || reportsList.offsetWidth || document.documentElement.clientWidth);
        // Try desired 3 columns, fallback to 2/1 if cards would get too narrow
        let cols = DESIRED_COLS;
        const widthFor = (c) => Math.floor(((containerWidth - WIDTH_FUDGE) - gap * (c - 1)) / c);
        let colWidth = widthFor(cols);
        while (cols > 1 && colWidth < MIN_CARD_WIDTH) {
          cols -= 1;
          colWidth = widthFor(cols);
        }
        return { cols, colWidth, gap };
      };

      const applyColMetrics = ({ colWidth }) => {
        Array.from(reportsList.querySelectorAll('.report-card')).forEach((c) => {
          c.style.width = colWidth + 'px';
        });
        if (masonry) {
          masonry.options.columnWidth = colWidth;
        }
      };

      const metrics = computeColMetrics();
      applyColMetrics(metrics);

  // Mark container so CSS doesn't keep grid behavior interfering
  reportsList.classList.add('gomk-masonry-active');

  // Initialize Masonry with a numeric columnWidth
      masonry = new Masonry(reportsList, {
        itemSelector: '.report-card',
        columnWidth: metrics.colWidth,
        percentPosition: false,
        gutter: metrics.gap,
        horizontalOrder: true,
        transitionDuration: 0
      });
      window.__gomkMasonry = masonry;

      // Debounced/scheduled layout helper to avoid layout thrash
      let layoutScheduled = false;
      const scheduleLayout = () => {
        if (!masonry) return;
        if (layoutScheduled) return;
        layoutScheduled = true;
        requestAnimationFrame(() => {
          layoutScheduled = false;
          try { masonry.layout(); } catch (e) { /* ignore */ }
        });
      };
      // expose for other modules (filters, see-more, etc.)
      window.__gomkScheduleMasonryLayout = scheduleLayout;
      // expose a recompute helper for resize
      window.__gomkRecomputeMasonryCols = () => {
        if (!masonry) return;
        const m = computeColMetrics();
        applyColMetrics(m);
        scheduleLayout();
      };

      // Wait for images then layout (scheduled)
      imagesLoadedLib(reportsList, () => scheduleLayout());

      // Observe mutations (cards show/hide) and schedule relayout.
      // IMPORTANT: do NOT observe attributes to avoid loops from Masonry's own
      // inline style updates during layout.
      const mo = new MutationObserver(() => scheduleLayout());
      mo.observe(reportsList, { childList: true, subtree: true });
      masonry.__gomkObserver = mo;

      // Also observe size changes of individual cards (e.g., See more expand)
      try {
        const ro = new ResizeObserver(() => scheduleLayout());
        Array.from(reportsList.querySelectorAll('.report-card')).forEach((el) => ro.observe(el));
        masonry.__gomkResizeObserver = ro;
      } catch (e) { /* ResizeObserver may be unavailable in very old browsers */ }
    };

    const disable = () => {
      if (!masonry) return;
      try {
        if (masonry.__gomkObserver) masonry.__gomkObserver.disconnect();
        if (masonry.__gomkResizeObserver) masonry.__gomkResizeObserver.disconnect();
        masonry.destroy();
      } catch (e) { /* ignore */ }
      // remove inline widths we set
      Array.from(reportsList.querySelectorAll('.report-card')).forEach((c) => {
        c.style.removeProperty('width');
      });
      reportsList.classList.remove('gomk-masonry-active');
      masonry = null;
      window.__gomkMasonry = null;
      try { delete window.__gomkScheduleMasonryLayout; } catch (e) {}
    };

    // Responsive toggling
    let resizeTimer = null;
    const check = () => {
      // Always enable Masonry and recompute columns on resize
      enable();
      try {
        if (typeof window.__gomkRecomputeMasonryCols === 'function') {
          window.__gomkRecomputeMasonryCols();
        }
      } catch (e) { /* ignore */ }
    };

    check();
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(check, 150);
    });
  })();

  searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    applyFilters();
    scrollToReports();
  });

  applyFilters();


  // Report details modal interactions
  const reportModal = document.getElementById('reportModal');
  if (reportModal && reportCards.length) {
    const modalDialog = reportModal.querySelector('.report-modal__dialog');
    const modalCloseButtons = Array.from(reportModal.querySelectorAll('[data-report-modal-close]'));
  const modalTitle = reportModal.querySelector('[data-report-modal-title]');
  const modalSubmitted = reportModal.querySelector('[data-report-modal-submitted]');
  const modalReporter = reportModal.querySelector('[data-report-modal-reporter]');
  const modalLocation = reportModal.querySelector('[data-report-modal-location]');
  const modalLocationItem = reportModal.querySelector('[data-report-modal-meta="location"]');
  const modalCategory = reportModal.querySelector('[data-report-modal-category]');
  const modalStatus = reportModal.querySelector('[data-report-modal-status]');
  const modalSummary = reportModal.querySelector('[data-report-modal-summary]');
  const modalMediaContainer = reportModal.querySelector('[data-report-modal-media]');
  const modalImage = reportModal.querySelector('[data-report-modal-image]');
  const modalPlaceholder = reportModal.querySelector('[data-report-modal-placeholder]');
    const modalBackdrop = reportModal.querySelector('[data-report-modal-backdrop]');

  let lastFocusedElement = null;
  let modalCloseTimer = null;
  const MODAL_ANIMATION_DURATION = 320;

    const applyStatusChip = (statusElement, modifier, label) => {
      if (!statusElement) return;
      statusElement.textContent = label || 'Status';
      statusElement.classList.remove('unresolved', 'in-progress', 'solved');
      if (modifier) {
        statusElement.classList.add(modifier);
      }
    };

    const updateMedia = (imageUrl, titleText) => {
      if (!modalImage || !modalPlaceholder || !modalMediaContainer) return;

      const hasImage = Boolean(imageUrl);

      modalMediaContainer.classList.toggle('has-image', hasImage);
      modalMediaContainer.classList.toggle('no-image', !hasImage);

      if (hasImage) {
        modalImage.src = imageUrl;
        modalImage.alt = `${titleText} photo`;
        modalImage.hidden = false;
        modalImage.removeAttribute('aria-hidden');
        modalPlaceholder.hidden = true;
        modalPlaceholder.setAttribute('aria-hidden', 'true');
        modalPlaceholder.style.display = 'none';
      } else {
        modalImage.removeAttribute('src');
        modalImage.alt = '';
        modalImage.hidden = true;
        modalImage.setAttribute('aria-hidden', 'true');
        modalPlaceholder.hidden = false;
        modalPlaceholder.removeAttribute('aria-hidden');
        modalPlaceholder.style.removeProperty('display');
      }
    };

    const openModal = (card) => {
      if (!card) return;
      if (modalCloseTimer) {
        clearTimeout(modalCloseTimer);
        modalCloseTimer = null;
      }

      lastFocusedElement = document.activeElement;
      reportModal.removeAttribute('hidden');
      document.body.classList.add('modal-open');
      reportModal.classList.remove('is-open');

      requestAnimationFrame(() => {
        reportModal.classList.add('is-open');
        requestAnimationFrame(() => {
          modalDialog?.focus({ preventScroll: true });
        });
      });

      const {
        title,
        summary,
        reporter,
        location,
        category,
        statusLabel,
        statusModifier,
        submitted,
        image,
      } = card.dataset;

      if (modalTitle) {
        modalTitle.textContent = title || 'Citizen report';
      }
      if (modalSubmitted) {
        modalSubmitted.textContent = submitted || '—';
      }
      if (modalReporter) {
        modalReporter.textContent = reporter || '—';
      }
      if (modalLocation) {
        modalLocation.textContent = location || '—';
      }
      if (modalLocationItem) {
        modalLocationItem.hidden = !location;
      }
      if (modalCategory) {
        if (category) {
          modalCategory.textContent = category;
          modalCategory.hidden = false;
        } else {
          modalCategory.textContent = 'Report';
          modalCategory.hidden = true;
        }
      }
      if (modalStatus) {
        applyStatusChip(modalStatus, statusModifier, statusLabel || 'Status');
        modalStatus.hidden = !statusLabel;
      }
      if (modalSummary) {
        modalSummary.textContent = summary || 'No summary provided.';
      }
      updateMedia(image, title || 'Report');
    };

    const closeModal = () => {
      if (reportModal.hasAttribute('hidden')) {
        return;
      }

      reportModal.classList.remove('is-open');

      modalCloseTimer = window.setTimeout(() => {
        reportModal.setAttribute('hidden', 'hidden');
        document.body.classList.remove('modal-open');

        if (modalImage) {
          modalImage.removeAttribute('src');
        }

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
          lastFocusedElement.focus({ preventScroll: true });
        }

        modalCloseTimer = null;
      }, MODAL_ANIMATION_DURATION);
    };

    modalCloseButtons.forEach((button) => {
      button.addEventListener('click', () => closeModal());
    });

    modalBackdrop?.addEventListener('click', () => closeModal());

    reportModal.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
      }
    });

    // Shared IntersectionObserver to only run location marquees when the
    // card is visible in the viewport. This reduces CPU work for offscreen
    // cards (especially when many are rendered).
    const locationMarqueeObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const el = entry.target;
        if (entry.isIntersecting) {
          if (typeof el._startMarquee === 'function') el._startMarquee();
        } else {
          if (typeof el._stopMarquee === 'function') el._stopMarquee();
        }
      });
    }, { threshold: 0.5 });

    reportCards.forEach((card) => {
      const handleOpen = (event) => {
        if (event.type === 'click' && event.target.closest('.icon-button')) {
          return;
        }
        // If the click was on a See more/less link, don't open modal
        if (event.type === 'click' && (event.target.closest('.report-see-more') || event.target.closest('.report-see-less'))) {
          event.preventDefault();
          event.stopPropagation();
          return;
        }
        openModal(card);
      };

      card.addEventListener('click', handleOpen);
      card.addEventListener('keydown', (event) => {
        if (event.target.closest('.icon-button')) {
          return;
        }
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleOpen(event);
        }
      });

      // See more/less behavior: expand/collapse the summary inline (no modal)
      const decodeEntities = (s) => {
        const el = document.createElement('div');
        el.innerHTML = s || '';
        return el.textContent || '';
      };

      const initInlineToggle = (p) => {
        if (!p) return;

        // the visible text node we will toggle; prefer an explicit span if present
        const textHolder = p.querySelector('.report-summary__text') || p;

        // Cache the collapsed text if not yet cached
        if (!p.dataset.collapsedText) {
          // Remove any trailing "See more/less" label from the current text
          const source = (textHolder.textContent || '').replace(/\s*(See more|See less)\s*$/i, '').trim();
          p.dataset.collapsedText = source;
        }

        const fullText = decodeEntities(card.dataset.summary || '');

        const setExpanded = (expand) => {
          p.dataset.expanded = expand ? 'true' : 'false';

          // Reset text to either collapsed or full
          if (textHolder.classList && textHolder.classList.contains('report-summary__text')) {
            textHolder.textContent = expand ? fullText : (p.dataset.collapsedText || '');
          } else {
            // textHolder is the paragraph itself
            textHolder.textContent = expand ? fullText : (p.dataset.collapsedText || '');
          }

          // Remove any existing action link
          const existing = p.querySelector('.report-see-more, .report-see-less');
          if (existing) existing.remove();

          // Append the appropriate action link after the text holder
          const link = document.createElement('a');
          link.href = '#';
          link.className = expand ? 'report-see-less' : 'report-see-more';
          link.textContent = expand ? ' See less' : ' See more';
          link.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            setExpanded(!expand);
          });

          p.appendChild(link);

          // Reflow Masonry once after DOM/text changes to avoid jank/freezes
          try {
            if (typeof window.__gomkScheduleMasonryLayout === 'function') {
              window.__gomkScheduleMasonryLayout();
            } else if (window.__gomkMasonry && typeof window.__gomkMasonry.layout === 'function') {
              window.__gomkMasonry.layout();
            }
          } catch (e) { /* ignore */ }
        };

        // Decide whether we need a see-more toggle. Prefer server-rendered
        // link, otherwise detect based on content length or visual overflow.
        const hasSeeMore = !!p.querySelector('.report-see-more');
        const collapsed = p.dataset.collapsedText || '';
        const needsToggle = () => {
          // If full text is longer than the collapsed snapshot, we need a toggle.
          if (fullText && fullText.length > collapsed.length) return true;
          // Otherwise, check if the rendered text is visually overflowing its box.
          try {
            return textHolder.scrollHeight > textHolder.clientHeight + 1;
          } catch (e) { return false; }
        };

        if (hasSeeMore || needsToggle()) {
          setExpanded(false);
        }
      };

      const summaryP = card.querySelector('.report-summary');
      if (summaryP) {
        initInlineToggle(summaryP);
      }

      // Marquee-like animated scrolling for overflowing location text.
      const initLocationMarquee = (el) => {
        if (!el) return;
        if (el.dataset.marqueeInit) return;

        // Wrap content in an inner span if not already done
        let inner = el.querySelector('.report-location__inner');
        if (!inner) {
          inner = document.createElement('span');
          inner.className = 'report-location__inner';
          // move child nodes into inner
          while (el.firstChild) {
            inner.appendChild(el.firstChild);
          }
          el.appendChild(inner);
        }

  // Only enable marquee when there are more than 4 words. Use the full
  // location (from the title attribute or dataset) for counting so that
  // server-side shortening doesn't prevent animation on long original
  // addresses.
  const fullText = (el.getAttribute('title') || el.dataset.location || inner.textContent || '').trim();
  const textForCount = fullText;
        const wordCount = textForCount ? textForCount.split(/\s+/).filter(Boolean).length : 0;
        if (wordCount <= 4) {
          // mark as initialized so we don't try again
          el.dataset.marqueeInit = '1';
          return;
        }

        // Ensure parent has overflow hidden (CSS should already) and inline-block sizing
        el.style.overflow = 'hidden';
        el.style.position = 'relative';

        // Per-element state and control functions
        let running = false;

        const run = () => {
          if (running === true) return;
          const parentW = el.clientWidth;
          const innerW = inner.scrollWidth;
          const distance = innerW - parentW;
          if (!(distance > 2)) {
            // nothing to scroll
            return;
          }

          running = true;

          // Speed: px per ms (lower = slower). Use 0.12 px/ms (~120px/sec).
          const pxPerMs = 0.12;
          const duration = Math.max(800, Math.round(distance / pxPerMs));

          // Start animation to left
          inner.style.transition = `transform ${duration}ms linear`;
          inner.style.transform = `translateX(-${distance}px)`;

          const onEnd = () => {
            inner.removeEventListener('transitionend', onEnd);
            // Pause 5s at the end
            setTimeout(() => {
              // reset instantly
              inner.style.transition = 'none';
              inner.style.transform = 'translateX(0)';
              // force reflow then restart after short delay
              // eslint-disable-next-line no-unused-expressions
              inner.offsetHeight;
              running = false;
              setTimeout(() => {
                if (el._isVisible) run();
              }, 600);
            }, 5000);
          };

          inner.addEventListener('transitionend', onEnd);
        };

        const startMarquee = () => {
          el._isVisible = true;
          // small delay so layout settles
          setTimeout(() => run(), 120);
        };

        const stopMarquee = () => {
          el._isVisible = false;
          // Freeze current position by removing transition and keeping computed transform
          const style = window.getComputedStyle(inner);
          const matrix = style.transform || style.webkitTransform || '';
          if (matrix && matrix !== 'none') {
            const parts = matrix.match(/matrix\((.+)\)/);
            let tx = 0;
            if (parts && parts[1]) {
              const nums = parts[1].split(',').map(s => parseFloat(s.trim()));
              tx = nums.length >= 5 ? nums[4] : 0;
            }
            inner.style.transition = 'none';
            inner.style.transform = `translateX(${tx}px)`;
          } else {
            inner.style.transition = 'none';
          }
          running = false;
        };

        // Pause/resume on hover
        el.addEventListener('mouseenter', () => {
          // treat hover as stop
          stopMarquee();
        });

        el.addEventListener('mouseleave', () => {
          // resume if visible
          if (el._isVisible) {
            // compute remaining and resume via startMarquee/run on next frame
            setTimeout(() => run(), 60);
          }
        });

        // expose controls for the shared observer
        el._startMarquee = startMarquee;
        el._stopMarquee = stopMarquee;

        // mark as initialized and register with observer
        el.dataset.marqueeInit = '1';
        if (typeof locationMarqueeObserver !== 'undefined' && locationMarqueeObserver) {
          locationMarqueeObserver.observe(el);
        } else {
          // fallback: run immediately
          setTimeout(run, 300);
        }
      };

      const loc = card.querySelector('.report-location');
      if (loc) {
        initLocationMarquee(loc);
      }
    });
  }

  // Scroll spy: keep the sidebar highlight in sync with the visible section.
  const sectionLinks = Array.from(document.querySelectorAll('.sidebar-link[data-section]'));
  const sections = sectionLinks
    .map((link) => {
      const section = document.getElementById(link.dataset.section);
      return section ? { link, section } : null;
    })
    .filter(Boolean);

  if (sections.length) {
    let activeSectionId = null;
    let scrollTicking = false;

  const setActiveSection = (sectionId) => {
      if (!sectionId || sectionId === activeSectionId) return;
      activeSectionId = sectionId;

      sections.forEach(({ link }) => {
        const isActive = link.dataset.section === sectionId;
        link.classList.toggle('active', isActive);
        if (isActive) {
          link.setAttribute('aria-current', 'page');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    };

  // Determine which section crosses the probe line (35% viewport height).
  const computeActiveByScroll = () => {
      const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      const probeLine = scrollY + viewportHeight * 0.35;

      let candidate = sections[0];

      sections.forEach((entry) => {
        const rect = entry.section.getBoundingClientRect();
        const top = rect.top + scrollY;
        const bottom = rect.bottom + scrollY;

        if (probeLine >= top && probeLine < bottom) {
          candidate = entry;
        } else if (probeLine >= bottom) {
          candidate = entry;
        }
      });

      setActiveSection(candidate.section.id);
    };

  // Throttle scroll events with requestAnimationFrame for perf.
  const scheduleScrollUpdate = () => {
      if (scrollTicking) return;
      scrollTicking = true;
      requestAnimationFrame(() => {
        scrollTicking = false;
        computeActiveByScroll();
      });
    };

  // If the page loads with a hash, jump the highlight immediately.
  const setFromHash = () => {
      const hashId = window.location.hash.replace('#', '');
      if (!hashId) return false;
      const match = sections.find(({ section }) => section.id === hashId);
      if (!match) return false;
      setActiveSection(match.section.id);
      return true;
    };

    sections.forEach(({ link, section }) => {
      link.addEventListener('click', () => setActiveSection(section.id));
    });

    window.addEventListener('scroll', scheduleScrollUpdate, { passive: true });
    window.addEventListener('resize', scheduleScrollUpdate);

    if (!setFromHash()) {
      computeActiveByScroll();
    }
  }

  // Mobile sidebar toggle
  const navToggleButtons = Array.from(document.querySelectorAll('[data-nav-toggle]'));
  const navScrim = document.querySelector('[data-nav-scrim]');
  const primarySidebar = document.getElementById('primary-sidebar');
  const navMediaQuery = window.matchMedia('(max-width: 768px)');

  if (navToggleButtons.length) {
    const setNavOpen = (open) => {
      const allowOpen = open && navMediaQuery.matches;
      const targetState = allowOpen;

      document.body.classList.toggle('nav-open', targetState);

      navToggleButtons.forEach((button) => {
        button.setAttribute('aria-expanded', targetState ? 'true' : 'false');
      });

      if (navScrim) {
        if (targetState) {
          navScrim.removeAttribute('hidden');
        } else {
          navScrim.setAttribute('hidden', 'hidden');
        }
      }
    };

    const toggleNav = () => {
      const isOpen = document.body.classList.contains('nav-open');
      setNavOpen(!isOpen);
    };

    navToggleButtons.forEach((button) => {
      button.addEventListener('click', toggleNav);
    });

    navScrim?.addEventListener('click', () => setNavOpen(false));

    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setNavOpen(false);
      }
    });

    const handleNavMediaChange = () => {
      if (!navMediaQuery.matches) {
        setNavOpen(false);
      }
    };

    if (typeof navMediaQuery.addEventListener === 'function') {
      navMediaQuery.addEventListener('change', handleNavMediaChange);
    } else if (typeof navMediaQuery.addListener === 'function') {
      navMediaQuery.addListener(handleNavMediaChange);
    }

    if (primarySidebar) {
      const sidebarLinks = Array.from(primarySidebar.querySelectorAll('a')); 
      sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
          if (navMediaQuery.matches) {
            setNavOpen(false);
          }
        });
      });

      // Mobile: toggle user dropdown when tapping the blue profile area
      const userToggle = primarySidebar.querySelector('[data-user-menu-toggle]');
      const userMenu = primarySidebar.querySelector('[data-user-menu]');
      let userMenuOpen = false;

      const setUserMenuOpen = (open) => {
        // Show only when NOT mobile (desktop/tablet)
        if (navMediaQuery.matches || !userMenu) return;
        userMenuOpen = !!open;
        userMenu.hidden = !userMenuOpen;
        userToggle?.setAttribute?.('aria-expanded', userMenuOpen ? 'true' : 'false');
      };

      userToggle?.addEventListener('click', (e) => {
        if (navMediaQuery.matches) return; // ignore on mobile
        e.preventDefault();
        e.stopPropagation();
        setUserMenuOpen(!userMenuOpen);
      });

      // Close when clicking outside inside sidebar
      primarySidebar.addEventListener('click', (e) => {
        if (navMediaQuery.matches || !userMenuOpen) return;
        if (userMenu && !userMenu.contains(e.target) && !userToggle.contains(e.target)) {
          setUserMenuOpen(false);
        }
      });
      // Close on Escape
      window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setUserMenuOpen(false);
      });
    }
  }

  // Dashboard notification popover toggle.
  const notificationContainer = document.querySelector('[data-notification]');

  if (notificationContainer) {
    const notificationToggle = notificationContainer.querySelector('[data-notification-toggle]');
    const notificationPanel = notificationContainer.querySelector('[data-notification-panel]');
    const notificationDot = notificationContainer.querySelector('.notification-dot');
    const markReadButton = notificationContainer.querySelector('[data-notification-mark-read]');
    const notificationList = notificationContainer.querySelector('.notification-list');

  let notificationsOpen = false;
  let notificationsLoaded = false; // set to true only after a successful load

    // Toggle the "All caught up" state on the mark-read button
    const setCaughtUp = (caught) => {
      if (!markReadButton) return;
      if (caught) {
        markReadButton.textContent = 'All caught up';
        markReadButton.setAttribute('aria-disabled', 'true');
        markReadButton.classList.add('is-disabled');
      } else {
        markReadButton.textContent = 'Mark all as read';
        markReadButton.removeAttribute('aria-disabled');
        markReadButton.classList.remove('is-disabled');
      }
    };

    const setNotificationOpen = (open) => {
      if (!notificationToggle || !notificationPanel) return;
      notificationsOpen = Boolean(open);
      notificationContainer.dataset.open = notificationsOpen ? 'true' : 'false';
      notificationToggle.setAttribute('aria-expanded', notificationsOpen ? 'true' : 'false');
      notificationPanel.hidden = !notificationsOpen;
      if (notificationsOpen) {
        notificationPanel?.focus?.({ preventScroll: true });
        if (!notificationsLoaded) loadNotifications();
      }
    };

    setNotificationOpen(false);

    // Preload unread count on page load so the dot is accurate after refresh
    (async function preloadUnread() {
      try {
        const r = await fetch('api/notifications_list.php?limit=1', { credentials: 'same-origin' });
        if (!r.ok) { if (notificationDot) notificationDot.setAttribute('hidden', 'hidden'); return; }
        const data = await r.json().catch(() => ({}));
        if (notificationDot) {
          if (data && data.success === true && (data.unreadCount || 0) > 0) {
            notificationDot.removeAttribute('hidden');
          } else {
            notificationDot.setAttribute('hidden', 'hidden');
          }
        }
        // Reflect caught-up state based on preload result
        if (typeof setCaughtUp === 'function') {
          setCaughtUp(!data || (data.unreadCount || 0) === 0);
        }
      } catch {
        if (notificationDot) notificationDot.setAttribute('hidden', 'hidden');
      }
    })();

    notificationToggle?.addEventListener('click', (event) => {
      event.preventDefault();
      setNotificationOpen(!notificationsOpen);
    });

    document.addEventListener('click', (event) => {
      if (!notificationsOpen) return;
      if (!notificationContainer.contains(event.target)) {
        setNotificationOpen(false);
      }
    });

    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && notificationsOpen) {
        setNotificationOpen(false);
        notificationToggle?.focus({ preventScroll: true });
      }
    });

    markReadButton?.addEventListener('click', () => {
      setCaughtUp(true);
      if (notificationDot && !notificationDot.hasAttribute('hidden')) {
        notificationDot.setAttribute('hidden', 'hidden');
      }
      // Persist read state if backend endpoint exists
      fetch('api/notifications_mark_read.php', { method: 'POST' }).catch(() => {});
    });

    // Fetch and render notifications for the signed-in user
    async function loadNotifications() {
      // show a tiny loading state
      if (notificationList) {
        notificationList.innerHTML = '<div class="notification-empty" role="status">Loading…</div>';
      }
      try {
        const r = await fetch('api/notifications_list.php?limit=20', { credentials: 'same-origin' });
        const data = await r.json().catch(() => ({}));

        // Not authenticated: show friendly message and allow retry later
        if (!r.ok && r.status === 401) {
          notificationsLoaded = false; // allow retry after login
          if (notificationList) {
            notificationList.innerHTML = '<div class="notification-empty">Sign in to see your notifications.</div>';
          }
          if (notificationDot) notificationDot.setAttribute('hidden', 'hidden');
          return;
        }

        if (!data || data.success !== true) {
          notificationsLoaded = false; // allow retry if server returned an error
          if (notificationList) {
            notificationList.innerHTML = '<div class="notification-empty">Unable to load notifications right now.</div>';
          }
          if (notificationDot) notificationDot.setAttribute('hidden', 'hidden');
          return;
        }

        if (Array.isArray(data.data) && notificationList) {
          notificationList.innerHTML = '';
          data.data.forEach((item) => {
            const li = document.createElement('article');
            li.className = 'notification-item';
            li.setAttribute('role', 'listitem');
            // mark unread visually
            if (item.is_read === 0 || item.is_read === false) {
              li.classList.add('is-unread');
            }
            const iconClass = item.type === 'warning' ? 'warning' : (item.type === 'success' ? 'success' : item.type === 'error' ? 'error' : '');
            li.innerHTML = `
              <div class="notification-icon ${iconClass}" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 8h.01" />
                  <path d="M12 12v4" />
                </svg>
              </div>
              <div class="notification-content">
                <p class="notification-title"></p>
                <p class="notification-meta"></p>
              </div>`;
            // add delete button (hidden until hover)
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'notification-delete';
            del.setAttribute('aria-label', 'Delete notification');
            // simpler X icon for delete
            del.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>';
            // attach dataset id for deletion
            del.dataset.notificationId = item.id || '';
            // on click, call delete API and remove item
            del.addEventListener('click', async (ev) => {
              ev.stopPropagation();
              const nid = del.dataset.notificationId;
              if (!nid) return;
              // confirmation step (use stylized modal if available)
              let confirmed = true;
              if (window.GOMK && window.GOMK.confirmDialog) {
                confirmed = await window.GOMK.confirmDialog('Delete this notification? This cannot be undone.', { okText: 'Delete', cancelText: 'Cancel' });
              } else {
                confirmed = window.confirm('Delete this notification? This cannot be undone.');
              }
              if (!confirmed) return;
              del.disabled = true;
              try {
                const fd = new FormData();
                fd.append('notification_id', nid);
                const r = await fetch('api/notifications_delete.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const res = await r.json().catch(() => ({}));
                if (r.ok && res && res.success) {
                  // remove from DOM
                  li.remove();
                  // update unread dot if needed
                  if (notificationDot && (item.is_read === 0 || item.is_read === false)) {
                    // re-check unread count quickly
                    try {
                      const rr = await fetch('api/notifications_list.php?limit=1', { credentials: 'same-origin' });
                      if (rr.ok) {
                        const dd = await rr.json().catch(() => ({}));
                        if (dd && (dd.unreadCount || 0) > 0) notificationDot.removeAttribute('hidden');
                        else notificationDot.setAttribute('hidden', 'hidden');
                        if (typeof setCaughtUp === 'function') setCaughtUp(!(dd && (dd.unreadCount || 0) > 0));
                      }
                    } catch (e) {}
                  }
                } else {
                  (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Unable to delete notification', { type: 'error' }) : alert('Unable to delete notification');
                  del.disabled = false;
                }
              } catch (e) {
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Unable to delete notification', { type: 'error' }) : alert('Unable to delete notification');
                del.disabled = false;
              }
            });
            li.appendChild(del);
            li.querySelector('.notification-title').textContent = item.title || 'Notification';
            li.querySelector('.notification-meta').textContent = item.meta || '';
            notificationList.appendChild(li);
          });
          if (!data.data.length) {
            notificationList.innerHTML = '<div class="notification-empty">No notifications yet.</div>';
          }

          // Constrain height to ~4 items; enable scrolling if there are more
          const items = notificationList.querySelectorAll('.notification-item');
          if (items.length > 4) {
            const firstH = items[0]?.offsetHeight || 64;
            const pad = 16; // small breathing room
            notificationList.style.maxHeight = (firstH * 4 + pad) + 'px';
            notificationList.style.overflowY = 'auto';
          } else {
            notificationList.style.maxHeight = '';
            notificationList.style.overflowY = '';
          }
        }

        if (notificationDot) {
          if ((data.unreadCount || 0) > 0) notificationDot.removeAttribute('hidden');
          else notificationDot.setAttribute('hidden', 'hidden');
        }
        setCaughtUp(!data || (data.unreadCount || 0) === 0);
        notificationsLoaded = true; // mark success
      } catch (e) {
        // network error: keep retry capability and show a friendly note
        notificationsLoaded = false;
        if (notificationDot) {
          notificationList.innerHTML = '<div class="notification-empty">Can\'t connect. Please try again.</div>';
        }
        if (notificationDot) notificationDot.setAttribute('hidden', 'hidden');
      }
    }
  }

  // Admin: wire status dropdowns to API endpoint
  (function initAdminStatusWiring() {
    const adminForms = Array.from(document.querySelectorAll('.admin-inline-form'));
    if (!adminForms.length) return;

    adminForms.forEach((form) => {
      const actionInput = form.querySelector('input[name="action"][value="update_status"]');
      const select = form.querySelector('select[name="status"]');
      if (!actionInput || !select) return;

      const reportIdInput = form.querySelector('input[name="report_id"]');
      const reportId = reportIdInput ? reportIdInput.value : '';

      const handleChange = async () => {
        const status = select.value;
        select.disabled = true;
        try {
          const fd = new FormData();
          fd.append('report_id', reportId);
          fd.append('status', status);
          const r = await fetch('api/reports_update_status.php', { method: 'POST', body: fd });
          const data = await r.json().catch(() => ({}));
          if (!r.ok || !data.success) {
            throw new Error(data.message || 'Failed to update status');
          }
          select.classList.add('saved');
          setTimeout(() => select.classList.remove('saved'), 800);
        } catch (e) {
          if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast(e.message || 'There was a problem updating the status.', { type: 'error' });
          else alert(e.message || 'There was a problem updating the status.');
        } finally {
          select.disabled = false;
        }
      };

      select.addEventListener('change', (ev) => {
        ev.preventDefault();
        handleChange();
      });
    });
  })();

  // Auth card mode toggle (login <-> signup) on profile page.
  const authCard = document.querySelector('.auth-card');
  if (authCard) {
    const authTitle = authCard.querySelector('[data-auth-title]');
    const loginForm = authCard.querySelector('.auth-form-login');
    const signupForm = authCard.querySelector('.auth-form-signup');
    const loginFooter = authCard.querySelector('.auth-footer-login');
    const signupFooter = authCard.querySelector('.auth-footer-signup');
    const switchLinks = Array.from(authCard.querySelectorAll('[data-auth-switch]'));

    const setAuthMode = (mode) => {
      const normalized = mode === 'signup' ? 'signup' : 'login';
      authCard.setAttribute('data-auth-mode', normalized);

      if (authTitle) {
        authTitle.textContent = normalized === 'signup' ? 'Sign Up' : 'Log In';
      }

      if (loginForm) {
        if (normalized === 'login') {
          loginForm.removeAttribute('hidden');
        } else {
          loginForm.setAttribute('hidden', 'hidden');
        }
      }

      if (signupForm) {
        if (normalized === 'signup') {
          signupForm.removeAttribute('hidden');
        } else {
          signupForm.setAttribute('hidden', 'hidden');
        }
      }

      if (loginFooter) {
        loginFooter.hidden = normalized !== 'login';
      }

      if (signupFooter) {
        signupFooter.hidden = normalized !== 'signup';
      }
    };

    switchLinks.forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        const { authSwitch } = link.dataset;
        setAuthMode(authSwitch === 'signup' ? 'signup' : 'login');
      });
    });

    setAuthMode(authCard.getAttribute('data-auth-mode') || 'login');
  }

  // Client-side: validate signup password strength before submit
  try {
    const signupFormEl = document.querySelector('.auth-form-signup');
    if (signupFormEl) {
      const mobileInput = signupFormEl.querySelector('#signupMobile');
      const emailInput = signupFormEl.querySelector('#signupEmail');
      const pwd = signupFormEl.querySelector('#signupPassword');

      // Mobile: enforce +63 prefix, max 10 digits after, no spaces
      if (mobileInput) {
        const ensurePrefix = () => {
          let v = mobileInput.value || '';
          // Remove spaces
          v = v.replace(/\s+/g, '');
          if (!v.startsWith('+63')) v = '+63' + v.replace(/^[+]*(63)?/, '');
          // Keep only digits after prefix and clamp to 10 digits
          const rest = v.slice(3).replace(/\D+/g, '').slice(0, 10);
          mobileInput.value = '+63' + rest;
        };
        // Initialize on focus/blur/input
        mobileInput.addEventListener('focus', () => { if (!mobileInput.value) mobileInput.value = '+63'; });
        mobileInput.addEventListener('input', ensurePrefix);
        mobileInput.addEventListener('blur', ensurePrefix);
        // Prevent deleting prefix
        mobileInput.addEventListener('keydown', (e) => {
          const pos = mobileInput.selectionStart || 0;
          if ((e.key === 'Backspace' && pos <= 3) || (e.key === 'Delete' && pos < 3)) {
            e.preventDefault();
            mobileInput.setSelectionRange(3, 3);
          }
        });
      }

      signupFormEl.addEventListener('submit', (ev) => {
        // Email: simple domain check, no spaces
        if (emailInput) {
          const email = (emailInput.value || '').trim();
          const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailRe.test(email)) {
            ev.preventDefault();
            if (window.GOMK?.showToast) window.GOMK.showToast('Please enter a valid email address.', { type: 'error' });
            emailInput.focus();
            return;
          }
        }

        // Mobile: validate final format +63XXXXXXXXXX
        if (mobileInput) {
          const mv = (mobileInput.value || '').trim();
          if (!/^\+63\d{10}$/.test(mv)) {
            ev.preventDefault();
            if (window.GOMK?.showToast) window.GOMK.showToast('Please enter a valid mobile number (+63XXXXXXXXXX).', { type: 'error' });
            mobileInput.focus();
            return;
          }
        }

        // Password: at least 8 chars, 1 uppercase, 1 number, 1 special, no spaces
        if (pwd) {
          const strongRe = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$/;
          if (!strongRe.test(pwd.value || '')) {
            ev.preventDefault();
            if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast('Password must be 8+ characters, include an uppercase letter, a number, a special character, and no spaces.', { type: 'error', duration: 3500 });
            pwd.focus();
            return;
          }
        }
      });
    }
  } catch (err) { /* ignore JS errors */ }

  // Password visibility toggles ("spy" buttons) on auth forms.
  const passwordToggleButtons = Array.from(document.querySelectorAll('[data-password-toggle]'));

  if (passwordToggleButtons.length) {
    passwordToggleButtons.forEach((button) => {
      const targetId = button.dataset.passwordToggle;
      const input = (targetId && document.getElementById(targetId))
        || button.closest('.auth-field-input')?.querySelector('input[data-password-field]');

      if (!input) {
        return;
      }

      const setVisibility = (visible) => {
        input.type = visible ? 'text' : 'password';
        button.dataset.visible = visible ? 'true' : 'false';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
      };

      // Initialize to the input's current type.
      setVisibility(input.type === 'text');

      // toggle button click handler (kept inside the forEach)
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const isVisible = button.dataset.visible === 'true';
        setVisibility(!isVisible);
      });
    });
  }

  // Numeric-only inputs: strip non-digit characters as the user types for inputs with data-numeric-only
  const numericInputs = Array.from(document.querySelectorAll('input[data-numeric-only]'));
  if (numericInputs.length) {
    numericInputs.forEach((inp) => {
      inp.addEventListener('input', (ev) => {
        const old = inp.value;
        const filtered = old.replace(/\D+/g, ''); // remove non-digits
        if (old !== filtered) {
          // preserve caret position as best effort
          const pos = inp.selectionStart || filtered.length;
          inp.value = filtered;
          try { inp.setSelectionRange(pos - 1, pos - 1); } catch (e) { /* ignore */ }
          if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast('Only digits are allowed in the mobile number.', { type: 'error', duration: 2200 });
        }
      });
      // Optionally prevent paste with non-digits
      inp.addEventListener('paste', (e) => {
        try {
          const pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
          if (/\D/.test(pasted)) {
            e.preventDefault();
            const digits = pasted.replace(/\D+/g, '');
            // insert digits at cursor
            const start = inp.selectionStart || 0;
            const end = inp.selectionEnd || 0;
            const val = inp.value;
            inp.value = val.slice(0, start) + digits + val.slice(end);
            if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast('Pasted content contained non-digits — only digits were kept.', { type: 'info', duration: 2200 });
          }
        } catch (err) { /* ignore */ }
      });
    });
  }

  // Create Report Page Functionality
  const initializeCreateReport = () => {
    console.log('Create report page loaded');
    
    const photoUploadArea = document.getElementById('photoUploadArea');
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');

  if (photoUploadArea && photoInput && photoPreview) {
      console.log('Photo upload elements found, initializing...');
      
      // Click to upload
      photoUploadArea.addEventListener('click', () => {
        console.log('Photo upload area clicked');
        photoInput.click();
      });

      // Drag and drop functionality
      photoUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        photoUploadArea.classList.add('dragover');
      });

      photoUploadArea.addEventListener('dragleave', () => {
        photoUploadArea.classList.remove('dragover');
      });

      photoUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        photoUploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          handleFileSelect(files[0]);
        }
      });

      // Keep a reference to the last selected image file (pre-crop)
      let selectedImageFile = null;

      // File input change
      photoInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
          selectedImageFile = e.target.files[0];
          handleFileSelect(e.target.files[0]);
        }
      });

      function handleFileSelect(file) {
        if (file && file.type.startsWith('image/')) {
          // Keep a reference so we can still upload if the user submits without cropping
          selectedImageFile = file;
          const reader = new FileReader();
          reader.onload = (e) => {
            showCropModal(e.target.result);
          };
          reader.readAsDataURL(file);
        }
      }

  // Cropping functionality (free crop with resize handles)
  let cropImage = null;
  let cropBox = null;
  let isDragging = false;      // moving the box
  let isResizing = false;      // resizing via handles
  let resizeDir = null;        // which handle is active
  let dragStart = { x: 0, y: 0 };

      function showCropModal(imageSrc) {
        const modal = document.getElementById('cropModal');
        const canvas = document.getElementById('cropCanvas');
        
        if (!modal || !canvas) return;
        
        modal.style.display = 'flex';
        
        const img = new Image();
        img.onload = () => {
          const ctx = canvas.getContext('2d');
          
          // Calculate size to fit in viewport while maintaining aspect ratio
          const maxWidth = Math.min(600, window.innerWidth * 0.8);
          const maxHeight = Math.min(400, window.innerHeight * 0.7);
          const scale = Math.min(maxWidth / img.width, maxHeight / img.height);
          
          // Set display size
          canvas.style.width = Math.round(img.width * scale) + 'px';
          canvas.style.height = Math.round(img.height * scale) + 'px';
          
          // Set actual canvas size to match original image
          canvas.width = img.width;
          canvas.height = img.height;
          
          // Draw image
          ctx.drawImage(img, 0, 0);
          
          // Initialize crop box with scaled dimensions (freeform, ~80% of display size)
          initializeCropBox(img.width * scale, img.height * scale);
          cropImage = img;
        };
        img.src = imageSrc;
      }

      function initializeCropBox(imageWidth, imageHeight) {
        const cropBoxElement = document.getElementById('cropBox');
        
        if (!cropBoxElement) return;
        
        cropBoxElement.innerHTML = '';
        // Free crop: start with a large centered box (80% of area)
        let cropWidth = imageWidth * 0.8;
        let cropHeight = imageHeight * 0.8;
        
        // Center the crop box
        const left = (imageWidth - cropWidth) / 2;
        const top = (imageHeight - cropHeight) / 2;
        
        cropBoxElement.style.left = left + 'px';
        cropBoxElement.style.top = top + 'px';
        cropBoxElement.style.width = cropWidth + 'px';
        cropBoxElement.style.height = cropHeight + 'px';
        cropBoxElement.style.display = 'block';
        cropBoxElement.style.position = 'absolute';
        
        cropBoxElement.addEventListener('mousedown', startDrag);
        // Add resize handles
        const handles = ['nw','n','ne','e','se','s','sw','w'];
        handles.forEach((dir) => {
          const h = document.createElement('span');
          h.className = `handle ${dir}`;
          h.dataset.dir = dir;
          h.addEventListener('mousedown', startResize);
          cropBoxElement.appendChild(h);
        });
        
        const canvas = document.getElementById('cropCanvas');
        if (canvas) {
          canvas.style.pointerEvents = 'none';
        }
        
        cropBox = {
          element: cropBoxElement,
          x: left,
          y: top,
          width: cropWidth,
          height: cropHeight
        };
      }

      function startDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        isDragging = true;
        
        const rect = document.querySelector('.crop-area').getBoundingClientRect();
        dragStart.x = e.clientX - rect.left - cropBox.x;
        dragStart.y = e.clientY - rect.top - cropBox.y;
        
        document.addEventListener('mousemove', dragCropBox);
        document.addEventListener('mouseup', stopDrag);
      }

      function startResize(e) {
        e.preventDefault();
        e.stopPropagation();
        isResizing = true;
        resizeDir = e.currentTarget.dataset.dir;
        const rect = document.querySelector('.crop-area').getBoundingClientRect();
        dragStart.x = e.clientX - rect.left;
        dragStart.y = e.clientY - rect.top;
        document.addEventListener('mousemove', resizeCropBox);
        document.addEventListener('mouseup', stopResize);
      }

      function dragCropBox(e) {
        if (!isDragging || !cropBox) return;
        
        e.preventDefault();
        const rect = document.querySelector('.crop-area').getBoundingClientRect();
        const newX = e.clientX - rect.left - dragStart.x;
        const newY = e.clientY - rect.top - dragStart.y;
        
        const maxX = rect.width - cropBox.width;
        const maxY = rect.height - cropBox.height;
        
        cropBox.x = Math.max(0, Math.min(newX, maxX));
        cropBox.y = Math.max(0, Math.min(newY, maxY));
        
        updateCropBoxElement();
      }

      function resizeCropBox(e) {
        if (!isResizing || !cropBox) return;
        e.preventDefault();
        const area = document.querySelector('.crop-area');
        const rect = area.getBoundingClientRect();
        const px = e.clientX - rect.left;
        const py = e.clientY - rect.top;

        const minSize = 40; // minimum crop size in pixels

        let x = cropBox.x;
        let y = cropBox.y;
        let w = cropBox.width;
        let h = cropBox.height;

        switch (resizeDir) {
          case 'nw': w += x - px; h += y - py; x = px; y = py; break;
          case 'ne': w = px - x; h += y - py; y = py; break;
          case 'se': w = px - x; h = py - y; break;
          case 'sw': w += x - px; x = px; h = py - y; break;
          case 'n':  h += y - py; y = py; break;
          case 's':  h = py - y; break;
          case 'e':  w = px - x; break;
          case 'w':  w += x - px; x = px; break;
        }

        // Constrain within area
        if (x < 0) { w += x; x = 0; }
        if (y < 0) { h += y; y = 0; }
        if (x + w > rect.width)  w = rect.width - x;
        if (y + h > rect.height) h = rect.height - y;

        // Enforce min size
        w = Math.max(minSize, w);
        h = Math.max(minSize, h);

        cropBox.x = x; cropBox.y = y; cropBox.width = w; cropBox.height = h;
        updateCropBoxElement();
      }

      function stopResize() {
        isResizing = false;
        resizeDir = null;
        document.removeEventListener('mousemove', resizeCropBox);
        document.removeEventListener('mouseup', stopResize);
      }

      function stopDrag() {
        isDragging = false;
        document.removeEventListener('mousemove', dragCropBox);
        document.removeEventListener('mouseup', stopDrag);
      }

      function updateCropBoxElement() {
        if (!cropBox) return;
        
        cropBox.element.style.left = cropBox.x + 'px';
        cropBox.element.style.top = cropBox.y + 'px';
        cropBox.element.style.width = cropBox.width + 'px';
        cropBox.element.style.height = cropBox.height + 'px';
      }

      // Modal event listeners
      const cropClose = document.getElementById('cropClose');
      const cropCancel = document.getElementById('cropCancel');
      const cropConfirm = document.getElementById('cropConfirm');

      if (cropClose) cropClose.addEventListener('click', hideCropModal);
      if (cropCancel) cropCancel.addEventListener('click', hideCropModal);
      if (cropConfirm) cropConfirm.addEventListener('click', confirmCrop);

      function hideCropModal() {
        const modal = document.getElementById('cropModal');
        if (modal) {
          modal.style.display = 'none';
        }
        cropImage = null;
        cropBox = null;
      }

      function confirmCrop() {
        if (!cropImage || !cropBox) return;
        
        const canvas = document.getElementById('cropCanvas');
        const scaleX = cropImage.width / canvas.offsetWidth;
        const scaleY = cropImage.height / canvas.offsetHeight;
        
        const actualX = Math.round(cropBox.x * scaleX);
        const actualY = Math.round(cropBox.y * scaleY);
        const actualWidth = Math.round(cropBox.width * scaleX);
        const actualHeight = Math.round(cropBox.height * scaleY);
        
        const croppedCanvas = document.createElement('canvas');
        const ctx = croppedCanvas.getContext('2d');
        
        croppedCanvas.width = actualWidth;
        croppedCanvas.height = actualHeight;
        
        ctx.drawImage(
          cropImage,
          actualX, actualY, actualWidth, actualHeight,
          0, 0, actualWidth, actualHeight
        );
        
        croppedCanvas.toBlob((blob) => {
          const url = URL.createObjectURL(blob);
          photoPreview.src = url;
          photoUploadArea.classList.add('has-photo');
          
          const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          photoInput.files = dataTransfer.files;
          selectedImageFile = file; // Replace the original with the cropped one
          
          hideCropModal();
        }, 'image/jpeg', 0.9);
      }

      // Form submission
      const createReportForm = document.getElementById('createReportForm');
      if (createReportForm) {
        createReportForm.addEventListener('submit', (e) => {
          e.preventDefault();
          
          const formData = new FormData(e.target);
          const category = formData.get('category');
          const title = formData.get('title');
          const description = formData.get('description');
          const location = formData.get('location');
          const currentPhoto = formData.get('photo');
          
          if (!category) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please select a category', { type: 'info' }) : alert('Please select a category');
            return;
          }
          
          if (!title.trim()) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please enter a title for your concern', { type: 'info' }) : alert('Please enter a title for your concern');
            return;
          }
          
          if (!description.trim()) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please enter a description', { type: 'info' }) : alert('Please enter a description');
            return;
          }
          
          if (!location.trim()) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please enter a location', { type: 'info' }) : alert('Please enter a location');
            return;
          }

          // If user selected a photo but didn't confirm crop, ensure it's still uploaded
          let hasPhotoFile = false;
          if ((!currentPhoto || (currentPhoto && typeof currentPhoto === 'string')) && selectedImageFile instanceof File) {
            formData.set('photo', selectedImageFile, selectedImageFile.name || 'photo.jpg');
            hasPhotoFile = true;
          }
          // If formData already contains a File under 'photo', honor it
          const pf = formData.get('photo');
          if (pf instanceof File) hasPhotoFile = true;

          // Photo is optional. Ensure we don't send an empty string for 'photo'
          // (some browsers may include an empty value in FormData). If no valid
          // File is present, remove the key so backend sees it as absent.
          const pfFinal = formData.get('photo');
          if (!(pfFinal instanceof File)) {
            try { formData.delete('photo'); } catch (e) {}
          }

          // Submit to backend
          fetch('api/reports_create.php', {
            method: 'POST',
            body: formData,
          })
          .then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) {
              const msg = data && data.message ? data.message : 'Failed to submit report.';
              throw new Error(msg);
            }
            return data;
          })
          .then((data) => {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Report submitted successfully!', { type: 'success' }) : alert('Report submitted successfully!');
            clearForm();
            // Optional: redirect back to home to see it in the feed
            setTimeout(() => { window.location.href = 'index.php#reports'; }, 300);
          })
          .catch((err) => {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast(err.message || 'There was a problem submitting your report.', { type: 'error' }) : alert(err.message || 'There was a problem submitting your report.');
          });
        });
      }

      function clearForm() {
        const form = document.getElementById('createReportForm');
        if (form) {
          form.reset();
        }
        photoUploadArea.classList.remove('has-photo');
        photoPreview.src = '';
      }

      // Clear form button handler
      const clearFormBtn = document.getElementById('clearFormBtn');
      if (clearFormBtn) { clearFormBtn.addEventListener('click', clearForm); }

      // Photo delete (X) button overlay
      const photoDelete = document.getElementById('photoDelete');
      if (photoDelete) {
        photoDelete.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          clearForm();
          // Also clear the file input files
          try { photoInput.value = ''; } catch {}
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Photo removed', { type: 'info' }) : void 0;
        });
      }

      // Location field click handler
      const locationField = document.getElementById('location');
      if (locationField) {
        locationField.addEventListener('click', () => {
          console.log('Location field clicked - map integration coming soon');
        });
      }

      console.log('Photo upload functionality fully initialized');
    } else {
      console.log('Photo upload elements not found');
    }
  };

  // Map / Place Picker integration for create-report page using Leaflet + Nominatim
  async function initMapPlacePicker() {
    const mapModal = document.getElementById('mapModal');
    const mapModalClose = document.getElementById('mapModalClose');
    const locationField = document.querySelector('.location-field');
    const mapEl = document.getElementById('reportMap');
    const placeInput = document.getElementById('leafletPlaceInput');
    const usePlaceBtn = document.getElementById('mapUsePlace');
    const clearBtn = document.getElementById('mapClearSelection');

    if (!locationField || !mapEl) return;

    let map = null;
    let marker = null;
    let chosenPlace = null;

    // Initialize Leaflet map when modal opens (lazy init)
    const ensureMap = () => {
      if (map) return map;
      try {
  // Marikina bounding box (approx) - lat, lng pairs: [[south, west], [north, east]]
  const marikinaBounds = L.latLngBounds([[14.57, 121.02], [14.76, 121.16]]);
  // Initialize map centered in Marikina and restrict to Marikina bounds
  map = L.map(mapEl).setView([14.650, 121.102], 13);
  map.setMaxBounds(marikinaBounds);
  map.options.maxBoundsViscosity = 0.95;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

  marker = L.marker([14.65, 121.102], { draggable: true }).addTo(map);
  try { marker.bindPopup('Drag or click to set location'); } catch (e) {}
        marker.on('dragend', async () => {
          const pos = marker.getLatLng();
          // clamp marker to Marikina bounds if dragged outside
          try {
            if (typeof marikinaBounds !== 'undefined' && !marikinaBounds.contains(pos)) {
              const clampedLat = Math.max(marikinaBounds.getSouth(), Math.min(marikinaBounds.getNorth(), pos.lat));
              const clampedLng = Math.max(marikinaBounds.getWest(), Math.min(marikinaBounds.getEast(), pos.lng));
              marker.setLatLng([clampedLat, clampedLng]);
              // update pos to clamped value
              pos.lat = clampedLat; pos.lng = clampedLng;
            }
          } catch (e) {}
          const latIn = document.getElementById('location_lat');
          const lngIn = document.getElementById('location_lng');
          const locInput = document.getElementById('location');
          if (latIn) latIn.value = pos.lat;
          if (lngIn) lngIn.value = pos.lng;
          // Reverse geocode and populate address
          const rev = await nominatimReverse(pos.lat, pos.lng);
          const display = rev && (rev.display_name || (rev.address && Object.values(rev.address).join(', '))) ? (rev.display_name || '') : '';
          if (locInput && display) locInput.value = display;
          try { if (placeInput && display) placeInput.value = display; } catch (e) {}
          try { marker.unbindPopup && marker.unbindPopup(); } catch (e) {}
          try { if (display) marker.bindPopup(display).openPopup(); } catch (e) {}
          try { chosenPlace = { lat: pos.lat, lon: pos.lng, display_name: display }; } catch (e) {}
        });

        // Clicking the map places the marker and reverse-geocodes
        map.on('click', async (e) => {
          try {
            const lat = e.latlng.lat;
            const lon = e.latlng.lng;
            // Prevent placing marker outside Marikina
            try {
              if (typeof marikinaBounds !== 'undefined' && !marikinaBounds.contains([lat, lon])) {
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please pick a location inside Marikina City.', { type: 'info' }) : alert('Please pick a location inside Marikina City.');
                return;
              }
            } catch (e) {}
            marker.setLatLng([lat, lon]);
            const latIn = document.getElementById('location_lat');
            const lngIn = document.getElementById('location_lng');
            const locInput = document.getElementById('location');
            if (latIn) latIn.value = lat;
            if (lngIn) lngIn.value = lon;
            const rev = await nominatimReverse(lat, lon);
            const display = rev && (rev.display_name || (rev.address && Object.values(rev.address).join(', '))) ? (rev.display_name || '') : '';
            if (locInput && display) locInput.value = display;
            try { if (placeInput && display) placeInput.value = display; } catch (e) {}
            try { marker.unbindPopup && marker.unbindPopup(); } catch (e) {}
            try { if (display) marker.bindPopup(display).openPopup(); } catch (e) {}
            try { chosenPlace = { lat: lat, lon: lon, display_name: display }; } catch (e) {}
          } catch (e) { /* ignore */ }
        });
      } catch (e) {
        map = null; marker = null;
      }
      return map;
    };

    const openModal = () => {
      if (!mapModal) return;
      mapModal.setAttribute('open', '');
      mapModal.style.display = 'flex';
      document.body.classList.add('modal-open');
      // create map if needed and invalidate size so tiles render. Call invalidateSize
      // twice (immediately and again after a short delay) to handle cases where the
      // modal or its animations cause the map container to be measured with 0 size
      // initially. This is a minimal change to ensure the map appears promptly.
      setTimeout(() => {
        ensureMap();
        try {
          if (map && typeof map.invalidateSize === 'function') {
            // force immediate reflow of tiles
            map.invalidateSize(true);
            // schedule a second invalidate to catch any late layout/transition changes
            setTimeout(() => { try { map.invalidateSize(true); } catch (e) {} }, 250);
          }
        } catch (e) {}
        if (chosenPlace && marker) {
          try { marker.setLatLng([chosenPlace.lat, chosenPlace.lon]); map.setView([chosenPlace.lat, chosenPlace.lon], 17); } catch (e) {}
        }
        // focus the place search input so users can type immediately
        try { if (placeInput) { placeInput.focus(); placeInput.select && placeInput.select(); } } catch (e) {}
      }, 80);
    };

    const closeModal = () => {
      if (!mapModal) return;
      mapModal.removeAttribute('open');
      mapModal.style.display = 'none';
      document.body.classList.remove('modal-open');
    };

    locationField.addEventListener('click', openModal);

    mapModalClose?.addEventListener('click', closeModal);
    mapModal.addEventListener('click', (ev) => { if (ev.target === mapModal) closeModal(); });

    // Nominatim search helper (public) — consider swapping to LocationIQ if you have a token
    const nominatimSearch = async (q) => {
      // Include an email parameter per Nominatim usage policy so the service can contact you if needed.
      const email = 'noreply@gomarikina.local';
  // Marikina viewbox: left(lon)=121.02, top(lat)=14.76, right(lon)=121.16, bottom(lat)=14.57
  const viewbox = '121.02,14.76,121.16,14.57';
  const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(q)}&addressdetails=1&limit=5&countrycodes=ph&viewbox=${encodeURIComponent(viewbox)}&bounded=1&email=${encodeURIComponent(email)}`;
      // Retry a couple times for transient network or 429 issues
      for (let attempt = 1; attempt <= 2; attempt++) {
        try {
          console.debug('nominatimSearch attempt', attempt, url);
          const r = await fetch(url, { headers: { 'Accept-Language': 'en' } });
          if (!r.ok) {
            console.warn('Nominatim search returned status', r.status);
            // If it's a client error (4xx other than 429) don't retry
            if (r.status >= 400 && r.status < 500 && r.status !== 429) return [];
            // otherwise retry once
            if (attempt === 2) return [];
            await new Promise(res => setTimeout(res, 250 * attempt));
            continue;
          }
          const body = await r.json().catch(() => null);
          console.debug('nominatimSearch response', body);
          return Array.isArray(body) ? body : [];
        } catch (e) {
          console.warn('Nominatim search error (attempt ' + attempt + ')', e);
          if (attempt === 2) throw e;
          await new Promise(res => setTimeout(res, 250 * attempt));
        }
      }
      return [];
    };

    // Reverse geocode (lat,lng) to a display name using Nominatim
    const nominatimReverse = async (lat, lon) => {
      const email = 'noreply@gomarikina.local';
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&addressdetails=1&email=${encodeURIComponent(email)}`;
      for (let attempt = 1; attempt <= 2; attempt++) {
        try {
          console.debug('nominatimReverse attempt', attempt, url);
          const r = await fetch(url, { headers: { 'Accept-Language': 'en' } });
          if (!r.ok) {
            console.warn('Nominatim reverse returned status', r.status);
            if (r.status >= 400 && r.status < 500 && r.status !== 429) return null;
            if (attempt === 2) return null;
            await new Promise(res => setTimeout(res, 250 * attempt));
            continue;
          }
          const body = await r.json().catch(() => null);
          console.debug('nominatimReverse response', body);
          return body;
        } catch (e) {
          console.warn('Nominatim reverse error (attempt ' + attempt + ')', e);
          if (attempt === 2) return null;
          await new Promise(res => setTimeout(res, 250 * attempt));
        }
      }
      return null;
    };

    // Simple autocomplete dropdown for the search input with keyboard support
    const resultsListId = 'leafletPlaceResults';
    const createResultsContainer = () => {
      let list = document.getElementById(resultsListId);
      if (!list) {
        list = document.createElement('div');
        list.id = resultsListId;
        list.className = 'autocomplete-results';
        // Append to body and position using viewport coordinates so the dropdown
        // aligns correctly even when the input is inside a transformed/modal container.
        list.style.position = 'absolute';
        list.style.zIndex = 2000;
        list.style.minWidth = (placeInput ? placeInput.offsetWidth : 240) + 'px';
        list.style.boxSizing = 'border-box';
        document.body.appendChild(list);
      }
      return list;
    };

    let activeIndex = -1;
    const clearResults = () => {
      const existing = document.getElementById(resultsListId);
      if (existing && existing.parentNode) existing.parentNode.removeChild(existing);
      activeIndex = -1;
    };

    const showResults = (items) => {
  const list = createResultsContainer();
  // position via bounding rect so it sits inside modal nicely. Use page scroll offsets
  // so the dropdown is positioned correctly when appended to document.body.
  const rect = placeInput.getBoundingClientRect();
  const scrollX = window.scrollX || window.pageXOffset || 0;
  const scrollY = window.scrollY || window.pageYOffset || 0;
  list.style.left = (rect.left + scrollX) + 'px';
  list.style.top = (rect.bottom + scrollY) + 'px';
  list.style.width = rect.width + 'px';
      list.innerHTML = '';

      items.forEach((it, idx) => {
        const el = document.createElement('div');
        el.className = 'autocomplete-item';
        el.dataset.index = idx;
        el.textContent = it.display_name || `${it.name || ''} ${it.type || ''}`.trim();
        el.style.padding = '8px 12px';
        el.style.cursor = 'pointer';
        el.tabIndex = 0;
        el.addEventListener('click', () => selectResult(it));
        el.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') selectResult(it); });
        list.appendChild(el);
      });

      if (!items.length) {
        list.innerHTML = '<div style="padding:8px 12px;color:var(--text-muted);">No results</div>';
      }
    };

    const selectResult = (it) => {
      chosenPlace = it;
      // set marker and inputs
      ensureMap();
      try {
        marker.setLatLng([parseFloat(it.lat), parseFloat(it.lon)]);
        map.setView([parseFloat(it.lat), parseFloat(it.lon)], 17);
      } catch (e) {}
      const latIn = document.getElementById('location_lat');
      const lngIn = document.getElementById('location_lng');
      const locInput = document.getElementById('location');
  if (latIn) latIn.value = it.lat;
  if (lngIn) lngIn.value = it.lon;
  if (locInput) locInput.value = it.display_name || '';
  try { marker.unbindPopup && marker.unbindPopup(); } catch (e) {}
  try { if (it.display_name) marker.bindPopup(it.display_name).openPopup(); } catch (e) {}
      clearResults();
      placeInput.value = it.display_name || '';
    };

    let searchTimer = null;
    if (placeInput) {
      placeInput.addEventListener('input', (ev) => {
        const q = (ev.target.value || '').trim();
        if (searchTimer) clearTimeout(searchTimer);
        if (!q) { clearResults(); return; }
        searchTimer = setTimeout(async () => {
          try {
            const res = await nominatimSearch(q);
            showResults(Array.isArray(res) ? res : []);
          } catch (e) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Search failed — try again', { type: 'error' }) : console.warn(e);
          }
        }, 300);
      });

      // keyboard navigation
      placeInput.addEventListener('keydown', (ev) => {
        const list = document.getElementById(resultsListId);
        const items = list ? Array.from(list.querySelectorAll('.autocomplete-item')) : [];
        if (ev.key === 'ArrowDown') {
          ev.preventDefault();
          activeIndex = Math.min(activeIndex + 1, items.length - 1);
          items.forEach((it, i) => it.classList.toggle('active', i === activeIndex));
          items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (ev.key === 'ArrowUp') {
          ev.preventDefault();
          activeIndex = Math.max(activeIndex - 1, 0);
          items.forEach((it, i) => it.classList.toggle('active', i === activeIndex));
          items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (ev.key === 'Enter') {
          ev.preventDefault();
          if (items.length && activeIndex >= 0) {
            items[activeIndex].click();
          } else if (items.length && items[0]) {
            items[0].click();
          }
        } else if (ev.key === 'Escape') {
          clearResults();
        }
      });

      // close results on outside click (take into account results appended to body)
      document.addEventListener('click', (ev) => {
        const list = document.getElementById(resultsListId);
        if (!list) return;
        if (ev.target === placeInput || list.contains(ev.target)) return;
        clearResults();
      });

      // remove results on blur after a small delay to allow click to register
      placeInput.addEventListener('blur', () => setTimeout(clearResults, 200));
    }

    // Use this place button copies the chosen place to the location input and closes modal
    usePlaceBtn?.addEventListener('click', async () => {
      const locInput = document.getElementById('location');
      // If user didn't pick from suggestions but typed an address, attempt a search
      if (!chosenPlace && placeInput && placeInput.value && placeInput.value.trim()) {
        try {
          const res = await nominatimSearch(placeInput.value.trim());
          if (Array.isArray(res) && res.length > 0) {
            chosenPlace = res[0];
          }
        } catch (e) {
          console.warn('Use place fallback search failed', e);
        }
      }

      // If still no chosenPlace, but a marker exists (user clicked/dragged), use its position
      if (!chosenPlace && typeof marker !== 'undefined' && marker) {
        try {
          const pos = marker.getLatLng();
          // ensure marker is within bounds if bounds exist
          if (pos && Number.isFinite(pos.lat) && Number.isFinite(pos.lng)) {
            const rev = await nominatimReverse(pos.lat, pos.lng);
            const display = rev && (rev.display_name || (rev.address && Object.values(rev.address).join(', '))) ? (rev.display_name || '') : '';
            chosenPlace = { lat: pos.lat, lon: pos.lng, display_name: display };
          }
        } catch (e) {
          console.warn('Use place reverse geocode failed', e);
        }
      }

      // Last fallback: if locInput already has a string, use it without coords
      if (!chosenPlace && locInput && locInput.value && locInput.value.trim()) {
        chosenPlace = { lat: '', lon: '', display_name: locInput.value.trim() };
      }

      if (!chosenPlace) {
        if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast('Please pick a place first.', { type: 'error' });
        return;
      }

      if (locInput) locInput.value = chosenPlace.display_name || '';
      const latIn = document.getElementById('location_lat');
      const lngIn = document.getElementById('location_lng');
      if (latIn) latIn.value = chosenPlace.lat || '';
      if (lngIn) lngIn.value = chosenPlace.lon || '';
      const evt = new Event('change', { bubbles: true }); locInput?.dispatchEvent(evt);
      closeModal();
    });

    // Clear selection
    clearBtn?.addEventListener('click', () => {
      chosenPlace = null;
      try { if (marker) marker.setLatLng([14.65, 120.97]); } catch (e) {}
      const latIn = document.getElementById('location_lat');
      const lngIn = document.getElementById('location_lng');
      const locInput = document.getElementById('location');
      if (latIn) latIn.value = '';
      if (lngIn) lngIn.value = '';
      if (locInput) locInput.value = '';
      (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Selection cleared', { type: 'info' }) : void 0;
    });

  }

  // Initialize the create report map picker if present
  initMapPlacePicker().catch(() => {});

  // Informational note if Leaflet doesn't load for some reason
  document.addEventListener('DOMContentLoaded', () => {
    try {
      if (typeof L === 'undefined') {
        if (window.GOMK && window.GOMK.showToast) {
          window.GOMK.showToast('Map services unavailable. Leaflet failed to load.', { type: 'error', duration: 8000 });
        } else {
          console.warn('Map services unavailable. Leaflet failed to load.');
        }
      }
    } catch (e) { /* ignore errors in check */ }
  });

  // Initialize create report functionality if on create-report page
  if (document.getElementById('createReportForm')) {
    initializeCreateReport();
  }

  // Profile Editing Functionality
  const initializeProfileEditing = () => {
    console.log('Profile editing functionality initialized');
    
  const editPasswordBtn = document.getElementById('editPasswordBtn');
  const editMobileBtn = document.getElementById('editMobileBtn');
  const passwordField = document.getElementById('passwordField');
  const mobileField = document.getElementById('mobileField');

    // Helper: call backend to persist a field update
    const saveProfileField = async (field, value) => {
      try {
        const fd = new FormData();
        fd.append('field', field);
        fd.append('value', value);
        const r = await fetch('api/profile_update.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.success) {
          throw new Error(data.message || 'Unable to save changes');
        }
        return data;
      } catch (e) {
        throw e;
      }
    };

    // Edit Password functionality
    if (editPasswordBtn && passwordField) {
      editPasswordBtn.addEventListener('click', () => {
        if (passwordField.readOnly) {
          // Enable editing
          passwordField.readOnly = false;
          passwordField.type = 'password';
          passwordField.value = '';
          passwordField.placeholder = 'Enter new password';
          passwordField.focus();

          // Inject confirm password input BELOW the first input (not beside)
          const group = passwordField.closest('.profile-input-group') || passwordField.parentElement;
          const fieldContainer = group?.parentElement || null; // typically .profile-field
          if (group && fieldContainer && !document.getElementById('passwordConfirmField')) {
            // Ensure the input and its edit button stay on one line
            try { group.style.flexWrap = 'nowrap'; } catch (e) { /* ignore */ }
            // Position the confirm group absolutely so it doesn't affect layout of siblings
            fieldContainer.style.position = fieldContainer.style.position || 'relative';
            const wrap = document.createElement('div');
            wrap.className = 'profile-input-group profile-input-group--confirm';
            wrap.id = 'passwordConfirmWrap';
            wrap.style.position = 'absolute';
            wrap.style.left = group.offsetLeft + 'px';
            wrap.style.width = group.offsetWidth + 'px';
            wrap.style.zIndex = '5';
            // layout confirm input and its toggle side by side
            wrap.style.display = 'grid';
            wrap.style.gridTemplateColumns = '1fr auto';
            wrap.style.alignItems = 'center';
            const computeTop = () => {
              try {
                const rect = group.getBoundingClientRect();
                const parentRect = fieldContainer.getBoundingClientRect();
                const top = rect.top - parentRect.top + group.offsetHeight + 8; // 8px gap
                wrap.style.top = top + 'px';
              } catch (e) {
                wrap.style.top = (group.offsetTop + group.offsetHeight + 8) + 'px';
              }
            };
            computeTop();
            // Recompute on resize while visible (keep horizontally aligned with the input)
            const onResize = () => {
              try {
                wrap.style.left = group.offsetLeft + 'px';
                wrap.style.width = group.offsetWidth + 'px';
              } catch (e) { /* ignore */ }
              computeTop();
            };
            window.addEventListener('resize', onResize);
            wrap._onResize = onResize;
            const confirm = document.createElement('input');
            confirm.type = 'password';
            confirm.className = 'profile-input';
            confirm.id = 'passwordConfirmField';
            confirm.placeholder = 'Confirm new password';
            confirm.autocomplete = 'new-password';
            confirm.style.width = '100%';
            wrap.appendChild(confirm);
            // Add a visibility toggle for confirm password
            if (!document.getElementById('passwordConfirmToggleBtn')) {
              const makeToggle = (input, id) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'auth-field-toggle';
                btn.id = id;
                btn.setAttribute('aria-label', 'Show password');
                btn.setAttribute('aria-pressed', 'false');
                btn.dataset.visible = 'false';
                btn.innerHTML = '<span class="auth-toggle-icon" aria-hidden="true"></span>';
                btn.addEventListener('click', (ev) => {
                  ev.preventDefault();
                  const isVisible = btn.dataset.visible === 'true';
                  input.type = isVisible ? 'password' : 'text';
                  btn.dataset.visible = isVisible ? 'false' : 'true';
                  btn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                  btn.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                });
                return btn;
              };
              wrap.appendChild(makeToggle(confirm, 'passwordConfirmToggleBtn'));
            }
            // Append to field container (absolute positioning avoids reflow)
            fieldContainer.appendChild(wrap);
          }

          // Add a visibility toggle for the main password field (inline with input)
          if (group && !document.getElementById('passwordToggleBtn')) {
            const makeToggle = (input, id) => {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'auth-field-toggle';
              btn.id = id;
              btn.setAttribute('aria-label', 'Show password');
              btn.setAttribute('aria-pressed', 'false');
              btn.dataset.visible = 'false';
              btn.innerHTML = '<span class="auth-toggle-icon" aria-hidden="true"></span>';
              btn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const isVisible = btn.dataset.visible === 'true';
                input.type = isVisible ? 'password' : 'text';
                btn.dataset.visible = isVisible ? 'false' : 'true';
                btn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                btn.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
              });
              return btn;
            };
            const editBtn = group.querySelector('.profile-edit-btn');
            const toggle = makeToggle(passwordField, 'passwordToggleBtn');
            if (editBtn && editBtn.parentElement === group) {
              group.insertBefore(toggle, editBtn);
            } else {
              group.appendChild(toggle);
            }
          }
          
          // Change button to save
          editPasswordBtn.innerHTML = `
            <svg viewBox="0 0 24 24">
              <path d="M20 6L9 17l-5-5"/>
            </svg>
          `;
          editPasswordBtn.title = 'Save password';
        } else {
          // Save changes
          const newPassword = passwordField.value.trim();
          const confirmEl = document.getElementById('passwordConfirmField');
          const confirmVal = (confirmEl?.value || '').trim();

          // Password policy: 8+ chars, 1 uppercase, 1 number, 1 special, no spaces
          const strongRe = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9])(?!.*\s).{8,}$/;
          if (!strongRe.test(newPassword)) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Password must be 8+ characters, include an uppercase letter, a number, a special character, and no spaces.', { type: 'error' }) : alert('Password must be 8+ characters, include an uppercase letter, a number, a special character, and no spaces.');
            passwordField.focus();
            return;
          }
          if (newPassword !== confirmVal) {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Passwords do not match. Please confirm your new password.', { type: 'error' }) : alert('Passwords do not match. Please confirm your new password.');
            confirmEl?.focus();
            return;
          }

          if (newPassword.length >= 8) {
            // Persist to backend
            const fd = new FormData();
            fd.append('field', 'password');
            fd.append('value', newPassword);
            fd.append('password_confirm', confirmVal);
            fetch('api/profile_update.php', { method: 'POST', body: fd, credentials: 'same-origin' })
              .then(r => r.json().catch(() => ({})).then(data => ({ ok: r.ok, data })))
              .then(({ ok, data }) => {
                if (!ok || !data?.success) throw new Error(data?.message || 'Unable to save changes');
                return data;
              })
              .then(() => {
                passwordField.readOnly = true;
                passwordField.type = 'password';
                passwordField.value = 'M*************';
                passwordField.placeholder = '';
                // Remove confirm input and wrapper
                const c = document.getElementById('passwordConfirmField');
                const w = document.getElementById('passwordConfirmWrap');
                if (w) { if (w._onResize) window.removeEventListener('resize', w._onResize); w.remove(); } else if (c) c.remove();
                // Remove visibility toggles
                const t1 = document.getElementById('passwordToggleBtn');
                if (t1) t1.remove();
                const t2 = document.getElementById('passwordConfirmToggleBtn');
                if (t2) t2.remove();
                editPasswordBtn.innerHTML = `
                  <svg viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                `;
                editPasswordBtn.title = 'Edit password';
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Password updated successfully!', { type: 'success' }) : alert('Password updated successfully!');
              })
              .catch((err) => {
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast(err.message || 'Failed to update password', { type: 'error' }) : alert(err.message || 'Failed to update password');
                passwordField.focus();
              });
          } else {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Password must be at least 8 characters long', { type: 'info' }) : alert('Password must be at least 8 characters long');
            passwordField.focus();
          }
        }
      });
    }

    // Edit Mobile Number functionality
    if (editMobileBtn && mobileField) {
      editMobileBtn.addEventListener('click', () => {
        if (mobileField.readOnly) {
          // Enable editing
          mobileField.readOnly = false;
          if (!mobileField.value || !/^\+63\d{0,10}$/.test(mobileField.value.replace(/\s+/g, ''))) {
            mobileField.value = '+63';
          }
          mobileField.focus();

          // Enforce +63 prefix and 10 digits after, no spaces while editing
          const ensurePrefix = () => {
            let v = (mobileField.value || '').replace(/\s+/g, '');
            if (!v.startsWith('+63')) v = '+63' + v.replace(/^[+]*(63)?/, '');
            const rest = v.slice(3).replace(/\D+/g, '').slice(0, 10);
            mobileField.value = '+63' + rest;
          };
          mobileField.addEventListener('input', ensurePrefix);
          mobileField.addEventListener('blur', ensurePrefix);
          mobileField.addEventListener('keydown', (e) => {
            const pos = mobileField.selectionStart || 0;
            if ((e.key === 'Backspace' && pos <= 3) || (e.key === 'Delete' && pos < 3)) {
              e.preventDefault();
              mobileField.setSelectionRange(3, 3);
            }
          });
          
          // Change button to save
          editMobileBtn.innerHTML = `
            <svg viewBox="0 0 24 24">
              <path d="M20 6L9 17l-5-5"/>
            </svg>
          `;
          editMobileBtn.title = 'Save mobile number';
        } else {
          // Save changes
          const newMobile = mobileField.value.trim();
          if (/^\+63\d{10}$/.test(newMobile)) {
            saveProfileField('mobile', newMobile)
              .then((data) => {
                mobileField.readOnly = true;
                if (data && data.value) mobileField.value = data.value;
                editMobileBtn.innerHTML = `
                  <svg viewBox=\"0 0 24 24\">
                    <path d=\"M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\"></path>
                    <path d=\"m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\"></path>
                  </svg>
                `;
                editMobileBtn.title = 'Edit mobile number';
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Mobile number updated successfully!', { type: 'success' }) : alert('Mobile number updated successfully!');
              })
              .catch((err) => {
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast(err.message || 'Failed to update mobile number', { type: 'error' }) : alert(err.message || 'Failed to update mobile number');
                mobileField.focus();
              });
          } else {
            (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Mobile must be +63 followed by 10 digits.', { type: 'info' }) : alert('Mobile must be +63 followed by 10 digits.');
            mobileField.focus();
          }
        }
      });
    }

    // Handle escape key to cancel editing
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (passwordField && !passwordField.readOnly) {
          passwordField.readOnly = true;
          passwordField.value = 'M*************';
          passwordField.placeholder = '';
          const c = document.getElementById('passwordConfirmField');
          const w = document.getElementById('passwordConfirmWrap');
          if (w) { if (w._onResize) window.removeEventListener('resize', w._onResize); w.remove(); } else if (c) c.remove();
          const t1 = document.getElementById('passwordToggleBtn');
          if (t1) t1.remove();
          const t2 = document.getElementById('passwordConfirmToggleBtn');
          if (t2) t2.remove();
          
          editPasswordBtn.innerHTML = `
            <svg viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
          `;
          editPasswordBtn.title = 'Edit password';
        }
        
        if (mobileField && !mobileField.readOnly) {
          mobileField.readOnly = true;
          // Keep current value as-is; if invalid, user can re-edit later
          
          editMobileBtn.innerHTML = `
            <svg viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
          `;
          editMobileBtn.title = 'Edit mobile number';
        }
      }
    });
  };

  // Initialize profile editing functionality if on profile page
  if (document.getElementById('editPasswordBtn') || document.getElementById('editMobileBtn')) {
    initializeProfileEditing();
  }

  // Profile Login Functionality
  const initializeProfileLogin = () => {
    console.log('Profile login functionality initialized');
    
    const profileContent = document.getElementById('profileContent');
    const loginForm = document.querySelector('.auth-form-login');
    
    if (profileContent && loginForm) {
      const authCardWrapper = loginForm.closest('.auth-card');
      const authSection = loginForm.closest('.auth-content');

      // Hide profile content initially
      profileContent.style.display = 'none';
      
      // Add login form submit handler
      loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const email = loginForm.querySelector('input[type="email"]').value;
        const password = loginForm.querySelector('input[type="password"]').value;
        
        // Simple validation (you can replace this with actual authentication)
        if (email && password) {
          // Show profile content
          profileContent.style.display = 'block';
          
          // Hide login form
          loginForm.style.display = 'none';

          if (authCardWrapper) {
            authCardWrapper.style.display = 'none';
          }

          if (authSection) {
            authSection.style.display = 'none';
          }
          
          // Show success message
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Login successful! Welcome to your profile.', { type: 'success' }) : alert('Login successful! Welcome to your profile.');
          
          console.log('User logged in successfully');
        } else {
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please enter both email and password', { type: 'info' }) : alert('Please enter both email and password');
        }
      });
    }
  };

  // Initialize profile login functionality if on profile page
  if (document.getElementById('profileContent')) {
    initializeProfileLogin();
  }

  // Create Report Login Functionality
  const initializeCreateReportLogin = () => {
    console.log('Create report login functionality initialized');
    
    const createReportContent = document.getElementById('createReportContent');
    const loginForm = document.querySelector('.auth-form-login');
    
    if (createReportContent && loginForm) {
      const authCardWrapper = loginForm.closest('.auth-card');
      const authSection = loginForm.closest('.auth-content');

      // Hide create report content initially
      createReportContent.style.display = 'none';
      
      // Add login form submit handler
      loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const email = loginForm.querySelector('input[type="email"]').value;
        const password = loginForm.querySelector('input[type="password"]').value;
        
        // Simple validation (you can replace this with actual authentication)
        if (email && password) {
          // Show create report content
          createReportContent.style.display = 'block';
          
          // Hide login form
          loginForm.style.display = 'none';

          if (authCardWrapper) {
            authCardWrapper.style.display = 'none';
          }

          if (authSection) {
            authSection.style.display = 'none';
          }
          
          // Show success message
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Login successful! You can now create a report.', { type: 'success' }) : alert('Login successful! You can now create a report.');
          
          console.log('User logged in successfully for create report');
        } else {
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Please enter both email and password', { type: 'info' }) : alert('Please enter both email and password');
        }
      });
    }
  };

  // Initialize create report login functionality if on create-report page
  if (document.getElementById('createReportContent')) {
    initializeCreateReportLogin();
  }

  // Smooth scrolling for in-page anchors and on-hash load
  // Notes:
  // - CSS `html { scroll-behavior: smooth; }` animates only same-document scrolls.
  // - When navigating from another page to index.php#reports, browsers typically jump; we re-apply a smooth scroll after load for consistency.
  (function setupSmoothScroll() {
    const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const behavior = prefersReduced ? 'auto' : 'smooth';

    const getTarget = (hash) => {
      if (!hash) return null;
      const id = decodeURIComponent(String(hash).replace(/^#/, ''));
      if (!id) return null;
      return document.getElementById(id);
    };

    const smoothScrollTo = (el) => {
      if (!el) return;
      // If you add a sticky header later, set a non-zero offset here.
      el.scrollIntoView({ behavior, block: 'start' });
    };

    // Same-page hash links like #top, #reports
    const samePageAnchors = Array.from(document.querySelectorAll('a[href^="#"]'));
    samePageAnchors.forEach((a) => {
      const href = a.getAttribute('href') || '';
      const target = getTarget(href);
      if (!target) return;

      a.addEventListener('click', (e) => {
        // Ignore modified clicks and non-left clicks
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        e.preventDefault();

        // Close mobile nav if open so scroll isn't blocked
        if (document.body.classList.contains('nav-open')) {
          document.body.classList.remove('nav-open');
          const scrim = document.querySelector('[data-nav-scrim]');
          scrim?.setAttribute('hidden', 'hidden');
        }

        smoothScrollTo(target);

        // Update the hash without triggering a jump
        if (history.pushState) {
          history.pushState(null, '', href);
        } else {
          window.location.hash = href;
        }
      });
    });

    // If we arrived with a hash (e.g., index.php#reports), re-apply a smooth scroll
      if (window.location.hash) {
        const target = getTarget(window.location.hash);
        if (target) {
          requestAnimationFrame(() => smoothScrollTo(target));
        }
      }
    })();

    
  });
