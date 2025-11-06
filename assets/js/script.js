/*
 * App UI Script
 *
 * Sections overview (search these headers to navigate):
 * - Announcements modal and carousel
 * - Mobile navigation and navbar behavior
 * - Notifications panel (fetch/list/mark read)
 * - Reports UI helpers (layout/animations)
 * - Admin inline actions (status updates)
 * - Auth card toggles and validation helpers
 *
 * Conventions:
 * - Progressive enhancement only; page works without JS.
 * - data-* attributes as DOM hooks; minimal global state.
 */

// Announcements modal
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

    // Initialize simple slide paginator inside announcements modal
    const initAnnouncementsModalCarousel = () => {
      const root = document.getElementById('announcementsModalCarousel');
      if (!root) return;
      const slides = Array.from(root.querySelectorAll('.ann-modal-slide'));
      const dots = Array.from(root.querySelectorAll('.ann-indicator'));
      if (!slides.length || !dots.length) return;
      let index = slides.findIndex(s => s.classList.contains('active'));
      if (index < 0) index = 0;
      
      const show = (i) => {
        i = (i + slides.length) % slides.length;
        if (i === index) return; // No change needed
        
        const oldSlide = slides[index];
        const newSlide = slides[i];
        
        // Add exiting class to current slide for smooth animation
        oldSlide.classList.add('slide-exiting');
        
        // Remove active from current slide after transition completes
        setTimeout(() => {
          oldSlide.classList.remove('active', 'slide-exiting');
          oldSlide.setAttribute('aria-hidden', 'true');
        }, 450); // Match CSS transition duration
        
        // Activate new slide immediately
        newSlide.classList.add('active');
        newSlide.setAttribute('aria-hidden', 'false');
        
        // Update indicators
        dots.forEach((d, k) => { 
          d.classList.toggle('active', k === i); 
          if (k === i) d.setAttribute('aria-current','true'); 
          else d.removeAttribute('aria-current'); 
        });
        
        index = i;
      };
      
      dots.forEach((btn, i) => btn.addEventListener('click', (ev) => { ev.preventDefault(); show(i); }));
      // Keyboard support when modal focused
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { e.preventDefault(); show(index - 1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); show(index + 1); }
      });
      // Ensure first slide shown on open
      show(index);
    };

    // Run after modal opens (minor delay to ensure DOM present)
    modal.addEventListener('transitionend', (e) => {
      if (e.target === modal && modal.hasAttribute('open')) {
        initAnnouncementsModalCarousel();
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
    // Choose a context-aware label for the OK button
    let okLabel = 'Yes';
    try {
      const actionEl = form.querySelector('input[name="action"]');
      const actionVal = (actionEl && actionEl.value) ? String(actionEl.value) : '';
      if (actionVal === 'archive_announcement' || /archive/i.test(msg)) {
        okLabel = 'Archive';
      } else if (/delete|remove/i.test(msg) || /delete/i.test(actionVal)) {
        okLabel = 'Delete';
      }
    } catch (e) { /* ignore */ }
    ok = await window.GOMK.confirmDialog(msg, { okText: okLabel, cancelText: 'Cancel' });
  } else {
    ok = window.confirm(msg);
  }
  if (ok) form.submit();
});

// Initialize simple announcements carousel (non-invasive)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.announcements-carousel').forEach(carousel => {
    const track = carousel.querySelector('.carousel-track');
    const prev = carousel.querySelector('.carousel-prev');
    const next = carousel.querySelector('.carousel-next');
    if (!track) return;

    // Ensure track is focusable for keyboard users
    if (!track.hasAttribute('tabindex')) track.setAttribute('tabindex', '0');

    const computeStep = () => {
      const first = track.querySelector('.public-announcement-card');
      const gap = parseInt(getComputedStyle(track).gap) || 20;
      return first ? Math.round(first.getBoundingClientRect().width + gap) : Math.round(track.clientWidth * 0.9);
    };

    // Layout carousel so exactly one card is centered per view.
    const layoutCarousel = () => {
      const cards = Array.from(track.querySelectorAll('.public-announcement-card'));
      if (!cards.length) return;
      // Choose card width as 86% of track or max 920px to keep cards readable
      const desired = Math.min(920, Math.round(track.clientWidth * 0.86));
      cards.forEach(c => {
        c.style.width = desired + 'px';
        c.style.flex = '0 0 auto';
      });
      // Center the active card by adding side padding so the first/last can snap to center
      const sidePad = Math.max(0, Math.round((track.clientWidth - desired) / 2));
      track.style.paddingLeft = sidePad + 'px';
      track.style.paddingRight = sidePad + 'px';
    };

    const scrollByStep = (dir) => {
      const step = computeStep();
      track.scrollBy({ left: dir * step, behavior: 'smooth' });
    };

    if (prev) prev.addEventListener('click', () => scrollByStep(-1));
    if (next) next.addEventListener('click', () => scrollByStep(1));

    // Hide or disable nav buttons when not needed and update on scroll/resize
    const updateCarouselNav = () => {
      if (!prev || !next) return;
      const canScroll = track.scrollWidth > track.clientWidth + 1; // allow tiny rounding
      // Show/hide buttons based on overflow
      prev.hidden = next.hidden = !canScroll;
      prev.disabled = !canScroll || track.scrollLeft <= 0;
      // Consider near-end as scrolled to end
      const atEnd = Math.abs(track.scrollLeft + track.clientWidth - track.scrollWidth) < 2;
      next.disabled = !canScroll || atEnd;
    };

    // Update on user scroll and on resize so buttons reflect current state
    track.addEventListener('scroll', () => requestAnimationFrame(updateCarouselNav));
    // On resize, recompute layout (card widths and track padding) then update nav
    window.addEventListener('resize', () => requestAnimationFrame(() => { layoutCarousel(); updateCarouselNav(); }));
    // Recompute layout when images load in the track to avoid layout flashes
    Array.from(track.querySelectorAll('img')).forEach(img => {
      if (!img.complete) img.addEventListener('load', () => requestAnimationFrame(() => { layoutCarousel(); updateCarouselNav(); }));
    });
    // Initial layout + nav update
    setTimeout(() => { layoutCarousel(); updateCarouselNav(); }, 30);

    // Keyboard support on the carousel container
    carousel.addEventListener('keydown', (ev) => {
      if (ev.key === 'ArrowLeft') { ev.preventDefault(); if (prev) prev.click(); }
      if (ev.key === 'ArrowRight') { ev.preventDefault(); if (next) next.click(); }
    });

    // Resize observer to adjust when layout changes
    let ro;
    try {
      ro = new ResizeObserver(() => { /* noop - computeStep reads sizes live */ });
      ro.observe(track);
    } catch (e) { /* ResizeObserver may not be available; safe to ignore */ }
  });
});

// Lightweight vanilla carousel for #announcementsCarousel when Bootstrap isn't present
(function initAnnouncementsCarousel(){
  if (typeof document === 'undefined') return;
  document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('announcementsCarousel');
    if (!root) return;
    // If Bootstrap's Carousel is present, prefer it
    if (window.bootstrap && typeof window.bootstrap.Carousel === 'function') return;

  const inner = root.querySelector('.carousel-inner');
    if (!inner) return;
  const items = Array.from(inner.querySelectorAll('.carousel-item'));
  // Support both legacy dot indicators and new numeric pagination
  const dotIndicators = Array.from(root.querySelectorAll('.carousel-indicators button'));
  const pageIndicators = Array.from(root.querySelectorAll('.carousel-pagination .pager-btn[data-index]'));
  const indicators = pageIndicators.length ? pageIndicators : dotIndicators;
  const btnPrev = root.querySelector('.carousel-pagination .pager-btn[data-prev]') || root.querySelector('.carousel-control-prev');
  const btnNext = root.querySelector('.carousel-pagination .pager-btn[data-next]') || root.querySelector('.carousel-control-next');
    if (!items.length) return;

    let current = items.findIndex(i => i.classList.contains('active'));
    if (current < 0) current = 0;

    // Make root focusable so keyboard listeners work
    if (!root.hasAttribute('tabindex')) {
      try { root.setAttribute('tabindex', '0'); } catch (e) { /* ignore */ }
    }

    // Helper to adjust carousel height smoothly
    const adjustCarouselHeight = () => {
      const activeItem = items.find(i => i.classList.contains('active'));
      if (activeItem) {
        // Force reflow to get accurate height
        activeItem.style.display = 'block';
        const height = activeItem.offsetHeight;
        inner.style.height = height + 'px';
      }
    };

    const setDisabled = (el, disabled) => {
      if (!el) return;
      try {
        el.disabled = !!disabled;
        if (disabled) el.setAttribute('aria-disabled', 'true');
        else el.removeAttribute('aria-disabled');
      } catch (e) { /* ignore */ }
    };

    const updateNavState = () => {
      setDisabled(btnPrev, current <= 0);
      setDisabled(btnNext, current >= items.length - 1);
    };

    const show = (index, resetTimer = true) => {
      // Clamp – no looping
      if (index < 0 || index >= items.length) {
        updateNavState();
        return false; // no change
      }
      if (index === current) { updateNavState(); return false; }
      
      // Reset auto-slide timer when manually navigating
      if (resetTimer && autoSlideInterval) {
        stopAutoSlide();
        startAutoSlide();
      }
      
      const oldItem = items[current];
      const newItem = items[index];
      
      // Measure new item's height BEFORE transition starts
      newItem.style.position = 'absolute';
      newItem.style.visibility = 'hidden';
      newItem.style.display = 'block';
      const newHeight = newItem.offsetHeight;
      newItem.style.position = '';
      newItem.style.visibility = '';
      newItem.style.display = '';
      
      // Set the target height immediately
      inner.style.height = newHeight + 'px';
      
      // Add exiting class to current item for smooth animation
      oldItem.classList.add('carousel-item-exiting');
      
      // Remove active from current item after transition completes
      setTimeout(() => {
        oldItem.classList.remove('active', 'carousel-item-exiting');
        oldItem.setAttribute('aria-hidden', 'true');
      }, 500); // Match CSS transition duration
      
      // Activate new item immediately
      newItem.classList.add('active');
      newItem.setAttribute('aria-hidden', 'false');
      
      // Update indicators (numeric or dots)
      indicators.forEach((btn, i) => {
        const isActive = i === index;
        if (isActive) {
          btn.classList.add('active');
          btn.setAttribute('aria-current', 'true');
        } else {
          btn.classList.remove('active');
          btn.removeAttribute('aria-current');
        }
      });
      current = index;
      updateNavState();
      return true;
    };
    
    // Set initial height
    adjustCarouselHeight();

    // Prev / Next handlers
  btnPrev && btnPrev.addEventListener('click', (e) => { e.preventDefault(); show(current - 1); });
  btnNext && btnNext.addEventListener('click', (e) => { e.preventDefault(); show(current + 1); });

    // Indicators (numeric buttons carry data-index; dots rely on position)
    indicators.forEach((btn, i) => btn.addEventListener('click', (e) => {
      e.preventDefault();
      const idxAttr = btn.getAttribute('data-index');
      const targetIndex = idxAttr !== null ? parseInt(idxAttr, 10) : i;
      show(targetIndex);
    }));

    // Keyboard navigation
    root.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') { e.preventDefault(); show(current - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); show(current + 1); }
    });

    // Touch swipe support
    let touchStartX = null;
    inner.addEventListener('touchstart', (e) => { touchStartX = e.touches && e.touches[0] ? e.touches[0].clientX : null; }, { passive: true });
    inner.addEventListener('touchend', (e) => {
      if (touchStartX === null) return;
      const endX = e.changedTouches && e.changedTouches[0] ? e.changedTouches[0].clientX : null;
      if (endX === null) return;
      const dx = endX - touchStartX;
      if (Math.abs(dx) > 40) {
        if (dx < 0) show(current + 1); else show(current - 1);
      }
      touchStartX = null;
    });

    // Auto-slide functionality
    let autoSlideInterval = null;
    const startAutoSlide = () => {
      stopAutoSlide();
      const interval = parseInt(root.getAttribute('data-bs-interval')) || 10000;
      autoSlideInterval = setInterval(() => {
        const advanced = show(current + 1, false);
        // Stop auto-slide when reaching the last slide
        if (!advanced) stopAutoSlide();
      }, interval);
    };

    const stopAutoSlide = () => {
      if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
        autoSlideInterval = null;
      }
    };

    // Pause auto-slide on hover or when user interacts
    root.addEventListener('mouseenter', stopAutoSlide);
    root.addEventListener('mouseleave', startAutoSlide);
    inner.addEventListener('touchstart', stopAutoSlide);
    inner.addEventListener('touchend', () => { setTimeout(startAutoSlide,10000); });

    // Adjust height on window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(adjustCarouselHeight, 150);
    });

    // Ensure initial state and start auto-slide
    show(current, false); // Don't reset timer on initial load; also updates nav
    if (items.length > 1) {
      startAutoSlide();
    }
  });
})();

/* --- Session keep-alive (prevents unexpected logouts during long browsing) --- */
(function initSessionKeepAlive(){
  try {
    // Only run on same-origin pages; ping every 5 minutes
    // Use a relative default (api/ping.php) so it works when the app is served under a subfolder like /gomarikina/
    const PING_URL = (window.GOMK && window.GOMK.PING_URL) || 'api/ping.php';
    const INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

    const ping = () => {
      // Use keepalive so it can fire during page unload too (best effort)
      fetch(PING_URL, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        keepalive: true,
        headers: { 'Accept': 'application/json' }
      }).then(() => {
        // Optionally refresh session cookie via a lightweight no-op by touching document.cookie (no value change) —
        // browsers will keep the session cookie alive due to server Set-Cookie on ping.
      }).catch(() => {/* silent */});
    };

    // Initial delayed ping, then interval
    setTimeout(ping, 20 * 1000); // first ping after 20s to avoid burst on load
    setInterval(ping, INTERVAL_MS);

    // Also ping when page becomes visible again after being hidden for a while
    document.addEventListener('visibilitychange', () => { if (!document.hidden) ping(); });
    window.addEventListener('focus', ping);
  } catch (e) { /* ignore */ }
})();

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

  // Smooth pagination transitions for Announcements on announcements.php
  (function initAnnouncementsPager() {
    const announcementsSection = document.getElementById('announcements');
    let list = document.getElementById('announcementsList');
    let pager = document.getElementById('announcementsPager');
    if (!announcementsSection || !list || !pager) return; // only on announcements page

    const attach = () => {
      if (!pager) return;
      const links = Array.from(pager.querySelectorAll('a.pager-btn'));
      links.forEach((a) => a.addEventListener('click', onClick));
    };

    const onClick = (e) => {
      const a = e.currentTarget;
      const disabled = a.getAttribute('aria-disabled') === 'true';
      if (disabled) { e.preventDefault(); return; }
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return; // allow new-tab
      e.preventDefault();
      swapTo(a.href);
    };

    const swapTo = async (url) => {
      try {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextList = doc.getElementById('announcementsList');
        const nextPager = doc.getElementById('announcementsPager');
        if (!nextList) { window.location.href = url; return; }

        const doSwap = () => {
          try { list.replaceWith(nextList); } catch (e) {}
          if (pager && nextPager) { try { pager.replaceWith(nextPager); } catch (e) {} }
          // Re-init references after swap
          const newList = document.getElementById('announcementsList');
          const newPager = document.getElementById('announcementsPager');
          if (newList) list = newList;
          if (newPager) pager = newPager;
          // Smooth scroll back to section
          announcementsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          // Focus first card for accessibility
          newList?.querySelector('.announcement-card')?.focus?.();
          // Re-attach listeners for the new pager
          attach();
        };

        if (document.startViewTransition) {
          document.startViewTransition(doSwap);
        } else {
          try { list.classList.add('is-out'); } catch (err) {}
          setTimeout(doSwap, 120);
        }

        try { history.pushState({}, '', url); } catch (err) {}
      } catch (err) {
        window.location.href = url;
      }
    };

    window.addEventListener('popstate', () => {
      swapTo(location.href);
    });

    attach();
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
      
      // Check if device is offline/unavailable
      if (data.error || data.status === 'offline') {
        // Dispatch unavailable status to update UI
        dispatchUpdate({ 
          barangay: currentBrgy, 
          latest: { wl: null, aqi: null, t: null, h: null, ts: data.timestamp },
          status: 'offline',
          message: data.message || 'Device unavailable'
        });
        return;
      }
      
      if (data.status !== 'online' && data.status !== 'degraded') return;

      const wl = computeWaterAlertLevel(Number(data.waterPercent || data.waterLevel || 0)).level;
      const aqi = Number(data.airQuality);
      const t   = Number(data.temperature);
      const h   = Number(data.humidity);
      const ts  = data.timestamp || new Date().toISOString();

      const hist = loadHistoryFor(currentBrgy);
      const add = (arr, v) => { arr.push(Number.isFinite(v) ? v : null); if (arr.length > MAX_POINTS) arr.shift(); };
      const addTime = (arr, v) => { arr.push(v); if (arr.length > MAX_POINTS) arr.shift(); };

      add(hist.water, wl); add(hist.air, aqi); add(hist.temp, t); add(hist.humid, h); addTime(hist.times, ts);
      saveHistoryFor(currentBrgy, hist);

      dispatchUpdate({ barangay: currentBrgy, latest: { wl, aqi, t, h, ts }, history: hist, status: 'online' });
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

  /* ========================================================================
     MASONRY GRID LAYOUT FOR REPORTS
     ========================================================================
     Purpose: Implements Masonry.js layout to display report cards in a
              responsive grid. Ensures cards expand vertically without affecting
              entire rows, providing a Pinterest-like layout.
     
     Features:
     - Auto-initializes on page load
     - Re-initializes on page navigation (back/forward, tab switching)
     - Handles browser cache scenarios
     - Automatically recalculates on window resize
     ======================================================================== */
  (function initMasonryResponsive(){
    if (!reportsList) return;

    // Configuration constants
    const DESIRED_COLS = 3;        // Target: 3 columns for optimal viewing
    const MIN_CARD_WIDTH = 210;    // Minimum card width before reducing columns
    const WIDTH_FUDGE = 24;        // Extra padding for scrollbars/rounding errors

    // State variables
    let masonry = null;
    let imagesLoadedLib = null;
    let resizeTimer = null;

    // ========================================================================
    // HELPER FUNCTIONS
    // ========================================================================

    /**
     * Dynamically loads external JavaScript libraries
     * @param {string} src - URL of the script to load
     */
    const loadScript = (src) => new Promise((resolve, reject) => {
      // Skip if already loaded
      if (document.querySelector(`script[src="${src}"]`)) return resolve();
      
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('Failed to load ' + src));
      document.head.appendChild(script);
    });

    /**
     * Ensures Masonry.js and imagesLoaded libraries are loaded
     * Loads from CDN if not already available
     */
    const ensureLibs = async () => {
      if (typeof imagesLoaded === 'undefined') {
        await loadScript('https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js');
      }
      if (typeof Masonry === 'undefined') {
        await loadScript('https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js');
      }
      imagesLoadedLib = window.imagesLoaded;
    };

    /**
     * Calculates optimal column width based on container size
     * Returns: { cols, colWidth, gap }
     */
    const computeColMetrics = () => {
      const gap = parseInt(getComputedStyle(reportsList).gap || 24, 10) || 24;
      const containerWidth = Math.max(320, 
        reportsList.clientWidth || 
        reportsList.offsetWidth || 
        document.documentElement.clientWidth
      );
      
      // Start with 3 columns, reduce if cards would be too narrow
      let cols = DESIRED_COLS;
      const widthFor = (c) => Math.floor(((containerWidth - WIDTH_FUDGE) - gap * (c - 1)) / c);
      let colWidth = widthFor(cols);
      
      while (cols > 1 && colWidth < MIN_CARD_WIDTH) {
        cols -= 1;
        colWidth = widthFor(cols);
      }
      
      return { cols, colWidth, gap };
    };

    /**
     * Applies calculated column width to all report cards
     */
    const applyColMetrics = ({ colWidth }) => {
      Array.from(reportsList.querySelectorAll('.report-card')).forEach((card) => {
        card.style.width = colWidth + 'px';
      });
      // Update Masonry if already initialized
      if (masonry) {
        masonry.options.columnWidth = colWidth;
      }
    };

    // ========================================================================
    // MASONRY ENABLE/DISABLE FUNCTIONS
    // ========================================================================

    /**
     * Disables and cleans up Masonry instance
     * Removes all positioning styles and observers
     */
    const disable = () => {
      // Disconnect observers and destroy Masonry instance
      if (masonry) {
        try {
          if (masonry.__gomkObserver) masonry.__gomkObserver.disconnect();
          if (masonry.__gomkResizeObserver) masonry.__gomkResizeObserver.disconnect();
          masonry.destroy();
        } catch (e) { /* ignore errors */ }
        masonry = null;
      }
      
      // Clean up global references
      window.__gomkMasonry = null;
      try { delete window.__gomkScheduleMasonryLayout; } catch (e) {}
      
      // Remove Masonry class and reset card styles
      if (reportsList) {
        reportsList.classList.remove('gomk-masonry-active');
        Array.from(reportsList.querySelectorAll('.report-card')).forEach((card) => {
          card.style.removeProperty('width');
          card.style.removeProperty('position');
          card.style.removeProperty('top');
          card.style.removeProperty('left');
        });
      }
    };

    /**
     * Enables and initializes Masonry layout
     * Loads libraries, calculates dimensions, and sets up observers
     */
    const enable = async () => {
      if (!reportsList) return;

      // Clean up any existing instance first (for page navigation scenarios)
      disable();

      // Load required libraries (Masonry.js and imagesLoaded)
      try {
        await ensureLibs();
      } catch (e) {
        // Gracefully fail: fall back to CSS grid layout
        return;
      }

      // Calculate and apply column metrics
      const metrics = computeColMetrics();
      applyColMetrics(metrics);

      // Add class to switch from CSS grid to block layout (required for Masonry)
      reportsList.classList.add('gomk-masonry-active');

      // Initialize Masonry instance
      masonry = new Masonry(reportsList, {
        itemSelector: '.report-card',
        columnWidth: metrics.colWidth,
        percentPosition: false,      // Use pixel positioning
        gutter: metrics.gap,         // Spacing between cards
        horizontalOrder: true,       // Fill rows left-to-right
        transitionDuration: 0        // No animation
      });
      
      // Store globally for other modules to access
      window.__gomkMasonry = masonry;

      // ========================================================================
      // LAYOUT SCHEDULING (Performance optimization)
      // ========================================================================
      
      /**
       * Schedules Masonry layout update using requestAnimationFrame
       * Prevents layout thrashing when multiple updates occur rapidly
       */
      let layoutScheduled = false;
      const scheduleLayout = () => {
        if (!masonry || layoutScheduled) return;
        layoutScheduled = true;
        requestAnimationFrame(() => {
          layoutScheduled = false;
          try {
            if (masonry) masonry.layout();
          } catch (e) { /* ignore */ }
        });
      };

      // Expose scheduleLayout for other modules (filters, modals, etc.)
      window.__gomkScheduleMasonryLayout = scheduleLayout;

      /**
       * Recomputes column widths and triggers layout update
       * Called on window resize or when container size changes
       */
      window.__gomkRecomputeMasonryCols = () => {
        if (!masonry) return;
        const newMetrics = computeColMetrics();
        applyColMetrics(newMetrics);
        scheduleLayout();
      };

      // ========================================================================
      // OBSERVERS FOR AUTOMATIC LAYOUT UPDATES
      // ========================================================================

      // Wait for images to load, then trigger layout
      imagesLoadedLib(reportsList, () => scheduleLayout());

      // Observe DOM changes (cards added/removed/hidden)
      // NOTE: Only observe childList to avoid infinite loops from Masonry's
      // own inline style updates
      const mutationObserver = new MutationObserver(() => scheduleLayout());
      mutationObserver.observe(reportsList, { childList: true, subtree: true });
      masonry.__gomkObserver = mutationObserver;

      // Observe card size changes (e.g., when "See more" expands content)
      try {
        const resizeObserver = new ResizeObserver(() => scheduleLayout());
        Array.from(reportsList.querySelectorAll('.report-card')).forEach((card) => {
          resizeObserver.observe(card);
        });
        masonry.__gomkResizeObserver = resizeObserver;
      } catch (e) {
        // ResizeObserver not available in very old browsers
      }
    };

    // ========================================================================
    // INITIALIZATION AND REINITIALIZATION
    // ========================================================================

    /**
     * Main function to disable old instance and enable fresh one
     * Used when page is navigated to ensure clean state
     */
    const reinitialize = () => {
      if (!reportsList) return;
      disable();
      // Small delay ensures cleanup completes before reinitializing
      setTimeout(() => {
        enable().then(() => {
          // Recompute after initialization completes
          if (typeof window.__gomkRecomputeMasonryCols === 'function') {
            window.__gomkRecomputeMasonryCols();
          }
        }).catch(() => { /* ignore */ });
      }, 50);
    };

    /**
     * Verifies Masonry state is consistent
     * Detects broken states (class present but no instance, or vice versa)
     */
    const verifyMasonry = () => {
      if (!reportsList) return;
      
      const hasCards = reportsList.querySelectorAll('.report-card').length > 0;
      const hasActiveClass = reportsList.classList.contains('gomk-masonry-active');
      const hasInstance = masonry !== null && window.__gomkMasonry !== null;
      
      // State mismatch detected - reinitialize
      if (hasCards && ((hasActiveClass && !hasInstance) || (!hasActiveClass && hasInstance))) {
        reinitialize();
      }
    };

    // ========================================================================
    // EVENT LISTENERS FOR PAGE NAVIGATION
    // ========================================================================

    // Initial setup with proper cleanup
    // Force clean state on page load/refresh
    if (reportsList) {
      // Clear any residual styles from previous sessions
      reportsList.classList.remove('gomk-masonry-active');
      Array.from(reportsList.querySelectorAll('.report-card')).forEach((card) => {
        card.style.removeProperty('width');
        card.style.removeProperty('position');
        card.style.removeProperty('top');
        card.style.removeProperty('left');
      });
    }
    
    setTimeout(() => {
      disable();
      setTimeout(() => {
        reinitialize();
      }, 50);
    }, 100);

    // Also reinitialize on window load (after all resources are loaded)
    window.addEventListener('load', () => {
      setTimeout(() => {
        verifyMasonry();
        if (reportsList && reportsList.querySelectorAll('.report-card').length > 0) {
          disable();
          setTimeout(reinitialize, 100);
        }
      }, 150);
    });

    // Clean up before page unload to prevent stale state
    window.addEventListener('beforeunload', () => {
      try {
        if (masonry) {
          disable();
        }
      } catch (e) { /* ignore */ }
    });

    // Handle page visibility changes (tab switching, minimize/restore)
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden && reportsList) {
        setTimeout(() => {
          verifyMasonry();
          reinitialize();
        }, 100);
      }
    });

    // Handle window focus (returning to tab)
    window.addEventListener('focus', () => {
      if (reportsList && reportsList.querySelectorAll('.report-card').length > 0) {
        setTimeout(() => {
          verifyMasonry();
          reinitialize();
        }, 100);
      }
    });

    // Handle window resize (debounced for performance)
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        verifyMasonry();
        reinitialize();
      }, 150);
    });

    // Handle browser back/forward navigation (pageshow fires on cached pages)
    window.addEventListener('pageshow', (event) => {
      // Always reinitialize to handle browser cache scenarios
      // Use longer delay for bfcache (back-forward cache) scenarios
      const delay = event.persisted ? 150 : 50;
      setTimeout(() => {
        disable();
        setTimeout(() => {
          reinitialize();
        }, 50);
      }, delay);
    });

    // Monitor DOM changes for layout issues
    if (reportsList) {
      const domObserver = new MutationObserver(() => {
        if (reportsList.querySelectorAll('.report-card').length > 0) {
          setTimeout(verifyMasonry, 100);
        }
      });
      domObserver.observe(reportsList, {
        childList: true,      // Watch for cards added/removed
        subtree: false        // Don't watch nested elements
      });
    }
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
  const mediaActions = reportModal.querySelector('[data-report-modal-actions]');
  const openFullBtn = reportModal.querySelector('[data-report-open-full]');
  const downloadBtn = reportModal.querySelector('[data-report-download]');
  const imageViewer = reportModal.querySelector('[data-report-image-viewer]');
  const imageViewerImg = reportModal.querySelector('[data-report-viewer-image]');
  const imageViewerClose = reportModal.querySelector('[data-report-viewer-close]');

  // Format a date string into 12-hour display like: "Nov 3, 2025 · 12:25 AM"
  window.formatTo12hDisplay = function(input) {
    try {
      if (!input) return '—';
      let s = String(input).trim();
      // Try to normalize common "YYYY-MM-DD HH:MM[:SS]" into ISO by inserting 'T'
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(s)) {
        s = s.replace(' ', 'T');
      }
      const d = new Date(s);
      if (isNaN(d.getTime())) {
        // If we already have an AM/PM formatted string, just return as-is
        if (/[AP]M/i.test(s)) return s;
        return s || '—';
      }
      const formatted = d.toLocaleString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true
      });
      // Convert trailing comma before time into a middle dot separator
      return formatted.replace(/,\s(?=\d)/, ' · ');
    } catch (e) { return input || '—'; }
  };

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
      if (mediaActions) mediaActions.hidden = !hasImage;

      if (hasImage) {
        modalImage.src = imageUrl;
        modalImage.alt = `${titleText} photo`;
        modalImage.hidden = false;
        modalImage.removeAttribute('aria-hidden');
        // Detect aspect ratio when image loads and add a modifier class for portrait images
        try {
          modalImage.onload = () => {
            try {
              const isPortrait = modalImage.naturalHeight > modalImage.naturalWidth;
              if (modalDialog) {
                modalDialog.classList.toggle('report-modal--portrait-image', !!isPortrait);
              }
            } catch (e) { /* ignore */ }
          };
        } catch (e) { /* ignore */ }
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
      // Close mobile nav if open so modal is visible on small screens
      try { if (typeof closeMobileNav === 'function') closeMobileNav(); } catch (e) {}
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
        modalSubmitted.textContent = window.formatTo12hDisplay ? window.formatTo12hDisplay(submitted) : (submitted || '—');
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

      // If the card contains lat/lng, make the modal location clickable to open the map and zoom to marker
      try {
        if (modalLocation && modalLocationItem) {
          const lat = card.getAttribute('data-lat') || card.dataset.lat;
          const lng = card.getAttribute('data-lng') || card.dataset.lng;
          // Remove any existing handler
          if (modalLocationItem._mapHandler) {
            try { modalLocationItem.removeEventListener('click', modalLocationItem._mapHandler); } catch (e) { /* ignore */ }
            modalLocationItem._mapHandler = null;
          }
          if (lat && lng) {
            modalLocation.dataset.lat = lat;
            modalLocation.dataset.lng = lng;
            modalLocation.style.cursor = 'pointer';
            modalLocationItem._mapHandler = (ev) => {
              ev && ev.preventDefault && ev.preventDefault();
              ev && ev.stopPropagation && ev.stopPropagation();
              try {
                const nlat = Number(lat);
                const nlng = Number(lng);
                if (!Number.isFinite(nlat) || !Number.isFinite(nlng)) return;
                if (typeof window.__gomkOpenMapAt === 'function') {
                  window.__gomkOpenMapAt(nlat, nlng, modalLocation.textContent || '');
                } else if (typeof openMapModal === 'function') {
                  // fallback: open map modal normally
                  openMapModal();
                }
              } catch (e) { /* ignore */ }
            };
            modalLocationItem.addEventListener('click', modalLocationItem._mapHandler);
          } else {
            modalLocation.style.removeProperty('cursor');
          }
        }
      } catch (e) { /* ignore */ }
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
        if (modalDialog) {
          modalDialog.classList.remove('report-modal--portrait-image');
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

    // Action buttons
    function openImageViewer(){
      const src = modalImage?.getAttribute('src');
      if (!src || !imageViewer || !imageViewerImg) return;
      imageViewerImg.src = src;
      imageViewerImg.alt = modalTitle?.textContent || 'Report image';
      imageViewer.classList.add('open');
      imageViewer.removeAttribute('hidden');
    }
    function closeImageViewer(){
      if (!imageViewer || !imageViewerImg) return;
      imageViewer.classList.remove('open');
      imageViewer.setAttribute('hidden','hidden');
      imageViewerImg.removeAttribute('src');
    }
    if (openFullBtn) { openFullBtn.addEventListener('click', openImageViewer); }
    if (imageViewerClose) { imageViewerClose.addEventListener('click', closeImageViewer); }
    imageViewer?.addEventListener('click', (e) => { if (e.target === imageViewer) closeImageViewer(); });
    if (downloadBtn) {
      downloadBtn.addEventListener('click', () => {
        const src = modalImage?.getAttribute('src');
        if (!src) return;
        const a = document.createElement('a');
        a.href = src;
        a.download = (modalTitle?.textContent || 'report-image').replace(/\s+/g,'-').toLowerCase() + '.jpg';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      });
    }

    reportModal.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        if (imageViewer && imageViewer.classList.contains('open')) { closeImageViewer(); return; }
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

          // Smooth height animation: measure start/end heights and animate
          const targetText = expand ? fullText : (p.dataset.collapsedText || '');
          // Measure start height
          const startHeight = p.getBoundingClientRect().height;

          // Apply target text off-DOM to measure end height
          const clone = p.cloneNode(true);
          // Ensure clone has only the text we want for measurement
          const cloneTextHolder = clone.querySelector('.report-summary__text') || clone;
          if (cloneTextHolder) cloneTextHolder.textContent = targetText;
          clone.style.position = 'absolute';
          clone.style.visibility = 'hidden';
          clone.style.height = 'auto';
          clone.style.maxHeight = 'none';
          clone.style.overflow = 'visible';
          p.parentNode && p.parentNode.appendChild(clone);
          const endHeight = clone.getBoundingClientRect().height;
          clone.remove();

          // Apply inline height to start the transition
          p.style.height = startHeight + 'px';
          p.style.overflow = 'hidden';
          p.style.transition = 'height 220ms ease';

          // Swap the content immediately so measured endHeight matches final content
          if (textHolder.classList && textHolder.classList.contains('report-summary__text')) {
            textHolder.textContent = targetText;
          } else {
            textHolder.textContent = targetText;
          }

          // Remove any existing action link and recreate with updated handler
          const existing = p.querySelector('.report-see-more, .report-see-less');
          if (existing) existing.remove();
          const link = document.createElement('a');
          link.href = '#';
          link.className = expand ? 'report-see-less' : 'report-see-more';
          link.textContent = expand ? 'See less' : 'See more';
          link.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            setExpanded(!expand);
          });
          try {
            if (textHolder && textHolder !== p && textHolder.appendChild) {
              textHolder.appendChild(link);
            } else {
              p.appendChild(link);
            }
          } catch (e) { p.appendChild(link); }

          // Trigger layout and animate to end height
          // Use requestAnimationFrame to ensure style changes are flushed
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              p.style.height = endHeight + 'px';
            });
          });

          const cleanup = () => {
            p.style.transition = '';
            p.style.height = '';
            p.style.overflow = '';
            p.removeEventListener('transitionend', onEnd);
            // Reflow Masonry after DOM/text changes to avoid jank/freezes
            try {
              if (typeof window.__gomkScheduleMasonryLayout === 'function') {
                window.__gomkScheduleMasonryLayout();
              } else if (window.__gomkMasonry && typeof window.__gomkMasonry.layout === 'function') {
                window.__gomkMasonry.layout();
              }
            } catch (e) { /* ignore */ }
          };

          const onEnd = (ev) => {
            if (ev && ev.target !== p) return;
            cleanup();
          };

          p.addEventListener('transitionend', onEnd);
          // Fallback cleanup in case transitionend doesn't fire
          setTimeout(cleanup, 320);
        };

        // Decide whether we need a see-more toggle. Prefer server-rendered
        // link, otherwise detect based on content length or visual overflow.
        const hasSeeMore = !!p.querySelector('.report-see-more');
        const collapsed = p.dataset.collapsedText || '';
        const needsToggle = () => {
          // If the full text is essentially the same as the collapsed snapshot,
          // don't show a toggle. Also skip toggles for very short messages.
          try {
            const f = (fullText || '').trim();
            const c = (collapsed || '').trim();
            if (!f) return false;
            // If text identical, no toggle
            if (f === c) return false;
            // If very short (few chars or words), don't show a toggle
            if (f.length <= 20) return false;
            const words = f.split(/\s+/).filter(Boolean);
            if (words.length <= 2) return false;
            // If full text is longer than the collapsed snapshot by a meaningful
            // amount (avoid showing link for tiny differences like punctuation)
            if ((f.length - c.length) > 6) return true;
          } catch (e) { /* ignore */ }
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

      // Wire location & share icon buttons inside each card
      try {
        const locBtn = card.querySelector('.location-button');
        if (locBtn) {
          locBtn.addEventListener('click', (ev) => {
            ev.preventDefault(); ev.stopPropagation();
            const lat = card.dataset.lat || card.getAttribute('data-lat');
            const lng = card.dataset.lng || card.getAttribute('data-lng');
            const loc = card.dataset.location || card.getAttribute('data-location');
            console.log('Location button clicked. Lat:', lat, 'Lng:', lng, 'Location:', loc);
            if (lat && lng) {
              const numLat = Number(lat);
              const numLng = Number(lng);
              console.log('Parsed coordinates - Lat:', numLat, 'Lng:', numLng);
              if (!isNaN(numLat) && !isNaN(numLng)) {
                try { 
                  if (typeof window.__gomkOpenMapAt === 'function') {
                    window.__gomkOpenMapAt(numLat, numLng, loc || 'Report Location');
                  } else {
                    console.error('__gomkOpenMapAt function not found');
                  }
                } catch (e) { 
                  console.error('Error calling __gomkOpenMapAt:', e);
                }
              } else {
                console.error('Invalid coordinates:', lat, lng);
              }
            } else {
              console.log('No coordinates found, opening map modal for search');
              // no coords: open the map modal to allow user search
              try { if (typeof openMapModal === 'function') openMapModal(); } catch (e) {}
            }
          });
        }
        const shareBtn = card.querySelector('.share-button');
        if (shareBtn) {
          shareBtn.addEventListener('click', async (ev) => {
            ev.preventDefault(); ev.stopPropagation();
            const id = card.dataset.id || card.getAttribute('data-id');
            const title = card.dataset.title || card.getAttribute('data-title') || '';
            const pageUrl = (window.location.origin || '') + (window.location.pathname || '') + (id ? ('?report=' + encodeURIComponent(id)) : '');
            // Try native share first
            try {
              if (navigator.share) {
                await navigator.share({ title: title || 'Citizen report', text: title || '', url: pageUrl });
                return;
              }
            } catch (e) { /* ignore */ }
            // Fallback: copy to clipboard
            try {
              if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(pageUrl);
                (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Link copied to clipboard', { type: 'success' }) : alert('Link copied to clipboard');
                return;
              }
            } catch (e) { /* ignore */ }
            // Last resort: prompt with the URL
            try { window.prompt('Copy this link', pageUrl); } catch (e) { /* ignore */ }
          });
        }
      } catch (e) { /* ignore wiring errors per-card */ }

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

    // Check if URL contains a report parameter and auto-open that report's modal
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const reportId = urlParams.get('report');
      if (reportId) {
        // Wait a bit for the page to fully load, then find and open the report
        setTimeout(() => {
          const targetCard = reportCards.find(card => {
            const cardId = card.dataset.id || card.getAttribute('data-id');
            return cardId && cardId.toString() === reportId.toString();
          });
          if (targetCard) {
            // Scroll to the report card first
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Then open the modal after a short delay
            setTimeout(() => {
              openModal(targetCard);
            }, 500);
          }
        }, 1000);
      }
    } catch (e) {
      console.warn('Failed to process report URL parameter:', e);
    }

    // Expose openModal globally so it can be accessed from other functions
    window.openModal = openModal;
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
    // Helper to programmatically close the mobile nav from elsewhere in the app
    const closeMobileNav = () => {
      try {
        document.body.classList.remove('nav-open');
        navToggleButtons.forEach((button) => button.setAttribute('aria-expanded', 'false'));
        if (navScrim) navScrim.setAttribute('hidden', 'hidden');
      } catch (e) { /* noop */ }
    };

    // Auto-close navbar when clicking on navigation links (mobile only)
    const navLinks = Array.from(document.querySelectorAll('[data-nav-link]'));
    navLinks.forEach((link) => {
      link.addEventListener('click', () => {
        if (navMediaQuery.matches) {
          closeMobileNav();
        }
      });
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
  const AUTO_MARK_THRESHOLD = 12; // unread count at or below this will be auto-marked on close
  let lastUnreadCount = 0; // from last successful fetch
  let markedThisSession = false; // avoid duplicate mark calls while toggling

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
        markedThisSession = false;
        if (!notificationsLoaded) loadNotifications();
      } else {
        // On close: if unread is small, auto-mark read on the server and update UI
        if (!markedThisSession && notificationsLoaded && lastUnreadCount > 0 && lastUnreadCount <= AUTO_MARK_THRESHOLD) {
          markedThisSession = true;
          try { fetch('api/notifications_mark_read.php', { method: 'POST' }); } catch {}
          if (notificationDot && !notificationDot.hasAttribute('hidden')) {
            notificationDot.setAttribute('hidden', 'hidden');
          }
          if (typeof setCaughtUp === 'function') setCaughtUp(true);
          lastUnreadCount = 0;
        }
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

    // Clear all notifications button
    const clearAllButton = notificationContainer.querySelector('[data-notification-clear-all]');
    clearAllButton?.addEventListener('click', async () => {
      // Confirm before clearing
      let confirmed = true;
      if (window.GOMK && window.GOMK.confirmDialog) {
        confirmed = await window.GOMK.confirmDialog('Clear all notifications? This cannot be undone.', { okText: 'Clear All', cancelText: 'Cancel' });
      } else {
        confirmed = window.confirm('Clear all notifications? This cannot be undone.');
      }
      if (!confirmed) return;

      clearAllButton.disabled = true;
      const originalText = clearAllButton.textContent;
      clearAllButton.textContent = 'Clearing...';

      try {
        // Get all notification IDs
        const items = notificationList?.querySelectorAll('.notification-item');
        if (!items || items.length === 0) {
          clearAllButton.disabled = false;
          clearAllButton.textContent = originalText;
          return;
        }

        // Delete all notifications in parallel
        const deletePromises = Array.from(items).map(async (item) => {
          const deleteBtn = item.querySelector('.notification-delete');
          const nid = deleteBtn?.dataset?.notificationId;
          if (!nid) return Promise.resolve();

          const fd = new FormData();
          fd.append('notification_id', nid);
          
          try {
            const response = await fetch('api/notifications_delete.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            return response.ok ? Promise.resolve() : Promise.reject();
          } catch (e) {
            return Promise.reject(e);
          }
        });

        await Promise.allSettled(deletePromises);

        // Immediately update UI by clearing items from DOM
        Array.from(items).forEach(item => item.remove());

        // Show empty state
        if (notificationList && notificationList.children.length === 0) {
          notificationList.innerHTML = '<div class="notification-empty">No notifications yet.</div>';
        }

        // Update UI state
        if (notificationDot) notificationDot.setAttribute('hidden', 'hidden');
        setCaughtUp(true);
        lastUnreadCount = 0;
        notificationsLoaded = true;

        // Disable Clear All button since there are no notifications now
        if (clearAllButton) {
          clearAllButton.classList.add('is-disabled');
          clearAllButton.setAttribute('aria-disabled', 'true');
          clearAllButton.disabled = true;
        }

        if (window.GOMK && window.GOMK.showToast) {
          window.GOMK.showToast('All notifications cleared', { type: 'success' });
        }
      } catch (e) {
        if (window.GOMK && window.GOMK.showToast) {
          window.GOMK.showToast('Failed to clear notifications', { type: 'error' });
        } else {
          alert('Failed to clear notifications');
        }
      } finally {
        clearAllButton.disabled = false;
        clearAllButton.textContent = originalText;
      }
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
          // Hide action buttons for guests
          if (markReadButton) markReadButton.style.display = 'none';
          if (clearAllButton) clearAllButton.style.display = 'none';
          return;
        }

        // Show action buttons for authenticated users
        if (markReadButton) markReadButton.style.display = '';
        if (clearAllButton) clearAllButton.style.display = '';

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
            const iconClass = item.type === 'warning' ? 'warning' : (item.type === 'success' ? 'success' : item.type === 'error' ? 'error' : 'info');
            
            // Pick appropriate icon based on type
            let iconSvg = '';
            if (item.type === 'success') {
              iconSvg = '<path d="M7.5 12.5 10.8 15.5 16.5 9" />';
            } else if (item.type === 'warning' || item.type === 'error') {
              iconSvg = '<circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />';
            } else {
              iconSvg = '<circle cx="12" cy="12" r="10" /><path d="M12 8h.01" /><path d="M12 12v4" />';
            }
            
            li.innerHTML = `
              <div class="notification-icon ${iconClass}" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                  ${iconSvg}
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

          // Enable/disable Clear All button based on whether there are notifications
          if (clearAllButton) {
            if (data.data && data.data.length > 0) {
              clearAllButton.classList.remove('is-disabled');
              clearAllButton.removeAttribute('aria-disabled');
              clearAllButton.disabled = false;
            } else {
              clearAllButton.classList.add('is-disabled');
              clearAllButton.setAttribute('aria-disabled', 'true');
              clearAllButton.disabled = true;
            }
          }
        }

        lastUnreadCount = (data.unreadCount || 0);
        if (notificationDot) {
          if (lastUnreadCount > 0) notificationDot.removeAttribute('hidden');
          else notificationDot.setAttribute('hidden', 'hidden');
        }

        // Header action policy:
        // - If unread > threshold: show actionable "Mark all as read"
        // - Else: show disabled "All caught up"; items will be marked on panel close
        if (markReadButton) {
          if (lastUnreadCount > AUTO_MARK_THRESHOLD) {
            markReadButton.hidden = false;
            markReadButton.textContent = 'Mark all as read';
            markReadButton.classList.remove('is-disabled');
            markReadButton.removeAttribute('aria-disabled');
          } else {
            markReadButton.hidden = false; // keep the label spot
            markReadButton.textContent = 'All caught up';
            markReadButton.classList.add('is-disabled');
            markReadButton.setAttribute('aria-disabled', 'true');
          }
        }

        setCaughtUp(lastUnreadCount === 0);
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

  // Smooth pagination transitions for Reports on index.php
  (function initReportsPager() {
    const reportsSection = document.getElementById('reports');
    let list = document.getElementById('reportsList');
    let pager = document.getElementById('reportsPager');
    if (!reportsSection || !list || !pager) return; // only on index page

    const attach = () => {
      if (!pager) return;
      const links = Array.from(pager.querySelectorAll('a.pager-btn'));
      links.forEach((a) => a.addEventListener('click', onClick));
    };

    const onClick = (e) => {
      const a = e.currentTarget;
      const disabled = a.getAttribute('aria-disabled') === 'true';
      if (disabled) { e.preventDefault(); return; }
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return; // allow new-tab
      e.preventDefault();
      swapTo(a.href);
    };

    const swapTo = async (url) => {
      try {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextList = doc.getElementById('reportsList');
        const nextPager = doc.getElementById('reportsPager');
        if (!nextList) { window.location.href = url; return; }

        const doSwap = () => {
          try { list.replaceWith(nextList); } catch (e) {}
          if (pager && nextPager) { try { pager.replaceWith(nextPager); } catch (e) {} }
          // Re-init references after swap
          const newList = document.getElementById('reportsList');
          const newPager = document.getElementById('reportsPager');
          // Update live references for future swaps
          if (newList) list = newList;
          if (newPager) pager = newPager;
          // Smooth scroll back to section
          reportsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          // Focus first card for accessibility
          newList?.querySelector('.report-card')?.focus?.();
          // Re-attach listeners for the new pager
          attach();
        };

        if (document.startViewTransition) {
          document.startViewTransition(doSwap);
        } else {
          // Fallback: quick fade-out then swap
          try { list.classList.add('is-out'); } catch (err) {}
          setTimeout(doSwap, 120);
        }

        // push history so back/forward works
        try { history.pushState({}, '', url); } catch (err) {}
      } catch (err) {
        // On error, just navigate normally
        window.location.href = url;
      }
    };

    // Handle back/forward
    window.addEventListener('popstate', () => {
      swapTo(location.href);
    });

    attach();
  })();

  // Admin: wire status dropdowns to API endpoint
  (function initAdminStatusWiring() {
    const adminForms = Array.from(document.querySelectorAll('.admin-inline-form'));
    if (!adminForms.length) return;

    adminForms.forEach((form) => {
      const actionInput = form.querySelector('input[name="action"][value="update_status"]');
      const select = form.querySelector('select[name="status"]');
      if (!actionInput || !select) return;
      const wrapper = select.closest('.admin-select-wrapper');

      const reportIdInput = form.querySelector('input[name="report_id"]');
      const reportId = reportIdInput ? reportIdInput.value : '';

      const applyColorClass = () => {
        const v = String(select.value || '').toLowerCase();
        select.classList.remove('status-unresolved','status-in_progress','status-solved');
        if (v === 'in_progress' || v === 'in-progress') select.classList.add('status-in_progress');
        else if (v === 'solved' || v === 'resolved') select.classList.add('status-solved');
        else select.classList.add('status-unresolved');
        if (wrapper) {
          wrapper.classList.remove('status-unresolved','status-in_progress','status-solved');
          if (v === 'in_progress' || v === 'in-progress') wrapper.classList.add('status-in_progress');
          else if (v === 'solved' || v === 'resolved') wrapper.classList.add('status-solved');
          else wrapper.classList.add('status-unresolved');
        }
      };

      // Apply on load
      applyColorClass();

      const handleChange = async () => {
        const status = select.value;
        applyColorClass();
        select.disabled = true;
        try {
          const fd = new FormData();
          fd.append('report_id', reportId);
          fd.append('status', status);
          const r = await fetch('api/reports_update_status.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await r.json().catch(() => ({}));
          if (!r.ok || !data.success) {
            throw new Error(data.message || 'Failed to update status');
          }
          select.classList.add('saved');
          setTimeout(() => select.classList.remove('saved'), 800);
          // Trigger a live snapshot refresh so the top cards update immediately
          try { window.GOMK && typeof window.GOMK.refreshAdminSnapshot === 'function' && window.GOMK.refreshAdminSnapshot(); } catch (e) {}
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

  // Admin: wire Archive buttons to API (no full-page reload)
  (function initAdminArchiveWiring() {
    const forms = Array.from(document.querySelectorAll('.admin-inline-form'));
    if (!forms.length) return;

    forms.forEach((form) => {
      const actionInput = form.querySelector('input[name="action"][value="archive_report"]');
      if (!actionInput) return;

      // Bypass the global confirm-on-submit handler; we'll handle confirm here
      try { form.removeAttribute('data-confirm-message'); } catch (e) {}

      const btn = form.querySelector('.admin-delete');
      const idEl = form.querySelector('input[name="report_id"]');
      const reportId = idEl ? String(idEl.value || '') : '';
      const row = form.closest('tr');

      const doArchive = async () => {
        if (!reportId) return;
        let ok = true;
        if (window.GOMK && window.GOMK.confirmDialog) {
          ok = await window.GOMK.confirmDialog('Archive this report?', { okText: 'Archive', cancelText: 'Cancel' });
        } else { ok = window.confirm('Archive this report?'); }
        if (!ok) return;

        try {
          const fd = new FormData();
          fd.append('report_id', reportId);
          const res = await fetch('api/report_archive.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const data = await res.json().catch(() => ({}));
          if (!res.ok || !data.success) throw new Error(data.message || 'Failed to archive');
          // Remove the row from the table
          if (row && row.parentNode) row.parentNode.removeChild(row);
          if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast('Report archived', { type: 'success' });
          // Refresh snapshot cards if available
          try { window.GOMK && typeof window.GOMK.refreshAdminSnapshot === 'function' && window.GOMK.refreshAdminSnapshot(); } catch (e) {}
        } catch (e) {
          if (window.GOMK && window.GOMK.showToast) window.GOMK.showToast(e.message || 'There was a problem archiving the report.', { type: 'error' });
          else alert(e.message || 'There was a problem archiving the report.');
        }
      };

      btn?.addEventListener('click', (ev) => { ev.preventDefault(); doArchive(); });
      form.addEventListener('submit', (ev) => { ev.preventDefault(); doArchive(); });
    });
  })();

  // Admin: live snapshot updater for Operations snapshot
  (function initAdminSnapshotLive() {
    const totalEl = document.querySelector('[data-snap-total]');
    const openEl = document.querySelector('[data-snap-open]');
    const progressEl = document.querySelector('[data-snap-progress]');
    const solvedEl = document.querySelector('[data-snap-solved]');
    const barOpen = document.querySelector('[data-snap-open-meter]');
    const barProg = document.querySelector('[data-snap-progress-meter]');
    const barSolved = document.querySelector('[data-snap-solved-meter]');
    const noteOpen = document.querySelector('[data-snap-open-note]');
    const noteProg = document.querySelector('[data-snap-progress-note]');
    const noteSolved = document.querySelector('[data-snap-solved-note]');
    const chipOpen = document.querySelector('[data-snap-open-chip]');
    const chipSolved = document.querySelector('[data-snap-solved-chip]');

    const present = (totalEl || openEl || progressEl || solvedEl);
    if (!present) return; // only on admin page

  let timer; let last;
    const fmt = (n) => (typeof n === 'number' ? n : parseInt(n||0,10) || 0);

    const apply = (data) => {
      try {
        const total = fmt(data.total);
        const unresolved = fmt(data.counts?.unresolved);
        const inProg = fmt(data.counts?.in_progress);
        const solved = fmt(data.counts?.solved);
        const rates = data.rates || {};
        const rOpen = fmt(rates.open);
        const rProg = fmt(rates.in_progress);
        const rSolved = fmt(rates.solved);

        if (totalEl) totalEl.textContent = String(total);
        if (openEl) openEl.textContent = String(unresolved);
        if (progressEl) progressEl.textContent = String(inProg);
        if (solvedEl) solvedEl.textContent = String(solved);
        if (barOpen) barOpen.style.width = Math.max(0, Math.min(100, rOpen)) + '%';
        if (barProg) barProg.style.width = Math.max(0, Math.min(100, rProg)) + '%';
        if (barSolved) barSolved.style.width = Math.max(0, Math.min(100, rSolved)) + '%';
        if (noteOpen) noteOpen.textContent = `${rOpen}% of all requests are still unresolved.`;
        if (noteProg) noteProg.textContent = `${rProg}% have teams presently dispatched.`;
        if (noteSolved) noteSolved.textContent = `Resolution rate holding at ${rSolved}%.`;
        if (chipOpen) chipOpen.textContent = `${rOpen}% open`;
        if (chipSolved) chipSolved.textContent = `${rSolved}% resolved`;
      } catch (e) { /* ignore */ }
    };

    const fetchOnce = async () => {
      try {
        const r = await fetch('api/admin_snapshot.php', { cache: 'no-store', credentials: 'same-origin' });
        const data = await r.json();
        if (!data || data.success === false) throw new Error(data?.message || 'Snapshot error');
        last = data; apply(data);
      } catch (e) {
        // Best-effort: keep last values
      }
    };

    // Expose manual refresh for other modules and poll frequently
    window.GOMK = window.GOMK || {};
    window.GOMK.refreshAdminSnapshot = fetchOnce;

    // Initial and polling every 5s
    fetchOnce();
    timer = setInterval(fetchOnce, 5000);

    // Clean up if needed
    window.addEventListener('beforeunload', () => { try { clearInterval(timer); } catch (e) {} });
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
  // cropMode: null = free, 'portrait' or 'landscape'
  let cropMode = null;
  // display size of image on canvas (used to re-init crop box)
  let cropDisplayWidth = 0;
  let cropDisplayHeight = 0;
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

          // remember display size for re-init when mode changes
          cropDisplayWidth = Math.round(img.width * scale);
          cropDisplayHeight = Math.round(img.height * scale);
          
          // Set actual canvas size to match original image
          canvas.width = img.width;
          canvas.height = img.height;
          
          // Draw image
          ctx.drawImage(img, 0, 0);
          
          // Initialize crop box with scaled dimensions (freeform, ~80% of display size)
          initializeCropBox(cropDisplayWidth, cropDisplayHeight);
          cropImage = img;
        };
        img.src = imageSrc;
      }

      function initializeCropBox(imageWidth, imageHeight) {
        const cropBoxElement = document.getElementById('cropBox');
        
        if (!cropBoxElement) return;
        
        cropBoxElement.innerHTML = '';
        // Start with a large centered box (80% of area) or respect selected orientation
        let cropWidth = imageWidth * 0.8;
        let cropHeight = imageHeight * 0.8;
        if (cropMode === 'portrait' || cropMode === 'landscape') {
          const aspect = (cropMode === 'landscape') ? (4/3) : (3/4);
          // Decide whether to fit by width or height depending on image shape
          if ((imageWidth / imageHeight) >= aspect) {
            // image is wider than target aspect -> use height to base size
            cropHeight = imageHeight * 0.8;
            cropWidth = Math.min(imageWidth, Math.round(cropHeight * aspect));
          } else {
            // image is taller than target aspect -> use width to base size
            cropWidth = imageWidth * 0.8;
            cropHeight = Math.min(imageHeight, Math.round(cropWidth / aspect));
          }
        }
        
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

        // If an orientation mode is active, enforce aspect ratio
        if (cropMode === 'portrait' || cropMode === 'landscape') {
          const aspect = (cropMode === 'landscape') ? (4/3) : (3/4);
          // For corner handles, drive height from width
          if (['nw','ne','se','sw'].includes(resizeDir)) {
            h = Math.round(w / aspect);
            // If height overflowed, adjust width instead
            if (h + y > rect.height) {
              h = rect.height - y;
              w = Math.round(h * aspect);
            }
          } else if (resizeDir === 'n' || resizeDir === 's') {
            // vertical handles - height driven by pointer, width computed
            w = Math.round(h * aspect);
            if (w + x > rect.width) {
              w = rect.width - x;
              h = Math.round(w / aspect);
            }
          } else if (resizeDir === 'e' || resizeDir === 'w') {
            // horizontal handles - width driven by pointer, height computed
            h = Math.round(w / aspect);
            if (h + y > rect.height) {
              h = rect.height - y;
              w = Math.round(h * aspect);
            }
          }
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

      // Crop orientation buttons
      const cropModePortraitBtn = document.getElementById('cropModePortrait');
      const cropModeLandscapeBtn = document.getElementById('cropModeLandscape');
      function setCropMode(mode) {
        cropMode = (mode === cropMode) ? null : mode;
        // update active state on buttons
        try {
          if (cropModePortraitBtn) cropModePortraitBtn.classList.toggle('active', cropMode === 'portrait');
          if (cropModeLandscapeBtn) cropModeLandscapeBtn.classList.toggle('active', cropMode === 'landscape');
          if (cropModePortraitBtn) cropModePortraitBtn.setAttribute('aria-pressed', cropMode === 'portrait');
          if (cropModeLandscapeBtn) cropModeLandscapeBtn.setAttribute('aria-pressed', cropMode === 'landscape');
        } catch (e) {}
        // reinitialize crop box to respect new aspect
        try { if (cropImage && cropDisplayWidth && cropDisplayHeight) initializeCropBox(cropDisplayWidth, cropDisplayHeight); } catch (e) {}
      }
      if (cropModePortraitBtn) cropModePortraitBtn.addEventListener('click', () => setCropMode('portrait'));
      if (cropModeLandscapeBtn) cropModeLandscapeBtn.addEventListener('click', () => setCropMode('landscape'));

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

          // Photo is REQUIRED for submission. If no File present, stop and ask user.
          const pfFinal = formData.get('photo');
          if (!(pfFinal instanceof File)) {
            (window.GOMK && window.GOMK.showToast)
              ? window.GOMK.showToast('Please add a photo to your report', { type: 'info' })
              : alert('Please add a photo to your report');
            return;
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

      function removePhoto() {
        photoUploadArea.classList.remove('has-photo');
        photoPreview.src = '';
        try { photoInput.value = ''; } catch {}
      }

      // Clear form button handler
      const clearFormBtn = document.getElementById('clearFormBtn');
      if (clearFormBtn) { clearFormBtn.addEventListener('click', clearForm); }

      // Photo delete (X) button overlay - only removes photo, not all details
      const photoDelete = document.getElementById('photoDelete');
      if (photoDelete) {
        photoDelete.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          removePhoto();
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

      // Character counters for title and description
      const titleInput = document.getElementById('title');
      const descriptionTextarea = document.getElementById('description');
      const titleCounter = document.getElementById('title-count');
      const descriptionCounter = document.getElementById('description-count');

      if (titleInput && titleCounter) {
        titleInput.addEventListener('input', function() {
          titleCounter.textContent = this.value.length;
        });
      }

      if (descriptionTextarea && descriptionCounter) {
        descriptionTextarea.addEventListener('input', function() {
          descriptionCounter.textContent = this.value.length;
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

  // Proceed if a map container exists. locationField is optional (present on create-report).
  if (!mapEl) return;

    let map = null;
    let marker = null;
    let chosenPlace = null;
    let isViewOnlyMode = false; // Flag to track if we're in view-only mode

    // Small helper to escape HTML when building popup content
    const escapeHtml = (s) => {
      try {
        return String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
      } catch (e) { return String(s); }
    };

    // Initialize Leaflet map when modal opens (lazy init)
    const ensureMap = () => {
      if (map) return map;
      try {
  // Marikina bounding box (approx) - lat, lng pairs: [[south, west], [north, east]]
  const marikinaBounds = L.latLngBounds([[14.57, 121.02], [14.76, 121.16]]);
  // Initialize map centered in Marikina and restrict to Marikina bounds.
  // Limit zoom levels and tile loading to reduce resource usage.
  map = L.map(mapEl, {
    center: [14.650, 121.102],
    zoom: 14,
    minZoom: 13,
    maxZoom: 18,
    maxBounds: marikinaBounds,
    maxBoundsViscosity: 0.95,
    preferCanvas: true,
    worldCopyJump: false
  });

  // Use a cleaner basemap (CartoDB Positron) to reduce POI icon clutter and
  // provide a softer, more modern look. This reduces map 'other' icons.
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    minZoom: 13,
    maxZoom: 18,
    noWrap: false,
    detectRetina: false,
    keepBuffer: 1,
    bounds: marikinaBounds
  }).addTo(map);

  // Main draggable marker for user selection
  marker = L.marker([14.65, 121.102], { draggable: true }).addTo(map);
  // Bind an initially empty popup (we'll update content when selection happens)
  try { marker.bindPopup(''); } catch (e) {}
        marker.on('dragend', () => {
          // If in view-only mode, don't allow dragging - reset marker position
          if (isViewOnlyMode && chosenPlace) {
            marker.setLatLng([chosenPlace.lat, chosenPlace.lon]);
            console.log('Drag prevented - view-only mode');
            return;
          }
          
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
          // Show lat/lng immediately and reverse-geocode in background so UI isn't blocked
          if (locInput) locInput.value = `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`;
          try { if (placeInput) placeInput.value = `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`; } catch (e) {}
          (async () => {
            try {
              const rev = await nominatimReverse(pos.lat, pos.lng);
              const display = rev && (rev.display_name || (rev.address && Object.values(rev.address).join(', '))) ? (rev.display_name || '') : '';
              if (locInput && display) locInput.value = display;
              try { if (placeInput && display) placeInput.value = display; } catch (e) {}
              try { chosenPlace = { lat: pos.lat, lon: pos.lng, display_name: display }; } catch (e) {}
              // Use chained popup API and open popup on the marker to show the selected address
              try {
                if (marker && display) {
                  marker.bindPopup(display, { closeButton: true }).openPopup();
                }
              } catch (e) {}
            } catch (e) { /* ignore reverse failures */ }
          })();
        });

  // Report marker layer + clustering + viewport fetching + caching
  let reportLayer = null;
  const createReportLayer = () => {
    if (reportLayer) return reportLayer;
    if (window.L && typeof L.markerClusterGroup === 'function') {
      try {
          // Custom cluster icon to match marker look and feel
          // disable zoomToBoundsOnClick so we can intercept cluster clicks and
          // show a popup listing child reports instead of zooming automatically.
          reportLayer = L.markerClusterGroup({
            iconCreateFunction: function(cluster) {
              const count = cluster.getChildCount();
              return L.divIcon({
                html: '<div class="gomk-cluster">' + count + '</div>',
                className: 'gomk-cluster-wrapper',
                iconSize: L.point(40, 40)
              });
            },
            zoomToBoundsOnClick: false,
            // Make clusters more likely by increasing pixel radius used to group markers
            maxClusterRadius: 120
          });
      } catch (e) { reportLayer = L.layerGroup(); }
    } else {
      reportLayer = L.layerGroup();
    }
    reportLayer.addTo(map);

      // When a cluster is clicked, show a popup listing children with links
      // to open the report modal. This avoids auto-zoom behavior and gives
      // users a quick way to view reports in that cluster.
      try {
        reportLayer.on('clusterclick', (ev) => {
          try {
            const cluster = ev.layer;
            const children = cluster.getAllChildMarkers();
            if (!children || !children.length) return;
            // Build a list HTML (limit to 12 entries to avoid huge popups)
            const max = 12;
            const items = children.slice(0, max).map((cm) => {
              const d = cm.__reportData || (cm.options && cm.options.__reportData) || {};
              const id = d.id || (d.data && d.data.id) || cm.reportId || '';
              const title = d.title || d.display_name || d.name || 'Report';
              return '<div class="gomk-cluster-item"><strong>' + escapeHtml(String(title)) + '</strong> <a href="#" class="gomk-view-report" data-id="' + escapeHtml(String(id)) + '">View</a></div>';
            });
            if (children.length > max) items.push('<div class="gomk-cluster-item">+' + (children.length - max) + ' more…</div>');
            const html = '<div class="gomk-cluster-popup">' + items.join('') + '</div>';
            const popup = L.popup({ maxWidth: 360 }).setLatLng(cluster.getLatLng()).setContent(html).openOn(map);
            
            // Add click handlers to the View links after popup opens
            setTimeout(() => {
              const viewLinks = document.querySelectorAll('.gomk-view-report');
              viewLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                  e.preventDefault();
                  const reportId = link.getAttribute('data-id');
                  if (!reportId) return;
                  
                  // Try to find the report in the children markers
                  const targetMarker = children.find(m => {
                    const d = m.__reportData || {};
                    return String(d.id) === String(reportId);
                  });
                  
                  if (targetMarker && targetMarker.__reportData) {
                    // Close the cluster popup
                    map.closePopup();
                    // Open the report in modal using openReportInModal
                    try {
                      if (typeof openReportInModal === 'function') {
                        openReportInModal(targetMarker.__reportData);
                      }
                    } catch (err) {
                      console.warn('Failed to open report modal:', err);
                      // Fallback: show a toast or alert
                      if (window.GOMK && window.GOMK.showToast) {
                        window.GOMK.showToast('Unable to open report details', { type: 'error' });
                      }
                    }
                  } else {
                    console.warn('Report not found in cluster:', reportId);
                    if (window.GOMK && window.GOMK.showToast) {
                      window.GOMK.showToast('Report not found', { type: 'error' });
                    }
                  }
                });
              });
            }, 50);
          } catch (e) { console.warn('Cluster click render failed', e); }
        });
      } catch (e) {}
    return reportLayer;
  };

  const renderMarkers = (reports) => {
    const layer = createReportLayer();
    if (layer.clearLayers) layer.clearLayers();
    try { reportCache.clear && reportCache.clear(); } catch (e) {}
    reports.forEach(r => {
      const lat = parseFloat(r.latitude || r.lat || 0);
      const lng = parseFloat(r.longitude || r.lon || 0);
      if (!lat || !lng) return;
      // Color-code by status: unresolved (red), in_progress (orange/yellow), solved (green)
      const st = String(r.status || '').toLowerCase();
      let color = '#d84545'; // red for unresolved
      if (st === 'in_progress' || st === 'in-progress') color = '#f59e0b'; // orange/amber for in_progress
      if (st === 'solved' || st === 'resolved') color = '#2e8b57'; // green for solved
      // Use a small divIcon so markers match the visual style of clusters
      const icon = L.divIcon({
        className: 'gomk-marker-wrapper',
        html: '<span class="gomk-marker" style="background:' + color + '"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
      });
      const m = L.marker([lat, lng], { icon });
      try {
        const title = r.title || r.display_name || r.name || 'Report location';
        const meta = [];
        if (r.category) meta.push(String(r.category));
        if (r.status) meta.push(String(r.status));
        const popupHtml = '<strong>' + escapeHtml(String(title)) + '</strong>' + (meta.length ? '<br>' + escapeHtml(meta.join(' · ')) : '');
        m.bindPopup(popupHtml, { closeButton: true });
        // Attach the report payload to marker for later reference (used by cluster popup links)
        try { m.__reportData = r; m.reportId = r.id; } catch (e) {}
        // Open popup on click only (remove hover behavior per request)
        m.on && m.on('click', () => { try { m.openPopup(); } catch (e) {} });
      } catch (e) {}
      layer.addLayer(m);
      try { m.__reportData = r; m.reportId = r.id; reportCache.set(r.id, { data: r, marker: m }); } catch (e) {}
    });
  };

  // Maintain an in-memory cache of loaded report IDs to avoid re-adding markers
  const reportCache = new Map();

  // Remove a cached marker by id (from layer and cache)
  const removeMarkerById = (id) => {
    try {
      const key = Number(id);
      if (!reportCache.has(key)) return;
      const entry = reportCache.get(key);
      const layer = createReportLayer();
      if (entry && entry.marker && layer && typeof layer.removeLayer === 'function') {
        try { layer.removeLayer(entry.marker); } catch (e) {}
      }
      reportCache.delete(key);
    } catch (e) {}
  };

  // Add a marker for a single report and register it in the cache
  const addMarker = (r) => {
    try {
      const layer = createReportLayer();
      const lat = parseFloat(r.latitude || r.lat || 0);
      const lng = parseFloat(r.longitude || r.lon || 0);
      if (!lat || !lng) return;
      // Color-code by status: unresolved (red), in_progress (orange/yellow), solved (green)
      const st = String(r.status || '').toLowerCase();
      let color = '#d84545'; // red for unresolved
      if (st === 'in_progress' || st === 'in-progress') color = '#f59e0b'; // orange/amber for in_progress
      if (st === 'solved' || st === 'resolved') color = '#2e8b57'; // green for solved
      const icon = L.divIcon({
        className: 'gomk-marker-wrapper',
        html: '<span class="gomk-marker" style="background:' + color + '"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
      });
      const m = L.marker([lat, lng], { icon });
      try {
        const title = r.title || r.display_name || r.name || '';
        const meta = [];
        if (r.category) meta.push(String(r.category));
        if (r.status) meta.push(String(r.status));
        const popupHtml = title ? ('<strong>' + escapeHtml(String(title)) + '</strong>' + (meta.length ? '<br>' + escapeHtml(meta.join(' · ')) : '')) : (r.display_name || '');
        m.bindPopup(popupHtml);
        try { m.__reportData = r; m.reportId = r.id; } catch (e) {}
        // Open popup on click only (remove hover behavior per request)
        m.on && m.on('click', () => { try { m.openPopup(); } catch (e) {} });
      } catch (e) {}
      layer.addLayer(m);
      reportCache.set(r.id, { data: r, marker: m });
    } catch (e) { /* ignore marker errors */ }
  };

  // Add only new markers from server response (no duplicates)
  function updateMarkers(items) {
    if (!Array.isArray(items)) return;
    items.forEach(item => {
      if (!item || typeof item.id === 'undefined') return;
      if (!reportCache.has(item.id)) {
        addMarker(item);
      }
    });
  }

  // Initial fetch on load and subsequent fetches when the map stops moving.
  const fetchForBounds = async () => {
    try {
      if (!map) return;
      const bounds = map.getBounds();
      const params = {
        north: bounds.getNorth(),
        south: bounds.getSouth(),
        east: bounds.getEast(),
        west: bounds.getWest()
      };
  const url = `api/get_reports.php?north=${params.north}&south=${params.south}&east=${params.east}&west=${params.west}`;
      const res = await fetch(url);
      if (!res.ok) return;
  const json = await res.json();
  if (!json || !json.success || !Array.isArray(json.data)) return;
  // Reconcile: fully re-render markers to drop deleted/out-of-bounds items
  renderMarkers(json.data);
    } catch (e) {
      console.warn('Failed to fetch reports for bounds', e);
    }
  };

  try { fetchForBounds(); } catch (e) {}
  map.on && map.on('moveend', () => { try { fetchForBounds(); } catch (e) {} });

  // When a popup is opened on the map (either for a cluster or marker),
  // wire up any "View" links inside it so they open the report modal.
  try {
    map.on && map.on('popupopen', (evt) => {
      try {
        const popupEl = evt.popup && evt.popup.getElement ? evt.popup.getElement() : null;
        if (!popupEl) return;
        // Attach delegated click handler for view links inside the popup
        const links = popupEl.querySelectorAll('.gomk-view-report');
        links.forEach((ln) => {
          ln.addEventListener('click', (ev) => {
            try {
              ev.preventDefault();
              const id = ln.dataset && ln.dataset.id ? ln.dataset.id : ln.getAttribute('data-id');
              if (!id) return;
              // Look up cached report and open modal
              const entry = reportCache.get(Number(id));
              if (entry && entry.data) {
                openReportInModal(entry.data);
                // close the popup so modal is focused
                try { map.closePopup(); } catch (e) {}
                return;
              }
              // Fallback: try fetching full report via reports_list endpoint (by loading all and matching id)
              (async () => {
                try {
                  const r = await fetch('api/reports_list.php')
                    .then(res => res.json())
                    .then(j => j && j.data ? j.data.find(x => String(x.id) === String(id)) : null);
                  if (r) {
                    openReportInModal(r);
                    try { map.closePopup(); } catch (e) {}
                  } else {
                    // Not found: show notice and remove stale marker
                    if (window.GOMK && window.GOMK.showToast) {
                      window.GOMK.showToast('This report is no longer available.', { type: 'info', duration: 3000 });
                    } else {
                      alert('This report is no longer available.');
                    }
                    removeMarkerById(id);
                    try { map.closePopup(); } catch (e) {}
                  }
                } catch (e) {
                  console.warn('Failed to fetch report for id', id, e);
                }
              })();
            } catch (e) {}
          });
        });
      } catch (e) {}
    });
  } catch (e) {}

        // Clicking the map places the marker and reverse-geocodes
        map.on('click', (e) => {
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
            if (locInput) locInput.value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
            // Reverse geocode asynchronously and update when ready
            (async () => {
              try {
                const rev = await nominatimReverse(lat, lon);
                const display = rev && (rev.display_name || (rev.address && Object.values(rev.address).join(', '))) ? (rev.display_name || '') : '';
                if (locInput && display) locInput.value = display;
                try { if (placeInput && display) placeInput.value = display; } catch (e) {}
                try { chosenPlace = { lat: lat, lon: lon, display_name: display }; } catch (e) {}
                // Show popup for the main marker when user clicks the map
                try {
                  if (marker && display) {
                    marker.bindPopup(display, { closeButton: true }).openPopup();
                  }
                } catch (e) {}
              } catch (e) { /* ignore */ }
            })();
          } catch (e) { /* ignore */ }
        });
      } catch (e) {
        map = null; marker = null;
      }
      return map;
    };

    // Helper to present a report object in the existing report modal.
    const openReportInModal = (reportObj) => {
      try {
        // Build a temporary element that mimics the .report-card dataset used by openModal
        const fake = document.createElement('div');
        fake.dataset.title = reportObj.title || reportObj.name || '';
        fake.dataset.summary = reportObj.summary || reportObj.description || '';
        fake.dataset.reporter = reportObj.reporter || 'Resident';
        fake.dataset.location = reportObj.location || '';
        fake.dataset.category = reportObj.category || '';
        // Compute statusLabel and modifier (basic mapping similar to PHP helper)
        const st = String(reportObj.status || '').toLowerCase();
        const label = (st === 'in_progress' || st === 'in-progress') ? 'In progress' : (st === 'solved' || st === 'resolved' ? 'Solved' : 'Unresolved');
        const modifier = (st === 'in_progress' || st === 'in-progress') ? 'in-progress' : (st === 'solved' || st === 'resolved' ? 'solved' : 'unresolved');
        fake.dataset.statusLabel = label;
        fake.dataset.statusModifier = modifier;
        fake.dataset.submitted = (window.formatTo12hDisplay ? window.formatTo12hDisplay(reportObj.submitted_at || reportObj.created_at || '') : (reportObj.submitted_at || reportObj.created_at || ''));
        fake.dataset.image = reportObj.image || reportObj.image_path || '';
        // Call the global modal opener defined earlier in script
        try { 
          if (window.openModal && typeof window.openModal === 'function') {
            window.openModal(fake);
          } else {
            console.warn('openModal function not found on window object');
          }
        } catch (e) { console.warn('Error calling openModal:', e); }
      } catch (e) { console.warn('openReportInModal failed', e); }
    };

    // Expose openReportInModal globally so it can be accessed from cluster click handlers
    window.openReportInModal = openReportInModal;

  const openMapModal = () => {
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
        
        // Set marker and view after a brief delay to ensure map is ready
        // Only do this if we have a specific location to show
        if (chosenPlace && marker && map) {
          // First set the view immediately
          try {
            map.setView([chosenPlace.lat, chosenPlace.lon], 19, { animate: false });
            marker.setLatLng([chosenPlace.lat, chosenPlace.lon]);
            console.log('openMapModal: Initial view and marker set to', chosenPlace.lat, chosenPlace.lon);
          } catch (e) { console.error('Error setting initial view:', e); }
          
          // Then refine after a delay
          setTimeout(() => {
            try {
              // Position marker at the report location
              marker.setLatLng([chosenPlace.lat, chosenPlace.lon]);
              console.log('openMapModal: Marker set to', chosenPlace.lat, chosenPlace.lon);
              
              // Zoom to very close street-level view
              const z = 19; // Maximum zoom for detailed view of report location
              map.setView([chosenPlace.lat, chosenPlace.lon], z, { animate: true, duration: 0.3 });
              console.log('openMapModal: Map view set to', chosenPlace.lat, chosenPlace.lon, 'zoom:', z);
              
              // Invalidate size again to ensure tiles load properly
              map.invalidateSize(true);
              
              // Open the popup
              setTimeout(() => {
                try {
                  const popupText = (chosenPlace.display_name || chosenPlace.name || 'Report Location').toString();
                  marker.bindPopup(popupText, { closeButton: true }).openPopup();
                  console.log('openMapModal: Popup opened with text:', popupText);
                } catch (e) { console.error('Error opening popup:', e); }
              }, 150);
            } catch (e) { console.error('Error setting map view:', e); }
          }, 200);
        }
        // No search input to focus anymore
      }, 80);
    };

  const closeMapModal = () => {
      if (!mapModal) return;
      mapModal.removeAttribute('open');
      mapModal.style.display = 'none';
      document.body.classList.remove('modal-open');
      // Clear chosenPlace when closing so it doesn't persist to next opening
      // unless explicitly set by __gomkOpenMapAt
      chosenPlace = null;
      // Reset view-only mode
      isViewOnlyMode = false;
      console.log('closeMapModal: chosenPlace cleared, view-only mode disabled');
    };

  if (locationField) {
    locationField.addEventListener('click', openMapModal);
  }

  mapModalClose?.addEventListener('click', closeMapModal);
  mapModal.addEventListener('click', (ev) => { if (ev.target === mapModal) closeMapModal(); });

    // Nominatim search helper (public) — consider swapping to LocationIQ if you have a token
    // Simple in-memory caches to reduce repeat Nominatim requests
    const nominatimSearchCache = new Map();
    const nominatimReverseCache = new Map();

    // AbortController used to cancel in-flight search requests when the user types again
    let nominatimSearchController = null;
    const nominatimSearch = async (q) => {
      if (!q) return [];
      if (nominatimSearchCache.has(q)) return nominatimSearchCache.get(q);
      // Build proxy URL (server-side proxy handles caching and rate limits)
      const viewbox = '121.02,14.76,121.16,14.57';
  const url = `api/geocode_proxy.php?action=search&q=${encodeURIComponent(q)}&viewbox=${encodeURIComponent(viewbox)}&limit=5`;
      // Abort previous controller if present
      try { if (nominatimSearchController) { try { nominatimSearchController.abort(); } catch (e) {} } } catch (e) {}
      nominatimSearchController = new AbortController();
      const signal = nominatimSearchController.signal;

      const maxAttempts = 2;
      let attempt = 0;
      while (attempt < maxAttempts) {
        attempt += 1;
        try {
          const r = await fetch(url, { signal });
          if (!r.ok) {
            console.warn('Geocode proxy returned status', r.status);
            if (r.status === 429) {
              (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Geocoding rate limit reached — please wait', { type: 'warning' }) : null;
              break; // don't retry on rate-limit
            }
            // Retry for 5xx/502 transient errors
            if (attempt < maxAttempts) {
              await new Promise(res => setTimeout(res, 300 * attempt));
              continue;
            }
            return [];
          }
          const body = await r.json().catch(() => null);
          // TEMP DEBUG: log raw geocode proxy response for diagnosis
          try { console.debug('geocode proxy raw body', body); } catch (e) {}
          // Support multiple response shapes:
          // - LocationIQ primary wrapper: { value: [...] }
          // - Raw array from Nominatim/LocationIQ: [ ... ]
          // - Some services: { data: [...] } or { results: [...] }
          let arr = [];
          if (Array.isArray(body)) arr = body;
          else if (body && Array.isArray(body.value)) arr = body.value;
          else if (body && Array.isArray(body.data)) arr = body.data;
          else if (body && Array.isArray(body.results)) arr = body.results;
          else arr = [];
          try { console.debug('geocode proxy normalized arr', arr); } catch (e) {}
          try { nominatimSearchCache.set(q, arr); } catch (e) {}
          return arr;
        } catch (e) {
          if (e.name === 'AbortError') return [];
          console.warn('Geocode proxy error', e);
          if (attempt < maxAttempts) {
            // small backoff then retry once
            await new Promise(res => setTimeout(res, 300 * attempt));
            continue;
          }
          (window.GOMK && window.GOMK.showToast) ? window.GOMK.showToast('Search failed — try again', { type: 'error' }) : null;
          return [];
        }
      }
      // ensure controller is cleared
      nominatimSearchController = null;
      return [];
    };

    // Reverse geocode (lat,lng) to a display name using Nominatim
    const nominatimReverse = async (lat, lon) => {
      const key = `${lat},${lon}`;
      if (nominatimReverseCache.has(key)) return nominatimReverseCache.get(key);
  const url = `api/geocode_proxy.php?action=reverse&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`;
      try {
        const r = await fetch(url);
        if (!r.ok) {
          console.warn('Geocode proxy reverse returned', r.status);
          return null;
        }
        const body = await r.json().catch(() => null);
        try { nominatimReverseCache.set(key, body); } catch (e) {}
        return body;
      } catch (e) {
        console.warn('Geocode proxy reverse error', e);
        return null;
      }
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
        // Reset margins so CSS margins (which add offsets) don't move the
        // absolutely-positioned element. We'll control max-width in showResults.
        list.style.margin = '0';
        list.style.maxWidth = 'none';
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
  // Ensure the results container doesn't add external margins and limit
  // its maximum width to the input width so centering math is consistent.
  list.style.margin = '0';
  list.style.maxWidth = rect.width + 'px';
  // Set width to auto first, then measure the effective width (which will
  // respect the inline maxWidth we just applied). Finally compute left so
  // the dropdown is centered under the input.
  list.style.width = 'auto';
  // Force reflow to ensure offsetWidth reflects applied styles
  const effectiveWidth = (list.offsetWidth || rect.width);
  const left = rect.left + scrollX + Math.max(0, (rect.width - effectiveWidth) / 2);
  list.style.left = left + 'px';
  list.style.top = (rect.bottom + scrollY) + 'px';
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
        try {
          // Update main marker popup content but don't auto-open it
          const content = it.display_name || it.name || '';
          if (marker.getPopup && marker.getPopup()) {
            marker.getPopup().setContent(content);
          } else {
            marker.bindPopup(content);
          }
        } catch (e) {}
        try {
          const z = (map && typeof map.getMaxZoom === 'function') ? Math.min(map.getMaxZoom(), 16) : 16;
          map.setView([parseFloat(it.lat), parseFloat(it.lon)], z);
        } catch (e) {}
      } catch (e) {}
      const latIn = document.getElementById('location_lat');
      const lngIn = document.getElementById('location_lng');
      const locInput = document.getElementById('location');
  if (latIn) latIn.value = it.lat;
  if (lngIn) lngIn.value = it.lon;
  if (locInput) locInput.value = it.display_name || '';
  // Popups disabled for markers (no bind/unbind) to keep UI clean and reduce resource usage.
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
  }, 350);
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
      // Close the map modal (use the map-specific close function)
      try { if (typeof closeMapModal === 'function') closeMapModal(); else closeModal && closeModal(); } catch (e) { /* ignore */ }
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

    // Expose helper to open the map modal focused on a lat/lng from other UI (e.g. report cards)
    window.__gomkOpenMapAt = (lat, lng, displayName) => {
      try {
        console.log('__gomkOpenMapAt called with:', { lat, lng, displayName });
        
        const numLat = Number(lat);
        const numLng = Number(lng);
        
        if (!numLat || !numLng || isNaN(numLat) || isNaN(numLng)) {
          console.warn('__gomkOpenMapAt called without valid coordinates', { lat, lng, numLat, numLng });
          return;
        }
        
        // Set the chosen place FIRST before initializing map
        chosenPlace = { lat: numLat, lon: numLng, display_name: displayName || 'Report location' };
        console.log('__gomkOpenMapAt - chosenPlace set to:', chosenPlace);
        
        // Enable view-only mode (disable dragging for report viewing)
        isViewOnlyMode = true;
        
        // Ensure map and marker are initialized
        ensureMap();
        
        console.log('__gomkOpenMapAt - marker exists:', !!marker);
        console.log('__gomkOpenMapAt - map exists:', !!map);
        console.log('__gomkOpenMapAt - view-only mode enabled');
        
        // Immediately position the marker and center map at the chosen location
        if (marker && map && chosenPlace) {
          marker.setLatLng([chosenPlace.lat, chosenPlace.lon]);
          map.setView([chosenPlace.lat, chosenPlace.lon], 19, { animate: false });
          const popupText = (chosenPlace.display_name || 'Report Location').toString();
          marker.bindPopup(popupText, { closeButton: true });
          console.log('__gomkOpenMapAt - marker and map centered at:', chosenPlace.lat, chosenPlace.lon);
        }
        
        // Open the modal (which will also refine the view and open popup)
        openMapModal();
      } catch (e) { 
        console.error('__gomkOpenMapAt failed:', e); 
      }
    };

    // Also expose openMapModal globally for fallback
    window.openMapModal = openMapModal;

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
          // Keep it in normal flow so the card expands naturally.
          const group = passwordField.closest('.profile-input-group') || passwordField.parentElement;
          const fieldContainer = group?.parentElement || null; // typically .profile-field
          if (group && fieldContainer && !document.getElementById('passwordConfirmField')) {
            try { group.style.flexWrap = 'nowrap'; } catch (e) { /* ignore */ }
            const wrap = document.createElement('div');
            wrap.className = 'profile-input-group profile-input-group--confirm';
            wrap.id = 'passwordConfirmWrap';
            // Basic layout
            wrap.style.position = 'relative';
            wrap.style.display = 'block';
            wrap.style.marginTop = '8px';
            // Append inside the same field container so it matches the Password input's width
            const cardBody = passwordField.closest('.profile-card-body') || fieldContainer;
            fieldContainer.appendChild(wrap);
            // Trigger the open animation on the next tick
            requestAnimationFrame(() => wrap.classList.add('is-open'));
            const confirm = document.createElement('input');
            confirm.type = 'password';
            confirm.className = 'profile-input';
            confirm.id = 'passwordConfirmField';
            confirm.placeholder = 'Confirm new password';
            confirm.autocomplete = 'new-password';
            confirm.style.width = '100%';
            wrap.appendChild(confirm);
            // Add a single "Show password" checkbox that toggles both fields
            if (!document.getElementById('passwordShowAllRow')) {
              const showRow = document.createElement('label');
              showRow.id = 'passwordShowAllRow';
              showRow.style.display = 'flex';
              showRow.style.alignItems = 'center';
              showRow.style.gap = '10px';
              showRow.style.marginTop = '10px';
              const cb = document.createElement('input');
              cb.type = 'checkbox';
              cb.id = 'passwordShowAllChk';
              const txt = document.createElement('span');
              txt.textContent = 'Show password';
              showRow.appendChild(cb);
              showRow.appendChild(txt);
              // Keep the checkbox aligned with the Password field width as well
              fieldContainer.appendChild(showRow);
              cb.addEventListener('change', () => {
                const type = cb.checked ? 'text' : 'password';
                passwordField.type = type;
                confirm.type = type;
              });
            }
          }
          // Remove any existing per-input toggle buttons if present (we use one checkbox now)
          try { document.getElementById('passwordToggleBtn')?.remove(); } catch (e) {}
          try { document.getElementById('passwordConfirmToggleBtn')?.remove(); } catch (e) {}
          
          // Change button to X (cancel) initially
          editPasswordBtn.innerHTML = `
            <svg viewBox="0 0 24 24">
              <path d="M18 6 6 18"/>
              <path d="m6 6 12 12"/>
            </svg>
          `;
          editPasswordBtn.title = 'Cancel';
          editPasswordBtn.dataset.mode = 'cancel';
          
          // Listen for input changes to switch to checkmark
          const switchToSaveMode = () => {
            const val = passwordField.value.trim();
            if (val.length > 0) {
              editPasswordBtn.innerHTML = `
                <svg viewBox="0 0 24 24">
                  <path d="M20 6L9 17l-5-5"/>
                </svg>
              `;
              editPasswordBtn.title = 'Save password';
              editPasswordBtn.dataset.mode = 'save';
            } else {
              editPasswordBtn.innerHTML = `
                <svg viewBox="0 0 24 24">
                  <path d="M18 6 6 18"/>
                  <path d="m6 6 12 12"/>
                </svg>
              `;
              editPasswordBtn.title = 'Cancel';
              editPasswordBtn.dataset.mode = 'cancel';
            }
          };
          passwordField.addEventListener('input', switchToSaveMode);
          const confirmEl = document.getElementById('passwordConfirmField');
          if (confirmEl) confirmEl.addEventListener('input', switchToSaveMode);
        } else {
          // Check if button is in cancel mode
          if (editPasswordBtn.dataset.mode === 'cancel') {
            // Cancel - reset everything
            passwordField.readOnly = true;
            passwordField.type = 'password';
            passwordField.value = '*************';
            passwordField.placeholder = '';
            const c = document.getElementById('passwordConfirmField');
            const w = document.getElementById('passwordConfirmWrap');
            if (w) { if (w._onResize) window.removeEventListener('resize', w._onResize); w.remove(); } else if (c) c.remove();
            const show = document.getElementById('passwordShowAllRow');
            if (show) show.remove();
            editPasswordBtn.innerHTML = `
              <svg viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            `;
            editPasswordBtn.title = 'Edit password';
            delete editPasswordBtn.dataset.mode;
            return;
          }
          
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
                const show = document.getElementById('passwordShowAllRow');
                if (show) show.remove();
                editPasswordBtn.innerHTML = `
                  <svg viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                `;
                editPasswordBtn.title = 'Edit password';
                delete editPasswordBtn.dataset.mode;
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
          const show = document.getElementById('passwordShowAllRow');
          if (show) show.remove();
          
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

    // Character counter for announcement form
    const announcementTitleInput = document.querySelector('input[name="announcement_title"]');
    const announcementBodyTextarea = document.querySelector('textarea[name="announcement_body"]');
    const headlineCounter = document.getElementById('headline-count');
    const messageCounter = document.getElementById('message-count');

    if (announcementTitleInput && headlineCounter) {
      announcementTitleInput.addEventListener('input', function() {
        headlineCounter.textContent = this.value.length;
      });
    }

    if (announcementBodyTextarea && messageCounter) {
      announcementBodyTextarea.addEventListener('input', function() {
        messageCounter.textContent = this.value.length;
      });
    }
    
  });
