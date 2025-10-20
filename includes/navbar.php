<?php
  require_once __DIR__ . '/../config/auth.php';

  // Determine the current PHP page so we can mark the matching nav link as active.
  $currentPath = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if ($currentPath === '' || $currentPath === false) {
    $currentPath = 'index.php';
  }

  $isHome = $currentPath === 'index.php';
  $isCreateReport = $currentPath === 'create-report.php';
  $isDashboard = $currentPath === 'dashboard.php';
  $isProfile = $currentPath === 'profile.php';
  $isAdmin = $currentPath === 'admin.php';
  $isAnnouncements = $currentPath === 'announcements.php';

  // When already on the dashboard we use in-page anchors; otherwise link back to index.
  $homeHref = $isHome ? '#top' : 'index.php#top';
  $reportsHref = $isHome ? '#reports' : 'index.php#reports';
?>

<!-- Navigation background scrim -->
<div class="nav-scrim" aria-hidden="true"></div>

<aside id="primary-sidebar" class="sidebar" aria-label="Primary navigation">
  <div class="sidebar-profile" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" data-user-menu-toggle>
    <div class="sidebar-avatar" aria-hidden="true">
      <svg viewBox="0 0 24 24" role="presentation" focusable="false">
        <circle cx="12" cy="8" r="4" />
        <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
      </svg>
    </div>
    <div class="sidebar-profile-text">
      <span class="sidebar-greeting">User</span>
    </div>
  </div>

  <?php if (is_logged_in()): ?>
  <div class="sidebar-user-menu" data-user-menu hidden>
    <a href="profile.php" class="sidebar-user-item">Profile</a>
    <a href="logout.php" class="sidebar-user-item danger">Log out</a>
  </div>
  <?php endif; ?>

  <nav class="sidebar-nav">
    <?php if (is_admin()): ?>
      <a class="sidebar-link<?= $isAdmin ? ' active' : '' ?>" href="admin.php"<?= $isAdmin ? ' aria-current="page"' : '' ?> data-section="admin">
        <span class="sidebar-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="presentation" focusable="false">
            <circle cx="10" cy="8" r="3.5" />
            <path d="M3 20c0-3.87 3.13-7 7-7 1.82 0 3.53.62 4.86 1.65" />
            <circle cx="18" cy="16" r="2.5" />
            <path d="M18 12.75v1.5" />
            <path d="M18 17.75v1.5" />
            <path d="M21.25 16h-1.5" />
            <path d="M16.25 16h-1.5" />
            <path d="m20.17 13.83-.9.9" />
            <path d="m18.9 18.1.9.9" />
            <path d="m15.83 14.73.9.9" />
            <path d="m16.73 17.27-.9.9" />
          </svg>
        </span>
        <span class="sidebar-label">Admin Panel</span>
      </a>
      <a class="sidebar-link<?= $isAnnouncements ? ' active' : '' ?>" href="announcements.php"<?= $isAnnouncements ? ' aria-current="page"' : '' ?> data-section="announcements">
        <span class="sidebar-icon" aria-hidden="true">
          <svg viewBox="0 0 1920 1920" role="presentation" focusable="false">
            <path d="M1587.162 31.278c11.52-23.491 37.27-35.689 63.473-29.816 25.525 6.099 43.483 28.8 43.483 55.002V570.46C1822.87 596.662 1920 710.733 1920 847.053c0 136.32-97.13 250.503-225.882 276.705v513.883c0 26.202-17.958 49.016-43.483 55.002a57.279 57.279 0 0 1-12.988 1.468c-21.12 0-40.772-11.745-50.485-31.171C1379.238 1247.203 964.18 1242.347 960 1242.347H564.706v564.706h87.755c-11.859-90.127-17.506-247.003 63.473-350.683 52.405-67.087 129.657-101.082 229.948-101.082v112.941c-64.49 0-110.57 18.861-140.837 57.487-68.781 87.868-45.064 263.83-30.269 324.254 4.18 16.828.34 34.673-10.277 48.34-10.73 13.665-27.219 21.684-44.499 21.684H508.235c-31.171 0-56.47-25.186-56.47-56.47v-621.177h-56.47c-155.747 0-282.354-126.607-282.354-282.353v-56.47h-56.47C25.299 903.523 0 878.336 0 847.052c0-31.172 25.299-56.471 56.47-56.471h56.471v-56.47c0-155.634 126.607-282.354 282.353-282.354h564.593c16.941-.112 420.48-7.002 627.275-420.48Zm-5.986 218.429c-194.71 242.371-452.216 298.164-564.705 311.04v572.724c112.489 12.876 369.995 68.556 564.705 311.04ZM903.53 564.7H395.294c-93.402 0-169.412 76.01-169.412 169.411v225.883c0 93.402 76.01 169.412 169.412 169.412H903.53V564.7Zm790.589 123.444v317.93c65.618-23.379 112.94-85.497 112.94-159.021 0-73.525-47.322-135.53-112.94-158.909Z" fill="currentColor" fill-rule="evenodd" stroke="none" />
          </svg>
        </span>
        <span class="sidebar-label">Announcements</span>
      </a>
      <a class="sidebar-link<?= $isDashboard ? ' active' : '' ?>" href="dashboard.php"<?= $isDashboard ? ' aria-current="page"' : '' ?> data-section="dashboard">
        <span class="sidebar-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" role="presentation" focusable="false">
            <path d="M4 4h6v6H4zm10 0h6v10h-6zm-10 10h6v6H4zm10 4h6v2h-6z" />
          </svg>
        </span>
        <span class="sidebar-label">Dashboard</span>
      </a>
    <?php else: ?>
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
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
        <?php if (is_logged_in()): ?>
      <a href="logout.php" class="sidebar-logout">Log out</a>
    <?php endif; ?>
    <img src="./uploads/small_go_marikina_logo.png" alt="Go Marikina logo" class="sidebar-logo">
  </div>
</aside>
