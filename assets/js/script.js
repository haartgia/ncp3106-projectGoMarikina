let lastScrollY = window.scrollY;
const navbar = document.querySelector('.navbar-wrapper');

window.addEventListener('scroll', () => {
    if (!navbar || navbar.classList.contains('search-hidden')) {
        lastScrollY = window.scrollY;
        return;
    }

    if (window.scrollY > lastScrollY) {
        // Scrolling down
        navbar.classList.add('hidden');
    } else {
        // Scrolling up
        navbar.classList.remove('hidden');
    }
    lastScrollY = window.scrollY;
});

// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = "block";
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function openLogin() {
    openModal("loginModal");
}

function openForgotPassword() {
    closeModal("loginModal");
    openModal("forgotModal");
}

function openSignup() {
    closeModal("loginModal");
    window.location.href = "signup.php";
}

function openCreateReport() {
    openModal("createReportModal");
}

// Report Modal Data
const reportData = {
  1: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "UNRESOLVED",
    author: "Miguel De Guzman | Community",
    description: "The road construction at Bulelak Street has been dragging on and it's making life harder for us residents—traffic everywhere, hard to get in and out of our homes, and no clear updates on when this will be finished."
  },
  2: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "UNRESOLVED",
    author: "Miguel De Guzman | Community",
    description: "Flooding along Sumulong has become a serious problem affecting our daily lives and safety. Every time it rains heavily, the water level rises quickly."
  },
  3: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "SOLVED",
    author: "Miguel De Guzman | Community",
    description: "Grabe na 'yung illegal parking dito sa area—halos kalagihati ng kalsada natatakpan na ng mga sasakyang nakaparada. This has been resolved by local authorities."
  },
  4: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "UNRESOLVED",
    author: "Maria Santos | Infrastructure",
    description: "Bridge maintenance needed for safety concerns. The structure shows signs of wear and requires immediate attention."
  },
  5: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "SOLVED",
    author: "Juan Dela Cruz | Environment",
    description: "Garbage collection issue has been resolved. Regular pickup schedule has been restored in the area."
  },
  6: {
    image: "https://hebbkx1anhila5yf.public.blob.vercel-storage.com/image-nnvLXZXV5lY4EPcfVxRvpqbcXUHcwp.png",
    status: "UNRESOLVED",
    author: "Ana Rodriguez | Community",
    description: "Street lighting needs improvement for public safety. Several lamp posts are not working, making the area dark at night."
  }
};

function openReportModal(reportId) {
  const report = reportData[reportId];
  if (report) {
    document.getElementById("reportImage").src = report.image;
    document.getElementById("reportStatus").textContent = report.status;
    document.getElementById("reportStatus").className = report.status.toLowerCase();
    document.getElementById("reportAuthor").textContent = report.author;
    document.getElementById("reportDescription").textContent = report.description;
    openModal("reportModal");
  }
}

// Close modals when clicking outside
document.addEventListener("DOMContentLoaded", () => {
    const descriptions = document.querySelectorAll(".report-description");
    descriptions.forEach((desc) => {
        const words = desc.textContent.trim().split(/\s+/);
        if (words.length >= 20) {
            const truncated = words.slice(0, 20).join(" ");
            desc.textContent = `${truncated} ....`;
        }
    });

    window.addEventListener("click", (event) => {
        const modals = document.querySelectorAll(".modal");
        modals.forEach((modal) => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });

    const navWrapper = document.querySelector('.navbar-wrapper');
    const navSearchTrigger = document.querySelector('.nav-search-trigger');
    const navSearchOverlay = document.querySelector('.navbar-search-overlay');
    const navSearchClose = document.querySelector('.navbar-search-close');
    const navSearchInput = document.getElementById('navbarSearchInput');
    const navSearchForm = document.querySelector('.navbar-search-form');

    const reportsGrid = document.querySelector('.reports-grid');
    const reportCards = reportsGrid ? Array.from(reportsGrid.querySelectorAll('.report-card')) : [];
    let noResultsMessage = null;
    let currentSearchQuery = '';
    let currentStatusFilter = 'all';

    reportCards.forEach((card) => {
        if (!card.dataset.searchText) {
            card.dataset.searchText = card.textContent.toLowerCase();
        }

        if (!card.dataset.status) {
            const statusElement = card.querySelector('.report-status');
            if (statusElement) {
                card.dataset.status = statusElement.textContent.trim().toLowerCase();
            }
        }
    });

    const getOrCreateNoResultsMessage = () => {
        if (!reportsGrid) return null;
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.className = 'reports-empty-state';
            noResultsMessage.textContent = 'No reports match your filters yet.';
            reportsGrid.appendChild(noResultsMessage);
        }
        return noResultsMessage;
    };

    const updateNoResultsVisibility = (visibleCount) => {
        const message = getOrCreateNoResultsMessage();
        if (message) {
            message.style.display = visibleCount ? 'none' : 'flex';
        }
    };

    const applyFilters = () => {
        if (!reportsGrid) return;

        let matches = 0;
        reportCards.forEach((card) => {
            const searchText = card.dataset.searchText || '';
            const status = card.dataset.status || '';
            const matchesSearch = !currentSearchQuery || searchText.includes(currentSearchQuery);
            const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

            if (matchesSearch && matchesStatus) {
                card.style.removeProperty('display');
                matches += 1;
            } else {
                card.style.display = 'none';
            }
        });

        updateNoResultsVisibility(matches);
    };

    const resetSearchFilters = () => {
        currentSearchQuery = '';
        if (navSearchInput) {
            navSearchInput.value = '';
        }
        applyFilters();
    };

    const openNavbarSearch = () => {
        if (navWrapper) {
            navWrapper.classList.add('search-hidden');
            navWrapper.classList.remove('hidden');
        }

        if (navSearchOverlay) {
            navSearchOverlay.classList.add('active');
            navSearchOverlay.setAttribute('aria-hidden', 'false');
        }

        if (navSearchInput) {
            navSearchInput.value = '';
            navSearchInput.focus();
        }

        resetSearchFilters();

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('touchstart', handleOutsideClick);
    };

    const closeNavbarSearch = () => {
        if (navSearchOverlay) {
            navSearchOverlay.classList.remove('active');
            navSearchOverlay.setAttribute('aria-hidden', 'true');
        }

        if (navWrapper) {
            navWrapper.classList.remove('search-hidden');
            navWrapper.classList.remove('hidden');
        }

        if (navSearchInput) {
            navSearchInput.value = '';
        }

        resetSearchFilters();

        if (navSearchTrigger) {
            navSearchTrigger.focus();
        }

        document.removeEventListener('mousedown', handleOutsideClick);
        document.removeEventListener('touchstart', handleOutsideClick);
    };

    navSearchTrigger?.addEventListener('click', (event) => {
        event.preventDefault();
        openNavbarSearch();
    });

    navSearchClose?.addEventListener('click', (event) => {
        event.preventDefault();
        closeNavbarSearch();
    });

    navSearchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        currentSearchQuery = (navSearchInput?.value || '').trim().toLowerCase();
        applyFilters();
    });

    navSearchInput?.addEventListener('input', (event) => {
        currentSearchQuery = event.target.value.trim().toLowerCase();
        applyFilters();
    });

    navSearchOverlay?.addEventListener('click', (event) => {
        if (event.target === navSearchOverlay) {
            closeNavbarSearch();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && navSearchOverlay?.classList.contains('active')) {
            closeNavbarSearch();
        }
    });

    function handleOutsideClick(event) {
        if (!navSearchOverlay?.classList.contains('active')) {
            return;
        }

        const clickedInsideOverlay = navSearchOverlay.contains(event.target);
        const clickedTrigger = navSearchTrigger?.contains(event.target);

        if (!clickedInsideOverlay && !clickedTrigger) {
            closeNavbarSearch();
        }
    }

    const filterControl = document.querySelector('.reports-filter-control');
    const filterButton = filterControl?.querySelector('.filter-btn');
    const filterDropdown = filterControl?.querySelector('.filter-dropdown');
    const filterOptions = filterDropdown ? Array.from(filterDropdown.querySelectorAll('.filter-option')) : [];
    const defaultFilterLabel = filterButton?.dataset.defaultLabel || filterButton?.textContent.trim();

    const updateFilterButtonLabel = () => {
        if (!filterButton) return;
        if (currentStatusFilter === 'all') {
            filterButton.textContent = defaultFilterLabel;
        } else {
            const activeOption = filterOptions.find((option) => option.dataset.filter === currentStatusFilter);
            const label = activeOption?.dataset.label || currentStatusFilter;
            filterButton.textContent = `${defaultFilterLabel}: ${label}`;
        }
    };

    const closeFilterDropdown = () => {
        if (!filterDropdown || !filterButton) return;
        filterDropdown.classList.remove('open');
        filterDropdown.hidden = true;
        filterButton.setAttribute('aria-expanded', 'false');
        document.removeEventListener('mousedown', handleFilterOutsideClick);
        document.removeEventListener('keydown', handleFilterEscape);
    };

    const openFilterDropdown = () => {
        if (!filterDropdown || !filterButton) return;
        filterDropdown.hidden = false;
        filterDropdown.classList.add('open');
        filterButton.setAttribute('aria-expanded', 'true');
        document.addEventListener('mousedown', handleFilterOutsideClick);
        document.addEventListener('keydown', handleFilterEscape);
    };

    const toggleFilterDropdown = () => {
        if (!filterDropdown || !filterButton) return;
        if (filterDropdown.classList.contains('open')) {
            closeFilterDropdown();
        } else {
            openFilterDropdown();
        }
    };

    const handleFilterOutsideClick = (event) => {
        if (!filterControl?.contains(event.target)) {
            closeFilterDropdown();
        }
    };

    const handleFilterEscape = (event) => {
        if (event.key === 'Escape') {
            closeFilterDropdown();
            filterButton?.focus();
        }
    };

    filterButton?.addEventListener('click', (event) => {
        event.preventDefault();
        toggleFilterDropdown();
    });

    filterOptions.forEach((option) => {
        option.addEventListener('click', () => {
            const selectedFilter = option.dataset.filter || 'all';
            currentStatusFilter = selectedFilter;

            filterOptions.forEach((opt) => {
                const isActive = opt === option;
                opt.classList.toggle('active', isActive);
                opt.setAttribute('aria-checked', isActive ? 'true' : 'false');
            });

            updateFilterButtonLabel();
            applyFilters();
            closeFilterDropdown();
        });
    });

    updateFilterButtonLabel();
    applyFilters();
});

// Form submissions
document.addEventListener("submit", (e) => {
    e.preventDefault();
    const form = e.target;
    if (form.classList.contains('navbar-search-form')) {
        return;
    }
    const formData = new FormData(form);
    console.log("Form submitted:", Object.fromEntries(formData));
    alert("Form submitted successfully!");
});
