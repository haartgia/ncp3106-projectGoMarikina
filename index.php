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
        <?php include './includes/navbar.php'; ?>

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
                    <button type="button" class="action-icon" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3-6 8-6s8 2 8 6" />
                        </svg>
                    </button>
                    <button type="button" class="action-icon" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                            <path d="M18 16v-5a6 6 0 0 0-12 0v5l-2 2h16z" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                    </button>
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
    <button type="button" class="floating-action" aria-label="Create a new report">
            <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                <rect x="11" y="5" width="2" height="14" rx="1" />
                <rect x="5" y="11" width="14" height="2" rx="1" />
            </svg>
        </button>
    </div>

    <script src="./assets/js/script.js" defer></script>
</body>
</html>