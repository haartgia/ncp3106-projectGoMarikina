document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.querySelector('.dashboard-search');
  const searchInput = document.querySelector('#reportSearch');
  const reportsList = document.querySelector('.reports-list');
  const reportCards = Array.from(document.querySelectorAll('.report-card'));

  const filterToggle = document.querySelector('.filter-toggle');
  const filterMenu = document.getElementById('reportFilterMenu');
  const filterOptions = filterMenu ? Array.from(filterMenu.querySelectorAll('.filter-option')) : [];
  const filterLabel = filterToggle?.querySelector('span');

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
    const normalized = query.toLowerCase();
    const contains = (word) => new RegExp(`\\b${word}\\b`).test(normalized);

    if (contains('unresolved') || contains('unsolved') || contains('pending')) {
      return 'unresolved';
    }

    if (contains('solved') || contains('resolved')) {
      return 'solved';
    }

    return null;
  };

  // Strip status keywords from the free-text portion so we don't double-filter.
  const removeStatusWords = (query) => {
    if (!query) return '';
    return query
      .replace(/\b(solved|resolved|unsolved|unresolved|pending)\b/gi, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  };

  // Sync the filter pill UI and aria state with the active status.
  const updateFilterUI = (status, { inferred = false } = {}) => {
    if (filterOptions.length) {
      filterOptions.forEach((option) => {
        const isActive = option.dataset.status === status;
        option.classList.toggle('active', isActive);
        option.setAttribute('aria-checked', isActive ? 'true' : 'false');
      });
    }

    if (filterLabel) {
      filterLabel.textContent = status === 'all' ? 'Filter' : `Filter: ${status.charAt(0).toUpperCase()}${status.slice(1)}`;
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

  searchForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    applyFilters();
  });

  applyFilters();

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
});
