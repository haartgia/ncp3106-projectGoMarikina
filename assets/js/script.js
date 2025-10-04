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
    }
  }

  // Dashboard notification popover toggle.
  const notificationContainer = document.querySelector('[data-notification]');

  if (notificationContainer) {
    const notificationToggle = notificationContainer.querySelector('[data-notification-toggle]');
    const notificationPanel = notificationContainer.querySelector('[data-notification-panel]');
    const notificationDot = notificationContainer.querySelector('.notification-dot');
    const markReadButton = notificationContainer.querySelector('[data-notification-mark-read]');

    let notificationsOpen = false;

    const setNotificationOpen = (open) => {
      if (!notificationToggle || !notificationPanel) return;
      notificationsOpen = Boolean(open);
      notificationContainer.dataset.open = notificationsOpen ? 'true' : 'false';
      notificationToggle.setAttribute('aria-expanded', notificationsOpen ? 'true' : 'false');
      notificationPanel.hidden = !notificationsOpen;
      if (notificationsOpen) {
        notificationPanel?.focus?.({ preventScroll: true });
      }
    };

    setNotificationOpen(false);

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
      markReadButton.textContent = 'All caught up';
      markReadButton.setAttribute('aria-disabled', 'true');
      markReadButton.classList.add('is-disabled');
      if (notificationDot && !notificationDot.hasAttribute('hidden')) {
        notificationDot.setAttribute('hidden', 'hidden');
      }
    });
  }

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

      button.addEventListener('click', (event) => {
        event.preventDefault();
        const isVisible = button.dataset.visible === 'true';
        setVisibility(!isVisible);
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

      // File input change
      photoInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
          handleFileSelect(e.target.files[0]);
        }
      });

      function handleFileSelect(file) {
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = (e) => {
            showCropModal(e.target.result);
          };
          reader.readAsDataURL(file);
        }
      }

      // Cropping functionality
      let cropImage = null;
      let cropBox = null;
      let isDragging = false;
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
          
          // Initialize crop box with scaled dimensions
          initializeCropBox(img.width * scale, img.height * scale);
          cropImage = img;
        };
        img.src = imageSrc;
      }

      function initializeCropBox(imageWidth, imageHeight) {
        const cropBoxElement = document.getElementById('cropBox');
        
        if (!cropBoxElement) return;
        
        cropBoxElement.innerHTML = '';
        
        const aspectRatio = 8/4;
        
        // Calculate maximum possible size while maintaining 8:4 ratio
        let cropWidth = imageWidth;
        let cropHeight = cropWidth / aspectRatio;
        
        // If height exceeds image bounds, scale down based on height
        if (cropHeight > imageHeight) {
          cropHeight = imageHeight;
          cropWidth = cropHeight * aspectRatio;
        }
        
        // Scale down slightly to ensure it fits within the image
        cropWidth *= 0.95;
        cropHeight *= 0.95;
        
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
        
        const actualX = cropBox.x * scaleX;
        const actualY = cropBox.y * scaleY;
        const actualWidth = cropBox.width * scaleX;
        const actualHeight = cropBox.height * scaleY;
        
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
          
          if (!category) {
            alert('Please select a category');
            return;
          }
          
          if (!title.trim()) {
            alert('Please enter a title for your concern');
            return;
          }
          
          if (!description.trim()) {
            alert('Please enter a description');
            return;
          }
          
          if (!location.trim()) {
            alert('Please enter a location');
            return;
          }
          
          alert('Report submitted successfully!\\n\\nTitle: ' + title + '\\nCategory: ' + category + '\\nDescription: ' + description + '\\nLocation: ' + location);
          clearForm();
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
      if (clearFormBtn) {
        clearFormBtn.addEventListener('click', clearForm);
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
          if (newPassword.length >= 6) {
            passwordField.readOnly = true;
            passwordField.value = 'M*************';
            passwordField.placeholder = '';
            
            // Change button back to edit
            editPasswordBtn.innerHTML = `
              <svg viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            `;
            editPasswordBtn.title = 'Edit password';
            
            alert('Password updated successfully!');
          } else {
            alert('Password must be at least 6 characters long');
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
          mobileField.value = '+63 ';
          mobileField.focus();
          
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
          if (newMobile.length >= 10 && newMobile.startsWith('+63')) {
            mobileField.readOnly = true;
            
            // Change button back to edit
            editMobileBtn.innerHTML = `
              <svg viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            `;
            editMobileBtn.title = 'Edit mobile number';
            
            alert('Mobile number updated successfully!');
          } else {
            alert('Please enter a valid Philippine mobile number starting with +63');
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
          mobileField.value = '+63 9451234567';
          
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
          
          // Show success message
          alert('Login successful! Welcome to your profile.');
          
          console.log('User logged in successfully');
        } else {
          alert('Please enter both email and password');
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
          
          // Show success message
          alert('Login successful! You can now create a report.');
          
          console.log('User logged in successfully for create report');
        } else {
          alert('Please enter both email and password');
        }
      });
    }
  };

  // Initialize create report login functionality if on create-report page
  if (document.getElementById('createReportContent')) {
    initializeCreateReportLogin();
  }
});
