<?php
  // Determine the current PHP page so we can mark the matching nav link as active.
  $currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($currentPath === '' || $currentPath === false) {
    $currentPath = 'index.php';
  }

  $isHome = $currentPath === 'index.php';
  $isCreateReport = $currentPath === 'create-report.php';
  $isDashboard = $currentPath === 'dashboard.php';
  $isProfile = $currentPath === 'profile.php';

  // When already on the dashboard we use in-page anchors; otherwise link back to index.
  $homeHref = $isHome ? '#top' : 'index.php#top';
  $reportsHref = $isHome ? '#reports' : 'index.php#reports';
?>

<aside id="primary-sidebar" class="sidebar" aria-label="Primary navigation">
  <div class="sidebar-profile">
    <div class="sidebar-avatar" aria-hidden="true">
      <svg viewBox="0 0 24 24" role="presentation" focusable="false">
        <circle cx="12" cy="8" r="4" />
        <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
      </svg>
    </div>
    <div class="sidebar-profile-text">
      <span class="sidebar-greeting">Miguel</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <!-- data-section hooks into scroll spy logic so highlights follow the visible section -->
    <a class="sidebar-link<?= $isHome ? ' active' : '' ?>" href="<?= $homeHref ?>" data-section="hero"<?= $isHome ? ' aria-current="page"' : '' ?>>
      <span class="sidebar-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
          <path d="M3 11.5L12 3l9 8.5V21a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z" />
        </svg>
      </span>
      <span class="sidebar-label">Home</span>
    </a>
    <a class="sidebar-link" href="<?= $reportsHref ?>" data-section="reports">
      <span class="sidebar-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
          <path d="M5 4h14a1 1 0 0 1 1 1v14l-4-3-4 3-4-3-4 3V5a1 1 0 0 1 1-1z" />
        </svg>
      </span>
      <span class="sidebar-label">Reports</span>
    </a>
    <a class="sidebar-link<?= $isCreateReport ? ' active' : '' ?>" href="create-report.php"<?= $isCreateReport ? ' aria-current="page"' : '' ?>>
      <span class="sidebar-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
          <path d="M12 5v14m-7-7h14" />
        </svg>
      </span>
      <span class="sidebar-label">Create a Report</span>
    </a>
    <a class="sidebar-link<?= $isDashboard ? ' active' : '' ?>" href="dashboard.php"<?= $isDashboard ? ' aria-current="page"' : '' ?>>
      <span class="sidebar-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
          <path d="M4 4h6v6H4zm10 0h6v10h-6zm-10 10h6v6H4zm10 4h6v2h-6z" />
        </svg>
      </span>
      <span class="sidebar-label">Dashboard</span>
    </a>
    <a class="sidebar-link<?= $isProfile ? ' active' : '' ?>" href="profile.php"<?= $isProfile ? ' aria-current="page"' : '' ?>>
      <span class="sidebar-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
        </svg>
      </span>
      <span class="sidebar-label">Profile</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <img src="./uploads/small_go_marikina_logo.png" alt="Go Marikina logo" class="sidebar-logo">
  </div>
</aside>
