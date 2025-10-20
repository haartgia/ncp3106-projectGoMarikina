<?php
require __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$userRole = $_SESSION['role'] ?? 'user';

$barangays = [
    'District I' => [
        'Sto. Niño', 'Malanday', 'Barangka', 'San Roque', 'Jesus Dela Peña',
        'Tañong', 'Kalumpang', 'Industrial Valley Complex', 'Sta. Elena'
    ],
    'District II' => [
        'Concepcion Uno', 'Tumana', 'Concepcion Dos', 'Marikina Heights',
        'Nangka', 'Parang', 'Fortune'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · GO! MARIKINA</title>
    <?php $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/'); ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= time() ?>">
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
                    <h1 class="city-hero-title">City Dashboard</h1>
                    <p class="city-hero-subtitle">Monitor ambient conditions, keep barangay responders aligned, and surface telemetry trends from a single view.</p>
                    <div class="city-hero-actions">
                        <a class="city-hero-button city-hero-button--primary" href="#city-metrics">View live sensors</a>
                        <a class="city-hero-button" href="index.php#reports">Review citizen reports</a>
                    </div>
                </div>
                <div class="city-hero-card">
                    <p class="city-card-label">Barangay selection</p>
                    <label for="barangaySelect" class="city-select-label">Choose a barangay to focus the readings</label>
                    <div class="city-select-wrapper">
                        <select id="barangaySelect" class="city-select">
                            <option value="" disabled selected>Choose a barangay...</option>
                            <?php foreach ($barangays as $district => $list): ?>
                                <optgroup label="<?= htmlspecialchars($district) ?>">
                                    <?php foreach ($list as $barangay): ?>
                                        <option value="<?= htmlspecialchars($barangay) ?>">
                                            <?= htmlspecialchars($barangay) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <span class="city-select-arrow" aria-hidden="true">▾</span>
                    </div>
                    <p class="city-selection-meta">Currently viewing: <strong id="selectedBarangay">None</strong></p>
                    <p class="city-selection-note">Sensor streams will populate once telemetry sources are connected.</p>
                </div>
            </header>

            <section class="admin-section city-section" id="city-metrics">
                <div class="admin-section-header city-section-header">
                    <div>
                        <h2>Environment snapshot</h2>
                        <p class="admin-section-subtitle">Water, air, temperature, and humidity at a glance for the selected barangay.</p>
                    </div>
                    <span class="city-selected-pill">Selected barangay: <strong id="selectedBarangayChip">None</strong></span>
                </div>

                <div class="city-metric-grid">
                    <article class="city-metric-card city-metric-card--water">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Water level</p>
                            <p class="city-metric-value">
                                <span id="waterLevelVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">mm</span>
                            </p>
                        </div>
                        <p class="city-metric-description">River gauges reporting in millimeters across monitored stations.</p>
                        <p class="city-metric-footnote">Awaiting sensor calibration.</p>
                    </article>

                    <article class="city-metric-card city-metric-card--air">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Air quality</p>
                            <p class="city-metric-value">
                                <span id="airQualityVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">AQI</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Ambient particulate and gas readings sampled every 5 minutes.</p>
                        <p class="city-metric-footnote">Stations standing by for live feed.</p>
                    </article>
                    <article class="city-metric-card city-metric-card--temperature">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Temperature</p>
                            <p class="city-metric-value">
                                <span id="temperatureVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">°C</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Surface temperature sensors calibrated to downtown baseline.</p>
                        <p class="city-metric-footnote">Waiting for telemetry handshake.</p>
                    </article>

                    <article class="city-metric-card city-metric-card--humidity">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Humidity</p>
                            <p class="city-metric-value">
                                <span id="humidityVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">%</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Relative humidity percentages from barangay weather kits.</p>
                        <p class="city-metric-footnote">Connection pending deployment.</p>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="<?= $BASE ?>/assets/js/script.js?v=<?= time() ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('barangaySelect');
            const selectedBanner = document.getElementById('selectedBarangay');
            const selectedChip = document.getElementById('selectedBarangayChip');
            const valueIds = ['waterLevelVal', 'airQualityVal', 'temperatureVal', 'humidityVal'];

            if (!select) {
                return;
            }

            const setMetricValue = (id, value) => {
                const element = document.getElementById(id);
                if (!element) return;

                const isEmpty = value === null || value === undefined || value === '';
                element.textContent = isEmpty ? '—' : value;
                element.dataset.empty = isEmpty ? 'true' : 'false';
            };

            const updateSelection = (value) => {
                const label = value || 'None';
                if (selectedBanner) selectedBanner.textContent = label;
                if (selectedChip) selectedChip.textContent = label;
            };

            updateSelection(select.value);
            valueIds.forEach((id) => setMetricValue(id, null));

            select.addEventListener('change', (event) => {
                updateSelection(event.target.value);
                valueIds.forEach((id) => setMetricValue(id, null));
            });
        });
    </script>
</body>
</html>

