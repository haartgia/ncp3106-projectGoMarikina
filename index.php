<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GO! MARIKINA</title>
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body id="top">
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include './includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main" id="main-content">
            <!-- Search bar + quick actions -->
            <header class="dashboard-header">
                <form class="dashboard-search" role="search">
                    <button type="submit" class="search-button" aria-label="Search reports">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </button>
                    <input type="search" id="reportSearch" name="q" placeholder="Search for Reports, Date, Status" autocomplete="off" aria-label="Search for reports by text or status">
                </form>

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
                                        <p class="notification-title">Road construction report resolved</p>
                                        <p class="notification-meta">5 minutes ago · Barangay San Roque</p>
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
                                        <p class="notification-title">New flooding report near Sto. Niño</p>
                                        <p class="notification-meta">12 minutes ago · Awaiting assignment</p>
                                    </div>
                                </article>
                                <article class="notification-item" role="listitem">
                                    <div class="notification-icon info" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                            <path d="M12 12v4" />
                                            <path d="M12 8h.01" />
                                            <circle cx="12" cy="12" r="10" />
                                        </svg>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-title">Maintenance scheduled for Riverbanks</p>
                                        <p class="notification-meta">45 minutes ago · Public works</p>
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

            <!-- Hero banner keeps branding artwork -->
            <section class="dashboard-hero" id="hero" aria-label="Go Marikina banner">
                <div class="hero-card">
                    <img src="./uploads/go_marikina_logo.png" alt="GO! MARIKINA">
                </div>
            </section>

            <!-- Reports listing; search/filter logic is coordinated via assets/js/script.js -->
            <section class="reports-section" id="reports">
                <div class="reports-header">
                    <h2>Current Reports</h2>
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

                <div class="reports-list" data-empty-message="No reports match your filters yet.">
                    <!-- Example unresolved report card -->
                    <article class="report-card" data-status="unresolved" data-tags="community flooding">
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
                                <span class="chip chip-status unresolved">Unresolved</span>
                            </div>
                        </header>
                        <p class="report-summary">The road construction at Bulelak Street has been dragging bla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla.</p>
                        <figure class="report-media aspect-8-4">
                            <img src="./uploads/flooding.png" alt="Flooding in Marikina">
                        </figure>
                    </article>

                    <!-- Duplicate card used as placeholder data -->
                    <article class="report-card" data-status="unresolved" data-tags="community infrastructure road">
                        <header class="report-card-header">
                            <div class="report-author">
                                <div class="author-avatar" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3>Road Construction</h3>
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
                                <span class="chip chip-status unresolved">Unresolved</span>
                            </div>
                        </header>
                        <p class="report-summary">The road construction at Bulelak Street has been dragging bla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla bla libabla.</p>
                        <figure class="report-media aspect-8-4">
                            <img src="./uploads/road-construction.png" alt="Road construction barriers">
                        </figure>
                    </article>

                    <!-- Sample solved report -->
                    <article class="report-card" data-status="solved" data-tags="community parking">
                        <header class="report-card-header">
                            <div class="report-author">
                                <div class="author-avatar" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3>Illegal Parking</h3>
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
                            <img src="./uploads/no-parking.png" alt="Illegal parking removal">
                        </figure>
                    </article>
                </div>
            </section>
        </main>

    <!-- Floating action button stays pinned bottom-right -->
    <button type="button" class="floating-action" aria-label="Create a new report" onclick="window.location.href='create-report.php'">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <script src="./assets/js/script.js" defer></script>
</body>
</html>