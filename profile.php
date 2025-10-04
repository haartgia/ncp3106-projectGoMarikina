<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROFILE - GO! MARIKINA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-logo">
                    <img src="./uploads/go_marikina_logo.png" alt="GO! MARIKINA" class="header-logo-img">
                </div>
                <h1 class="profile-title">PROFILE</h1>
                <div class="dashboard-actions">
                    <a href="profile.php" class="action-icon" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                        </svg>
                    </a>
                    <div class="notification" data-notification>
                        <button type="button" class="action-icon notification-toggle" aria-label="Open notifications" aria-expanded="false" aria-haspopup="true" data-notification-toggle data-notification-target="dashboardNotifications">
                            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                <path d="M18 16v-5a6 6 0 0 0-12 0v5l-2 2h16z" />
                                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                            <span class="notification-dot" aria-hidden="true"></span>
                        </button>
                        <section class="notification-panel" id="dashboardNotifications" aria-labelledby="notificationPanelTitle" role="dialog" aria-modal="false" tabindex="-1" hidden data-notification-panel>
                            <header class="notification-panel-header">
                                <h3 id="notificationPanelTitle">Notifications</h3>
                                <button type="button" class="notification-action" data-notification-mark-read>Mark all as read</button>
                            </header>
                            <div class="notification-list" role="list">
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M7.5 12.5 10.8 15.5 16.5 9" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Profile updated successfully</p>
                                        <p class="notification-meta">2 minutes ago</p>
                                    </div>
                                </article>
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon warning" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M12 9v4" />
                                            <path d="M12 17h.01" />
                                            <path d="M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L12.71 3.86a1 1 0 0 0-1.72 0z" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">New report submitted</p>
                                        <p class="notification-meta">15 minutes ago</p>
                                    </div>
                                </article>
                            </div>
                            <footer class="notification-panel-footer">
                                <a href="#" class="notification-footer-link">View all activity</a>
                            </footer>
                        </section>
                    </div>
                </div>
            </header>

            <?php include __DIR__ . '/includes/login.php'; ?>

            <!-- Profile Content (Hidden until login) -->
            <div id="profileContent" style="display: none;">
                <!-- Profile Information Section -->
                <section class="profile-section">
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h3>User Details</h3>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-info">
                                <div class="profile-field">
                                    <label class="profile-label">Name</label>
                                    <input type="text" class="profile-input" value="Miguel De Guzman" readonly>
                                </div>
                                
                                <div class="profile-field">
                                    <label class="profile-label">Email</label>
                                    <input type="email" class="profile-input" value="miguelivan@gmail.com" readonly>
                                </div>
                                
                                <div class="profile-field">
                                    <label class="profile-label">Password</label>
                                    <div class="profile-input-group">
                                        <input type="password" class="profile-input" value="M*************" readonly id="passwordField">
                                        <button type="button" class="profile-edit-btn" id="editPasswordBtn" data-field="password">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="profile-field">
                                    <label class="profile-label">Mobile Number</label>
                                    <div class="profile-input-group">
                                        <input type="tel" class="profile-input" value="+63 9451234567" readonly id="mobileField">
                                        <button type="button" class="profile-edit-btn" id="editMobileBtn" data-field="mobile">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Reports Summary -->
                <div class="reports-summary">
                    <h2>No. of Reports 7</h2>
                </div>

                <!-- Dividing Line -->
                <div class="dividing-line"></div>

                <!-- Reports Section -->
                <section class="reports-section">
                    <div class="reports-header">
                        <h3>Your Reports</h3>
                        <div class="reports-filter">
                            <button type="button" class="filter-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="reportFilterMenu">
                                <span>Filter</span>
                                <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                    <path d="M4 5h16M7 12h10m-6 7h2" />
                                </svg>
                            </button>
                            <div class="filter-menu" id="reportFilterMenu" role="menu" hidden>
                                <button type="button" class="filter-option active" data-status="all" role="menuitemradio" aria-checked="true">All Reports</button>
                                <button type="button" class="filter-option" data-status="unresolved" role="menuitemradio" aria-checked="false">Unresolved</button>
                                <button type="button" class="filter-option" data-status="solved" role="menuitemradio" aria-checked="false">Solved</button>
                            </div>
                        </div>
                    </div>

                    <div class="reports-list">
                        <!-- Sample report card -->
                        <article class="report-card" data-status="solved" data-tags="community flooding">
                            <header class="report-card-header">
                                <div class="report-author">
                                    <div class="author-avatar" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3>Flooding</h3>
                                        <p>Miguel De Guzman</p>
                                    </div>
                                </div>
                                <div class="report-header-actions">
                                    <button type="button" class="icon-button" aria-label="View location">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z" />
                                        </svg>
                                    </button>
                                    <button type="button" class="icon-button" aria-label="Share report">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
                                            <path d="m7 9 5-6 5 6" />
                                            <path d="M12 3v13" />
                                        </svg>
                                    </button>
                                    <span class="chip chip-category">Community</span>
                                    <span class="chip chip-status solved">Solved</span>
                                </div>
                            </header>
                            <p class="report-summary">The road construction at Bulelak Street has been dragging bla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla.</p>
                            <figure class="report-media aspect-8-4">
                                <img src="./uploads/flooding.png" alt="Flooding in Marikina">
                            </figure>
                        </article>
                    </div>
                </section>
            </div>
        </main>

        <!-- Floating action button -->
        <button type="button" class="floating-action" aria-label="Create a new report" onclick="window.location.href='create-report.php'">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <script src="assets/js/script.js" defer></script>
</body>
</html>
