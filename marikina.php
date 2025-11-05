<?php
require_once __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marikina River · GO! MARIKINA</title>
    <?php 
        $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
        $jsVersion = @filemtime(__DIR__ . '/assets/js/script.js') ?: time();
    ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= $cssVersion ?>">
</head>
<body id="top">
    <div class="dashboard-layout">
        <button type="button" class="mobile-nav-toggle" data-nav-toggle aria-controls="primary-sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="mobile-nav-toggle-bars" aria-hidden="true"></span>
        </button>
        <?php include __DIR__ . '/includes/navbar.php'; ?>
        <div class="mobile-nav-scrim" data-nav-scrim hidden></div>

        <main class="dashboard-main city-main" id="main-content">
            <header class="city-hero">
                <div class="city-hero-text">
                    <p class="city-hero-kicker">City operations command</p>
                    <h1 class="city-hero-title">Marikina River</h1>
                    <p class="city-hero-subtitle">Live telemetry for the Marikina River. This page mirrors the dashboard layout and is ready for API integration.</p>
                    <div class="city-hero-actions">
                        <a class="city-hero-button" href="dashboard.php" aria-label="Back to City Dashboard" title="Back to City Dashboard">Back to dashboard</a>
                        <a class="city-hero-button city-hero-button--primary" href="#river-telemetry" aria-label="Jump to river telemetry" title="Jump to river telemetry">View telemetry</a>
                    </div>
                </div>
                <div class="city-hero-card">
                    <p class="city-card-label">Integration status</p>
                    <p class="city-selection-meta">API: <strong>Not connected</strong></p>
                    <p class="city-selection-note">Once the river API endpoint is configured, live graphs and readings will appear below.</p>
                </div>
            </header>

            <section class="admin-section city-section" id="river-telemetry">
                <div class="admin-section-header city-section-header">
                    <div>
                        <h2>River telemetry snapshot</h2>
                        <p class="admin-section-subtitle">Water level, flow rate, and rainfall at a glance.</p>
                    </div>
                </div>

                <div class="city-metric-grid">
                    <article class="city-metric-card city-metric-card--water">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Water level <span class="status-icon status-gray" title="Water level icon"><i class="bi bi-moisture" aria-hidden="true"></i></span></p>
                            <p class="city-metric-value"><span id="riverWaterLevel" data-city-metric-value data-empty="true">—</span><span class="city-metric-unit">m</span></p>
                        </div>
                        <p class="city-metric-description">Latest gauge reading from Marikina River.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline"><canvas id="riverWaterChart"></canvas></div>
                            <time id="riverWaterTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--air">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Flow rate <span class="status-icon status-gray" title="Flow rate icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h8M12 12c4 0 4-6 8-6"/></svg></span></p>
                            <p class="city-metric-value"><span id="riverFlowRate" data-city-metric-value data-empty="true">—</span><span class="city-metric-unit">m³/s</span></p>
                        </div>
                        <p class="city-metric-description">Computed from upstream gauges.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline"><canvas id="riverFlowChart"></canvas></div>
                            <time id="riverFlowTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--temperature">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Rainfall <span class="status-icon status-gray" title="Rainfall icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 18l2-2m2 2l2-2M6 8a6 6 0 1 1 12 0 4 4 0 0 1 0 8H6a4 4 0 0 1 0-8z"/></svg></span></p>
                            <p class="city-metric-value"><span id="riverRain" data-city-metric-value data-empty="true">—</span><span class="city-metric-unit">mm</span></p>
                        </div>
                        <p class="city-metric-description">Accumulated precipitation affecting river levels.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline"><canvas id="riverRainChart"></canvas></div>
                            <time id="riverRainTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <?php include __DIR__ . '/includes/footer.php'; ?>
    </div>

    <script>
        // Minimal placeholder to show where data binding will happen
        // Replace with real fetch() against your river API endpoint later.
        document.addEventListener('DOMContentLoaded', () => {
            // Example wiring: fetch(`${BASE_URL}/api/marikina_river.php`).then(...)
        });
    </script>
</body>
</html>
