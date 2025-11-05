<?php
require_once __DIR__ . '/includes/bootstrap.php';

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
    <?php 
        $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $cssVersion = @filemtime(__DIR__ . '/assets/css/style.css') ?: time();
        $jsVersion = @filemtime(__DIR__ . '/assets/js/script.js') ?: time();
    ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* UI fixes and consistency */
        .city-metric-card {
            overflow: hidden;
            display: flex;               /* keep footer pinned at bottom */
            flex-direction: column;
        }
        .city-metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }
        .city-metric-value {
            display: flex;
            align-items: baseline;
            gap: .25rem;
            white-space: nowrap;
            min-width: 0;
        }
        [data-city-metric-value] { font-variant-numeric: tabular-nums; }
        .city-metric-unit { white-space: nowrap; }
    /* Centered status icon between title and measurement */
    .city-metric-status{ flex:1 1 auto; display:flex; align-items:center; justify-content:center; min-width:34px; }
    .status-icon{ display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:999px; background:rgba(15,23,42,.05); color:#64748b; }
    .status-icon svg{ width:16px; height:16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .status-icon i.bi{ font-size:16px; line-height:1; display:inline-block; }
    .status-green{ color:#16a34a; background:rgba(22,163,74,.12); }
    .status-blue{ color:#0ea5e9; background:rgba(14,165,233,.12); }
    .status-orange{ color:#f59e0b; background:rgba(245,158,11,.12); }
    .status-red{ color:#ef4444; background:rgba(239,68,68,.12); }
    .status-gray{ color:#94a3b8; background:rgba(148,163,184,.18); }

        .city-metric-title {
            text-transform: uppercase;
            letter-spacing: .12em;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 520px) { .city-metric-title { white-space: normal; } }

        /* Footer row: sparkline + time, always at bottom */
        .city-metric-footer {
            margin-top: auto;            /* push to bottom */
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .city-metric-sparkline {
            flex: 1 1 auto;
            height: 56px;
            border-radius: 8px;
            overflow: hidden;
            background: transparent;     /* transparent bg (not white) */
        }
        .city-metric-sparkline canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        .city-metric-time {
            font-size: 12px;
            color: #6b7280;              /* neutral-500 */
            white-space: nowrap;
            user-select: none;
        }

        .city-metric-sparkline { cursor: pointer; } /* indicate it's clickable */

    /* Fallback: ensure metric grid uses CSS Grid even if external CSS is delayed */
    .city-metric-grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:24px; }

    /* Modal layout fixes */
        .graph-modal-dialog{
            width:clamp(900px, 95vw, 1100px);
            height:clamp(560px, 82vh, 760px);
            display:grid;
            grid-template-rows:auto 1fr auto;
            overflow:hidden;
            background:#ffffff;                 /* visible card */
            color:#0f172a;                      /* readable text */
            border-radius:20px;                 /* rounded */
            box-shadow:0 30px 80px -24px rgba(2,6,23,.45);
            border:1px solid rgba(15,23,42,.08);
        }
        .graph-modal-body{
            display:grid;
            grid-template-columns:minmax(0,1fr) 300px; /* canvas | list */
            align-items:stretch;
            overflow:hidden; /* prevent bleed/cut */
        }
        .graph-modal-canvas-wrap{
            padding:16px;
            background:transparent;
            min-width:0;     /* allow shrink in grid */
            overflow:hidden; /* prevent inner scrollbars; canvas resizes */
            min-height:280px; /* ensure visible drawing area */
            padding-bottom:24px; /* extra space for x-axis labels */
        }
        .graph-modal-list{
            min-width:0;
            border-left:1px solid rgba(15,23,42,.08);
            padding:12px 12px 12px 0;
            overflow:auto;   /* scroll timestamps, not clip */
            background:rgba(2,6,23,.02);
        }
        .graph-modal-list h4{ margin:8px 12px; color:#0f172a; }
        #graphModalCanvas{width:100%;height:100%;display:block;background:transparent;}

        /* Modal overlay essentials */
        .graph-modal[hidden]{ display:none !important; }
        .graph-modal{
            position:fixed; inset:0; z-index:1000;
            display:flex; align-items:center; justify-content:center;
            background:rgba(15,23,42,.5); padding:24px;
        }
        /* Lock page scroll when modal is open */
        html.modal-open, body.modal-open { overflow: hidden !important; }
        .graph-modal-header{
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px; border-bottom:1px solid rgba(15,23,42,.08);
        }
    .graph-modal-title{ font-size:18px; font-weight:600; color:#0f172a; }
        .graph-modal-title-row{ display:flex; align-items:center; gap:12px; }
        .graph-modal-select{
            font: inherit; font-size:13px; color:#0f172a;
            padding:6px 10px; border-radius:10px;
            border:1px solid rgba(15,23,42,.15); background:#fff;
        }
        .graph-modal-close{
            appearance:none; border:0; background:transparent; cursor:pointer;
            font-size:20px; line-height:1; color:#475569; padding:4px 8px;
        }
        .graph-modal-footer{
            display:flex; justify-content:space-between; align-items:center;
            padding:10px 16px; border-top:1px solid rgba(15,23,42,.08);
            font-size:12px; color:#64748b;
        }
        .graph-modal-items{ list-style:none; margin:0; padding:0 8px 8px 12px; }
        .graph-modal-item{ display:flex; justify-content:space-between; gap:12px; padding:6px 6px; border-bottom:1px dashed rgba(15,23,42,.08); font-size:13px; }
        .graph-modal-time{ color:#475569; }
        .graph-modal-value{ font-variant-numeric:tabular-nums; font-weight:600; }

        /* Smooth section switching for barangay <-> river */
        .city-switcher{ position:relative; }
        .city-switcher > section{ transition: opacity .35s ease, transform .35s ease; }
        .city-switcher > section.is-hidden{ opacity:0; transform: translateY(8px); pointer-events:none; position:absolute; inset:0; }
        .city-switcher > section.is-active{ opacity:1; transform: translateY(0); }

    /* Ensure hidden hero cards don't display */
    .city-hero-card[hidden]{ display:none !important; }

    /* Graph tooltip */
    .graph-tooltip{ position:fixed; z-index:1001; pointer-events:none; display:none; padding:10px 12px; border-radius:10px; background:#fff; color:#0f172a; border:1px solid rgba(15,23,42,.08); box-shadow:0 10px 25px rgba(2,6,23,.15); font-size:12px; line-height:1.3; }
    .graph-tooltip .ttl{ font-weight:700; }
    .graph-tooltip .val{ font-variant-numeric:tabular-nums; }

        /* Inline info tooltip for environment cards */
        .info-tip-wrap{ position: relative; display:inline-flex; align-items:center; }
        .info-tip{
            appearance:none; border:1px solid rgba(15,23,42,.18); background:rgba(2,6,23,.04);
            color:#475569; width:18px; height:18px; border-radius:999px; display:inline-flex;
            align-items:center; justify-content:center; font-size:12px; line-height:1; cursor:help;
        }
        .info-tip:focus{ outline:2px solid #0ea5e9; outline-offset:2px; }
        .info-tip-bubble{
            position:absolute; top:130%; left:0; z-index:100; display:none; min-width:220px; max-width:280px;
            padding:10px 12px; border-radius:10px; background:#ffffff; color:#0f172a;
            border:1px solid rgba(15,23,42,.08); box-shadow:0 10px 25px rgba(2,6,23,.15); font-size:12px;
        }
        .info-tip-bubble:after{
            content:""; position:absolute; top:-6px; left:10px; width:10px; height:10px; background:#fff;
            border-left:1px solid rgba(15,23,42,.08); border-top:1px solid rgba(15,23,42,.08); transform:rotate(45deg);
        }
    /* Show inline bubble only when explicitly toggled (we use a fixed overlay by default) */
    .info-tip-wrap[data-open="true"] .info-tip-bubble{ display:block; }
        .info-tip-wrap .status-icon{ cursor: help; }
        @media (max-width: 520px){ .info-tip-bubble{ left:auto; right:0; } .info-tip-bubble:after{ left:auto; right:10px; } }

        /* Remove page scrolling on desktop (hide scrollbar) */
        @media (min-width: 1024px){
            html, body { overflow: hidden; }
            body { -ms-overflow-style: none; scrollbar-width: none; }
            body::-webkit-scrollbar{ width:0; height:0; }
        }
    </style>
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
                    <p class="city-hero-subtitle">Monitor ambient conditions for City Wide and Barangay, keep barangay responders aligned, and surface telemetry trends from a single view.</p>
                    <div class="city-hero-actions">
                        <a id="btnRiver" class="city-hero-button" href="#river-telemetry" data-mode="river" aria-label="View Marikina River telemetry" title="View Marikina River telemetry">City Wide</a>
                        <a id="btnBarangay" class="city-hero-button city-hero-button--primary" href="#city-metrics" data-mode="barangay" aria-label="Go to barangay section" title="Go to barangay section" aria-current="page">Barangay</a>
                    </div>
                </div>
                <div class="city-hero-card" id="brgyHeroCard">
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
                <!-- River station selection card (shown when Marikina mode is active) -->
                <div class="city-hero-card" id="riverHeroCard" hidden>
                    <p class="city-card-label">Station selection</p>
                    <label for="stationSelect" class="city-select-label">Choose a Marikina River station</label>
                    <div class="city-select-wrapper">
                        <select id="stationSelect" class="city-select" aria-label="Choose a river station">
                            <option value="" disabled selected>Choose a station...</option>
                            <option>Montalban</option>
                            <option>San Mateo-1</option>
                            <option>Rodriguez</option>
                            <option>Nangka</option>
                            <option>Sto Nino</option>
                            <option>Tumana Bridge</option>
                            <option>Rosario Bridge</option>
                        </select>
                        <span class="city-select-arrow" aria-hidden="true">▾</span>
                    </div>
                    <p class="city-selection-meta">Currently viewing: <strong id="selectedStation">Unavailable</strong></p>
                    <p class="city-selection-note">Levels update from PAGASA water level table.</p>
                </div>
            </header>

            <div class="city-switcher" id="citySwitcher">
            <section class="admin-section city-section is-active" id="city-metrics">
                <div class="admin-section-header city-section-header">
                    <div>
                        <h2>Environment snapshot</h2>
                        <p class="admin-section-subtitle">Water, air, temperature, and humidity at a glance for the selected barangay.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="city-selected-pill">Selected barangay: <strong id="selectedBarangayChip">None</strong></span>
                        <span id="deviceStatusPill" class="city-device-pill device-unknown">
                            <span class="device-dot" aria-hidden="true"></span>
                            <span id="deviceStatusText">Device: Unknown</span>
                        </span>
                    </div>
                </div>

                <div class="city-metric-grid">
                    <article class="city-metric-card city-metric-card--water">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Water level
                                <span class="info-tip-wrap">
                                    <span id="waterStatusIcon" class="status-icon status-gray" aria-describedby="tip-water" tabindex="0"><i class="bi bi-moisture" aria-hidden="true"></i></span>
                                    <span class="info-tip-bubble" id="tip-water" role="tooltip">River gauges by alert level: L1 Gutter‑deep, L2 Knee‑deep, L3 Waist‑deep. Values reflect the latest barangay reading.</span>
                                </span>
                            </p>
                            <p class="city-metric-value">
                                <span id="waterLevelVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">LEVEL</span>
                            </p>
                        </div>
                        <p class="city-metric-description">
                            River gauges reporting by alert level — L1 Gutter‑deep, L2 Knee‑deep, L3 Waist‑deep.
                        </p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline">
                                <canvas id="waterChart"></canvas>
                            </div>
                            <time id="waterTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--air">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Air quality
                                <span class="info-tip-wrap">
                                    <span id="airStatusIcon" class="status-icon status-gray" aria-describedby="tip-air" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h8M6 12h12M10 16h8"/></svg></span>
                                    <span class="info-tip-bubble" id="tip-air" role="tooltip">Air Quality Index (AQI) — lower is better. 0–50 Excellent, 51–100 Good, 101–150 Moderate, 151+ Poor.</span>
                                </span>
                            </p>
                            <p class="city-metric-value">
                                <span id="airQualityVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">AQI</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Ambient particulate and gas readings sampled every 5 minutes.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline">
                                <canvas id="airChart"></canvas>
                            </div>
                            <time id="airTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--temperature">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Temperature
                                <span class="info-tip-wrap">
                                    <span id="tempStatusIcon" class="status-icon status-gray" aria-describedby="tip-temp" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a2 2 0 0 1 2 2v7.17a4 4 0 1 1-4 0V4a2 2 0 0 1 2-2z"/></svg></span>
                                    <span class="info-tip-bubble" id="tip-temp" role="tooltip">Ambient air temperature measured near the selected barangay station, shown in °C.</span>
                                </span>
                            </p>
                            <p class="city-metric-value">
                                <span id="temperatureVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">°C</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Surface temperature sensors calibrated to downtown baseline.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline">
                                <canvas id="tempChart"></canvas>
                            </div>
                            <time id="tempTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--humidity">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Humidity
                                <span class="info-tip-wrap">
                                    <span id="humidStatusIcon" class="status-icon status-gray" aria-describedby="tip-humid" tabindex="0"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c3.5 5.5 7 8 7 12a7 7 0 1 1-14 0c0-4 3.5-6.5 7-12z"/></svg></span>
                                    <span class="info-tip-bubble" id="tip-humid" role="tooltip">Relative humidity indicates how much moisture is in the air, as a percentage.</span>
                                </span>
                            </p>
                            <p class="city-metric-value">
                                <span id="humidityVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">%</span>
                            </p>
                        </div>
                        <p class="city-metric-description">Relative humidity percentages from barangay weather kits.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline">
                                <canvas id="humidChart"></canvas>
                            </div>
                            <time id="humidTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>
                </div>
            </section>

            <!-- River telemetry: hidden by default, toggled via buttons -->
            <section class="admin-section city-section is-hidden" id="river-telemetry" aria-hidden="true">
                <div class="admin-section-header city-section-header">
                    <div>
                        <h2>Marikina River levels</h2>
                        <p class="admin-section-subtitle">Current, Alert, Alarm, and Critical levels per station.</p>
                    </div>
                    <span class="city-selected-pill">Selected station: <strong id="selectedStationChip">Unavailable</strong></span>
                </div>

                <div class="city-metric-grid">
                    <article class="city-metric-card city-metric-card--water">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Current level <span class="status-icon status-gray"><i class="bi bi-moisture" aria-hidden="true"></i></span></p>
                            <p class="city-metric-value"><span id="riverCurrent" data-city-metric-value data-empty="true">—</span><span class="city-metric-unit">EL.m</span></p>
                        </div>
                        <p class="city-metric-description">Latest gauge reading at the selected station.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline"><canvas id="riverCurrentChart"></canvas></div>
                            <time id="riverCurrentTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>

                    <article class="city-metric-card city-metric-card--temperature">
                        <div class="city-metric-header">
                            <p class="city-metric-title">Air temperature <span class="status-icon status-gray"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a2 2 0 0 1 2 2v7.17a4 4 0 1 1-4 0V4a2 2 0 0 1 2-2z"/></svg></span></p>
                            <p class="city-metric-value"><span id="riverTempVal" data-city-metric-value data-empty="true">—</span><span class="city-metric-unit">°C</span></p>
                        </div>
                        <p class="city-metric-description">Nearest ambient temperature reading.</p>
                        <div class="city-metric-footer">
                            <div class="city-metric-sparkline"><canvas id="riverTempChart"></canvas></div>
                            <time id="riverTempTime" class="city-metric-time" datetime="">—</time>
                        </div>
                    </article>
                </div>
            </section>
            </div>
        </main>
    </div>

    <!-- Modal: Full graph with timestamps -->
    <div id="graphModal" class="graph-modal" hidden>
        <div class="graph-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="graphModalTitle">
                        <div class="graph-modal-header">
                                <div class="graph-modal-title-row">
                                        <div class="graph-modal-title" id="graphModalTitle">Metric</div>
                                        <label style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:6px">
                                            View:
                                            <select id="graphGranularity" class="graph-modal-select" aria-label="Granularity">
                                                <option value="minute">Per minute</option>
                                                <option value="30min">Per 30 mins</option>
                                                <option value="hour" selected>Per hour</option>
                                            </select>
                                        </label>
                                </div>
                <button id="graphModalClose" class="graph-modal-close" aria-label="Close">✕</button>
            </div>
            <div class="graph-modal-body">
                <div class="graph-modal-canvas-wrap">
                    <canvas id="graphModalCanvas"></canvas>
                </div>
                <div class="graph-modal-list">
                    <h4 id="graphModalListTitle">Timestamps</h4>
                    <ul id="graphModalList" class="graph-modal-items"></ul>
                </div>
            </div>
            <div class="graph-modal-footer">
                <span id="graphModalMeta">Showing most recent points</span>
                <span>Times shown in Asia/Manila</span>
            </div>
        </div>
    </div>
    <div id="graphTooltip" class="graph-tooltip" role="status" aria-live="polite"></div>

    <script src="<?= $BASE ?>/assets/js/script.js?v=<?= $jsVersion ?>" defer></script>
    <script>
    (() => {
      'use strict';
            // Defensive: reset any leftover UI state from previous pages/restores
            try { document.body.classList.remove('nav-open', 'modal-open'); } catch(e) {}

      const BASE = '<?= $BASE ?>';
      const qs = id => document.getElementById(id);

    const select = qs('barangaySelect');
    const selectedBanner = qs('selectedBarangay');
    const selectedChip = qs('selectedBarangayChip');
    const selectedStationBanner = qs('selectedStation');
    const selectedStationChip = qs('selectedStationChip');
    const airBadge = qs('airQualityBadge');
    // Toggle buttons and sections
    const btnRiver = document.getElementById('btnRiver');
    const btnBrgy = document.getElementById('btnBarangay');
    const sectionRiver = document.getElementById('river-telemetry');
    const sectionBrgy = document.getElementById('city-metrics');
                    const dots = {
                        air: qs('airStatusIcon'),
                        water: qs('waterStatusIcon'),
                        temp: qs('tempStatusIcon'),
                        humid: qs('humidStatusIcon')
                    };

      const canvases = {
        water: qs('waterChart'),
        air:   qs('airChart'),
        temp:  qs('tempChart'),
        humid: qs('humidChart')
      };

            // Device status pill helpers
            const devicePill = qs('deviceStatusPill');
            const deviceText = qs('deviceStatusText');
            function setDeviceStatus(status){
                if (!devicePill || !deviceText) return;
                devicePill.classList.remove('device-online','device-offline','device-unknown');
                if (status === 'online'){
                    devicePill.classList.add('device-online');
                    deviceText.textContent = 'Device: Online';
                } else if (status === 'offline'){
                    devicePill.classList.add('device-offline');
                    deviceText.textContent = 'Device: Offline';
                } else {
                    devicePill.classList.add('device-unknown');
                    deviceText.textContent = 'Device: Unknown';
                }
            }

            // River time-series (shallow buffer so modal can render)
            const riverHistory = { current: [], temp: [], times: [] };

      // Modal refs
      const modal = qs('graphModal');
      const modalClose = qs('graphModalClose');
      const modalCanvas = qs('graphModalCanvas');
      const modalTitle = qs('graphModalTitle');
      const modalList = qs('graphModalList');
      const modalMeta = qs('graphModalMeta');
    const tooltipEl = qs('graphTooltip');
    const granularitySelect = qs('graphGranularity');

    if (!select) return;

    function setActiveMode(mode){
        const isRiver = mode === 'river';
        // Switch sections with a small rAF to trigger CSS transition reliably
        [sectionRiver, sectionBrgy].forEach(sec => {
            sec.classList.remove('is-active');
            sec.classList.add('is-hidden');
            sec.setAttribute('aria-hidden','true');
        });
        const show = isRiver ? sectionRiver : sectionBrgy;
        show.classList.remove('is-hidden');
        requestAnimationFrame(() => show.classList.add('is-active'));
        show.removeAttribute('aria-hidden');

        // Flip hero button styles and hero card visibility
        if (isRiver){
            btnRiver.classList.add('city-hero-button--primary');
            btnRiver.setAttribute('aria-current','page');
            btnBrgy.classList.remove('city-hero-button--primary');
            btnBrgy.removeAttribute('aria-current');
            document.getElementById('brgyHeroCard')?.setAttribute('hidden','');
            document.getElementById('riverHeroCard')?.removeAttribute('hidden');
        } else {
            btnBrgy.classList.add('city-hero-button--primary');
            btnBrgy.setAttribute('aria-current','page');
            btnRiver.classList.remove('city-hero-button--primary');
            btnRiver.removeAttribute('aria-current');
            document.getElementById('brgyHeroCard')?.removeAttribute('hidden');
            document.getElementById('riverHeroCard')?.setAttribute('hidden','');
        }
    }

    // City-wide aggregation removed: City Wide uses river cards (2 cards). Barangay mode keeps 4 cards.

    function smoothScrollIntoView(target){
        // Intentionally disabled: no automatic page scrolling on toggle
        return;
    }

    // Wire buttons to toggle without page navigation
    if (btnRiver) btnRiver.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveMode('river');
        smoothScrollIntoView(sectionRiver);
    ensureRiverData();
    });
    if (btnBrgy) btnBrgy.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveMode('barangay');
        smoothScrollIntoView(sectionBrgy);
    });

    let currentBrgy = '';
    const MAX_POINTS = (window.GoMKData?.MAX_POINTS) || 720;
    const SELECT_KEY = 'gomk.selectedBarangay';
    // Bump cache key to avoid stale data from removed endpoints
    const RIVER_CACHE_KEY = 'gomk.river.cache.v2';
    const RIVER_SELECTED_KEY = 'gomk.river.station';

      const history = { water:[], air:[], temp:[], humid:[], times:[] };
    let riverData = null; // { stations: [...] }

      // Helpers
      function computeWaterAlertLevel(v){
        if (!Number.isFinite(v) || v <= 0) return { level: 0 };
        if (v === 100) return { level: 1 };   // binary float switch
        if (v <= 33)  return { level: 1 };
        if (v <= 66)  return { level: 2 };
        return { level: 3 };
      }
                    function computeAQICategory(aqi){
                const v = Number(aqi);
                if (!isFinite(v)) return { label:'—', cls:'', show:false };
                // You can adjust thresholds; these are simple ranges
                if (v <= 50)   return { label:'Excellent', cls:'aqi-excellent', show:true };
                if (v <= 100)  return { label:'Good',      cls:'aqi-good',      show:true };
                if (v <= 150)  return { label:'Moderate',  cls:'aqi-moderate',  show:true };
                return              { label:'Poor',      cls:'aqi-poor',      show:true };
            }
            function updateAQIBadge(aqi){
                if (!airBadge) return;
                const cat = computeAQICategory(aqi);
                if (!cat.show){ airBadge.hidden = true; return; }
                airBadge.textContent = cat.label;
                airBadge.hidden = false;
                airBadge.classList.remove('aqi-excellent','aqi-good','aqi-moderate','aqi-poor');
                if (cat.cls) airBadge.classList.add(cat.cls);
            }
                    function setDot(el, colorCls){ if (!el) return; el.classList.remove('status-green','status-blue','status-orange','status-red','status-gray'); el.classList.add(colorCls || 'status-gray'); }
                    function updateAirDot(aqi){ const v = Number(aqi); if (!isFinite(v)) { setDot(dots.air,'status-gray'); return; } if (v <= 50) setDot(dots.air,'status-green'); else if (v <= 100) setDot(dots.air,'status-blue'); else if (v <= 150) setDot(dots.air,'status-orange'); else setDot(dots.air,'status-red'); }
                    function updateWaterDot(level){ const v = Number(level); if (!isFinite(v)) { setDot(dots.water,'status-gray'); return; } if (v <= 1) setDot(dots.water,'status-green'); else if (v === 2) setDot(dots.water,'status-orange'); else setDot(dots.water,'status-red'); }
                    function updateTempDot(temp){ const v = Number(temp); if (!isFinite(v)) { setDot(dots.temp,'status-gray'); return; } if (v < 20) setDot(dots.temp,'status-blue'); else if (v <= 32) setDot(dots.temp,'status-green'); else if (v <= 38) setDot(dots.temp,'status-orange'); else setDot(dots.temp,'status-red'); }
                    function updateHumidDot(h){ const v = Number(h); if (!isFinite(v)) { setDot(dots.humid,'status-gray'); return; } if (v < 35) setDot(dots.humid,'status-orange'); else if (v <= 70) setDot(dots.humid,'status-green'); else setDot(dots.humid,'status-blue'); }
      function formatTime(ts){
        if (!ts) return '—';
        const d = new Date(ts.replace(' ','T') + '+08:00');
        return d.toLocaleTimeString('en-PH', { timeZone:'Asia/Manila', hour:'2-digit', minute:'2-digit', hour12:true });
      }
      function fullTime(ts){
        if (!ts) return '—';
        const d = new Date(ts.replace(' ','T') + '+08:00');
        return d.toLocaleString('en-PH', { timeZone:'Asia/Manila', year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true });
      }
      function setTime(id, ts){
        const el = qs(id); if (!el) return;
        el.textContent = ts ? formatTime(ts) : '—';
        if (ts) el.setAttribute('datetime', ts.replace(' ','T') + '+08:00'); else el.removeAttribute('datetime');
      }
      function addPoint(arr, v){ arr.push(Number.isFinite(v) ? v : null); if (arr.length > MAX_POINTS) arr.shift(); }
      function addTime(ts){ history.times.push(ts || new Date().toISOString()); if (history.times.length > MAX_POINTS) history.times.shift(); }

      function clearCanvas(cv){
        if (!cv) return;
        const dpr = window.devicePixelRatio || 1;
        const r = cv.getBoundingClientRect();
        cv.width = Math.max(1, r.width * dpr);
        cv.height = Math.max(1, r.height * dpr);
        const ctx = cv.getContext('2d'); ctx.clearRect(0,0,cv.width,cv.height);
      }
      function drawSpark(cv, values, color){
        if (!cv) return;
        const dpr = window.devicePixelRatio || 1;
        const r = cv.getBoundingClientRect();
        const w = Math.max(1, r.width * dpr), h = Math.max(1, r.height * dpr);
        cv.width = w; cv.height = h;
        const ctx = cv.getContext('2d'); ctx.clearRect(0,0,w,h);
        const pts = values.filter(v=>v!==null);
        if (pts.length < 2) return;
        const min = Math.min(...pts), max = Math.max(...pts);
        const pad = Math.max(1, Math.round(h * .12)), n = values.length, stepX = w / Math.max(1, n-1);
        ctx.lineWidth = Math.max(1, Math.round(1.5 * dpr)); ctx.lineJoin='round'; ctx.lineCap='round';
        const grad = ctx.createLinearGradient(0,0,0,h); grad.addColorStop(0,color); grad.addColorStop(1,color+'99');
        ctx.beginPath();
        values.forEach((v,i)=>{ if(v===null) return; const x=i*stepX; const y=h-pad-((v-min)/(max-min||1))*(h-pad*2); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); });
        ctx.strokeStyle = grad; ctx.stroke();
      }

            // ========== RIVER HELPERS ============
                            function setStationSelection(name){
                        const text = name || 'None';
                        if (selectedStationBanner) selectedStationBanner.textContent = text;
                        if (selectedStationChip) selectedStationChip.textContent = text;
                    }

                    function populateRiver(stationName){
                if (!riverData || !Array.isArray(riverData.stations)) return;
                const found = riverData.stations.find(s => s.name === stationName) || riverData.stations[0];
                if (!found) return;
                        setStationSelection(found.name);
                            const setVal = (id, v) => { const el = qs(id); if(!el) return; el.textContent = (v==null? '—' : String(v)); el.dataset.empty = v==null ? 'true':'false'; };
                                const setTimeSafe = (id, ts) => { const el = qs(id); if(!el) return; el.textContent = ts ? formatTime(ts) : '—'; if (ts) el.setAttribute('datetime', ts); else el.removeAttribute('datetime'); };
                                setVal('riverCurrent', found.current);
                                setTimeSafe('riverCurrentTime', found.time);

                                // Temperature: reuse latest ambient temperature we already maintain
                                const lastTemp = (() => { for (let i=history.temp.length-1;i>=0;i--){ const v = history.temp[i]; if (v!=null) return Number(v); } return null; })();
                                setVal('riverTempVal', lastTemp != null ? Number(lastTemp).toFixed(1) : null);
                                setTimeSafe('riverTempTime', history.times.at(-1) || null);

                                // Initialize river history with station's historical data if available
                                if (found.history && Array.isArray(found.history) && found.history.length > 0) {
                                    riverHistory.current = [...found.history];
                                } else if (riverHistory.current.length === 0) {
                                    // If no history yet, create a simple trend
                                    riverHistory.current = Array(20).fill(null).map(() => 
                                        found.current + (Math.random() - 0.5) * 2.0
                                    );
                                }

                                // Draw small sparklines
                                drawSpark(qs('riverCurrentChart'), riverHistory.current, '#16a34a');
                                drawSpark(qs('riverTempChart'), history.temp.length > 0 ? history.temp : [26, 26.5, 26.2, 26.8, 26.1], '#f59e0b');

                            // Push current reading into riverHistory
                            const MAX_RIVER = 240;
                            if (found.current != null){ riverHistory.current.push(Number(found.current)); if (riverHistory.current.length>MAX_RIVER) riverHistory.current.shift(); }
                            riverHistory.temp.push(lastTemp != null ? Number(lastTemp) : null); if (riverHistory.temp.length>MAX_RIVER) riverHistory.temp.shift();
                            const t = found.time || new Date().toISOString();
                            riverHistory.times.push(t); if (riverHistory.times.length>MAX_RIVER) riverHistory.times.shift();
            }

            async function fetchRiver(){
                // Return dummy data for all Marikina River stations with historical trends
                return new Promise((resolve) => {
                    setTimeout(() => {
                        const stations = [
                            { name: 'Montalban', current: 12.5, alert: 14.0, alarm: 16.0, critical: 18.0 },
                            { name: 'San Mateo-1', current: 13.2, alert: 15.0, alarm: 17.0, critical: 19.0 },
                            { name: 'Rodriguez', current: 11.8, alert: 13.5, alarm: 15.5, critical: 17.5 },
                            { name: 'Nangka', current: 10.9, alert: 12.5, alarm: 14.5, critical: 16.5 },
                            { name: 'Sto Nino', current: 9.7, alert: 11.5, alarm: 13.5, critical: 15.5 },
                            { name: 'Tumana Bridge', current: 8.3, alert: 10.0, alarm: 12.0, critical: 14.0 },
                            { name: 'Rosario Bridge', current: 7.5, alert: 9.0, alarm: 11.0, critical: 13.0 }
                        ];
                        const now = new Date();
                        const timestamp = now.toISOString().split('T')[0] + ' ' + 
                                        now.toTimeString().split(' ')[0].substring(0,5) + ':00';
                        // Add random variations to current levels (±0.5m) and generate historical data
                        stations.forEach(s => {
                            const baseLevel = s.current;
                            s.current = Math.max(0, baseLevel + (Math.random() - 0.5) * 1.0);
                            s.current = Number(s.current.toFixed(2));
                            s.time = timestamp;
                            
                            // Generate dummy historical data (last 24 hours, hourly)
                            s.history = [];
                            for (let i = 24; i >= 0; i--) {
                                const variation = (Math.random() - 0.5) * 2.0; // ±1m variation
                                s.history.push(Math.max(0, baseLevel + variation));
                            }
                        });
                        resolve({ stations });
                    }, 100);
                });
            }

            function ensureRiverData(){
                try {
                    const cached = localStorage.getItem(RIVER_CACHE_KEY);
                    if (cached){
                        const obj = JSON.parse(cached);
                        riverData = obj; // trust immediate
                    }
                } catch {}
                // If cache has no usable values, force a refresh
                const cacheEmpty = !riverData || !Array.isArray(riverData.stations) || riverData.stations.every(s => s == null || s.current == null);
                if (!riverData || cacheEmpty){
                                fetchRiver().then(data => {
                        riverData = data;
                        try { localStorage.setItem(RIVER_CACHE_KEY, JSON.stringify(data)); } catch {}
                                                const sel = document.getElementById('stationSelect');
                                    // Populate station options dynamically if list present
                                    if (sel && data?.stations?.length){
                                        sel.innerHTML = '<option value="" disabled>Choose a station...</option>' + data.stations.map(s=>`<option>${s.name}</option>`).join('');
                                    }
                        const savedName = localStorage.getItem(RIVER_SELECTED_KEY) || (sel && sel.options[1]?.value);
                        if (sel && savedName) sel.value = savedName;
                        populateRiver(savedName);
                                                // Bind change once
                                                if (sel && !sel._bound){
                                                    sel.addEventListener('change', () => {
                                                        const name = sel.value;
                                                        localStorage.setItem(RIVER_SELECTED_KEY, name);
                                                        populateRiver(name);
                                                    });
                                                    sel._bound = true;
                                                }
                                }).catch(err => {
                                    console.error('river fetch error', err);
                                    setStationSelection('Unavailable');
                                    const setVal = (id, v) => { const el = qs(id); if(!el) return; el.textContent = v; };
                                    setVal('riverCurrent', '—');
                                    setVal('riverTempVal', '—');
                                });
                } else {
                    const sel = document.getElementById('stationSelect');
                    const savedName = localStorage.getItem(RIVER_SELECTED_KEY) || (sel && sel.options[1]?.value);
                    if (sel && savedName) sel.value = savedName;
                                            populateRiver(savedName);
                                            if (sel && !sel._bound){
                                                sel.addEventListener('change', () => {
                                                    const name = sel.value;
                                                    localStorage.setItem(RIVER_SELECTED_KEY, name);
                                                    populateRiver(name);
                                                });
                                                sel._bound = true;
                                            }
                }
            }
            function drawFullChart(cv, values, times, color, unit, opts={}){
        if (!cv) return;
        const wrap = cv.parentElement, dpr = window.devicePixelRatio || 1;
    const rect = wrap.getBoundingClientRect();
    // Fix canvas size based on container but cap to avoid growth across redraws
    const wCSS = Math.max(360, Math.floor(rect.width));
    const hCSS = Math.max(320, Math.floor(rect.height));
        cv.style.width = wCSS+'px'; cv.style.height = hCSS+'px';
        cv.width = Math.floor(wCSS * dpr); cv.height = Math.floor(hCSS * dpr);
        const ctx = cv.getContext('2d'); ctx.setTransform(dpr,0,0,dpr,0,0); ctx.clearRect(0,0,wCSS,hCSS);
                const present = values.filter(v=>v!==null && isFinite(v));
        const pad = {top:16, right:16, bottom:80, left:56};
        const innerW = wCSS - pad.left - pad.right, innerH = hCSS - pad.top - pad.bottom;

                let minY, maxY;
                if (present.length >= 1) {
                    minY = (opts.domain && opts.domain[0] != null) ? opts.domain[0] : Math.min(...present);
                    maxY = (opts.domain && opts.domain[1] != null) ? opts.domain[1] : Math.max(...present);
                    if (!opts.domain) { const span = Math.max(1e-6, maxY - minY); minY -= span*.1; maxY += span*.1; }
                } else { // no data yet: choose a friendly default range
                    minY = (opts.domain && opts.domain[0] != null) ? opts.domain[0] : 0;
                    maxY = (opts.domain && opts.domain[1] != null) ? opts.domain[1] : 1;
                }
                const ySpan = Math.max(1e-6, maxY - minY);

        // axes
        ctx.strokeStyle='rgba(15,23,42,.15)'; ctx.lineWidth=1;
        ctx.beginPath(); ctx.moveTo(pad.left, pad.top); ctx.lineTo(pad.left, hCSS - pad.bottom); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(pad.left, hCSS - pad.bottom); ctx.lineTo(wCSS - pad.right, hCSS - pad.bottom); ctx.stroke();

        // Y ticks
        ctx.fillStyle='#475569'; ctx.font='12px system-ui,-apple-system,Segoe UI,Roboto,Arial';
        const ticks = opts.ticks ?? 4;
        for (let i=0;i<=ticks;i++){
          const r = i/ticks, y = hCSS - pad.bottom - r*innerH;
          ctx.globalAlpha=.25; ctx.beginPath(); ctx.moveTo(pad.left,y); ctx.lineTo(wCSS - pad.right,y); ctx.stroke(); ctx.globalAlpha=1;
          const val = minY + r*ySpan, lbl = opts.yLabel ? opts.yLabel(val) : val.toFixed(0);
          ctx.fillText(lbl + (unit && unit!=='lvl' ? ` ${unit}` : ''), 6, y+4);
        }

                // X labels
                const n = values.length, stepX = n > 1 ? innerW / (n-1) : 0;
    ctx.textAlign='center';
                        if (n >= 2) {
                                        [0,Math.floor(n/2),n-1].forEach(i => { const x = pad.left + i*stepX; const ts = times[i] || ''; ctx.fillText(formatTime(ts), x, hCSS - 24); });
                } else if (n === 1) {
                                        const x = pad.left + innerW/2; const ts = times[0] || '';
                                        ctx.fillText(formatTime(ts), x, hCSS - 24);
                }
        ctx.textAlign='left';

                // data line or point
                if (present.length >= 2) {
                    ctx.lineWidth=2; ctx.lineJoin='round'; ctx.lineCap='round'; ctx.strokeStyle=color;
                    ctx.beginPath();
                    for(let i=0;i<n;i++){ const v=values[i]; if(v===null) continue; const x = pad.left + (n>1 ? i*stepX : innerW/2); const y=hCSS - pad.bottom - ((v-minY)/ySpan)*innerH; if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); }
                    ctx.stroke();
                        } else if (present.length === 1) {
                            // draw a horizontal line and a point so the chart is never blank
                    let idx = values.findIndex(v => v!==null && isFinite(v));
                    if (idx < 0) idx = 0;
                    const v = values[idx] ?? minY;
                    const x = pad.left + innerW/2;
                    const y = hCSS - pad.bottom - ((v-minY)/ySpan)*innerH;
                            // horizontal line across the chart at the value
                            ctx.strokeStyle = color;
                            ctx.lineWidth = 2;
                            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(pad.left + innerW, y); ctx.stroke();
                            // point marker: bigger with soft halo for visibility
                            const toRGBA = (hex,a) => { const m=/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex||''); if(!m) return `rgba(22,163,74,${a})`; const r=parseInt(m[1],16), g=parseInt(m[2],16), b=parseInt(m[3],16); return `rgba(${r},${g},${b},${a})`; };
                            const halo = toRGBA(color, 0.22);
                            ctx.save();
                            ctx.beginPath(); ctx.fillStyle = halo; ctx.arc(x, y, 11, 0, Math.PI*2); ctx.fill();
                            ctx.beginPath(); ctx.fillStyle = color; ctx.arc(x, y, 6, 0, Math.PI*2); ctx.fill();
                            ctx.lineWidth = 2; ctx.strokeStyle = '#ffffff'; ctx.beginPath(); ctx.arc(x, y, 6, 0, Math.PI*2); ctx.stroke();
                            ctx.restore();
                } else {
                    // no data: show friendly placeholder text
                    ctx.fillStyle = 'rgba(71,85,105,.8)';
                    ctx.textAlign='center';
                    ctx.fillText('No data yet', pad.left + innerW/2, pad.top + innerH/2);
                    ctx.textAlign='left';
                }
      }

      // Modal open/close
                    function aggregateBy(values, times, step){
                const buckets = new Map();
                        for (let i=0;i<values.length;i++){
                    const v = values[i]; const ts = times[i]; if (!ts) continue;
                            const d = new Date((ts.includes('T')?ts:ts.replace(' ','T')) + '+08:00');
                            // bucket key by step: 'minute' -> YYYY-MM-DD HH:MM, '30min' -> HH:[00 or 30], 'hour' -> HH
                            let key;
                            if (step === 'minute') {
                                key = d.getFullYear()+ '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
                            } else if (step === '30min') {
                                const m = d.getMinutes(); const b = m < 30 ? '00' : '30';
                                key = d.getFullYear()+ '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + b;
                            } else { // hour
                                key = d.getFullYear()+ '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0');
                            }
                    const entry = buckets.get(key) || { sum:0, count:0, firstTs: ts };
                    if (v!=null && isFinite(v)) { entry.sum += Number(v); entry.count++; }
                    if (!entry.firstTs) entry.firstTs = ts;
                    buckets.set(key, entry);
                }
                const sorted = Array.from(buckets.entries()).sort((a,b)=> a[0]<b[0]? -1 : 1);
                const outVals = []; const outTimes = [];
                for (const [,e] of sorted){ outVals.push(e.count ? e.sum/e.count : null); outTimes.push(e.firstTs); }
                return { values: outVals, times: outTimes };
            }

                    function openGraph(kind){
                        // clean any previous resize handler to avoid stacking
                        if (modal._onResize) { window.removeEventListener('resize', modal._onResize); modal._onResize = null; }
                const map = {
          water:{ title:'Water level (Alert)', values: history.water, unit:'lvl', color:'#2563eb', domain:[0,3], ticks:3, yLabel:v=>'L'+Math.round(v) },
          air:{   title:'Air quality (AQI)',    values: history.air,   unit:'AQI', color:'#0ea5e9', domain:[0,200], ticks:4 },
          temp:{  title:'Temperature (°C)',     values: history.temp,  unit:'°C',  color:'#f59e0b', domain:[10,45], ticks:5 },
                    humid:{ title:'Humidity (%)',         values: history.humid, unit:'%',   color:'#10b981', domain:[0,100], ticks:5 },
                    riverCurrent:{ title:'Marikina River Current (EL.m)', values: riverHistory.current, unit:'EL.m', color:'#16a34a', ticks:4 }
        };
        const cfg = map[kind]; if (!cfg) return;

                    const step = (sessionStorage.getItem('gomk.graph.step') || 'hour');
                        if (granularitySelect) granularitySelect.value = step;
                    modalTitle.textContent = cfg.title + (step==='hour' ? ' — hourly avg' : step==='30min' ? ' — 30‑min avg' : ' — per‑minute avg');
                    modal.dataset.kind = kind;
        modalList.innerHTML = '';
                        const agg = aggregateBy(cfg.values, history.times, step);
                        const vals = agg.values; const tms = agg.times;
                for (let i = tms.length - 1; i >= 0; i--) {
          const li = document.createElement('li'); li.className='graph-modal-item';
                    const t = document.createElement('span'); t.className='graph-modal-time'; t.textContent = fullTime(tms[i]);
          const v = document.createElement('span'); v.className='graph-modal-value';
                    const val = vals[i];
          v.textContent = (val == null) ? '—' : `${Number(val).toFixed(cfg.unit==='°C'?1:0)} ${cfg.unit==='lvl'?'':cfg.unit}`;
          li.append(t,v); modalList.appendChild(li);
        }
                        modalMeta.textContent = `Points: ${vals.filter(x=>x!==null).length} (${step==='hour'?'hourly avg': step==='30min'?'30‑min avg':'per‑minute avg'})`;

        modal.removeAttribute('hidden');
    document.documentElement.classList.add('modal-open');
    document.body.classList.add('modal-open');
                                const draw = () => drawFullChart(modalCanvas, vals, tms, cfg.color, cfg.unit, { domain: cfg.domain, ticks: cfg.ticks, yLabel: cfg.yLabel });
        draw();
        const onResize = () => draw();
        modal._onResize = onResize;
        window.addEventListener('resize', onResize);

                // Tooltip: compute nearest index and show value/time
                const pad = {top:16, right:16, bottom:80, left:56};
                                function fmtShort(ts){ if(!ts) return '—'; const d = new Date((ts.includes('T')?ts:ts.replace(' ','T')) + '+08:00'); const mm = String(d.getMonth()+1).padStart(2,'0'); const dd = String(d.getDate()).padStart(2,'0'); const time = d.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',hour12:true,timeZone:'Asia/Manila'}); return `${mm}-${dd} ${time}`; }
                function valText(v){ if(v==null || !isFinite(v)) return '—'; if (cfg.unit==='°C') return `${Number(v).toFixed(1)}°C`; if(cfg.unit==='%') return `${Number(v).toFixed(1)}%`; if(cfg.unit==='AQI') return `${Math.round(v)} AQI`; if(cfg.unit==='lvl') return `L${Math.round(v)}`; return String(v); }
                                function showTooltip(x,y,idx){ if(!tooltipEl) return; const ts = tms[idx]; const v = vals[idx]; const title = fmtShort(ts); const label = (cfg.unit==='lvl' ? 'Level' : cfg.unit==='EL.m' ? 'Current [EL.m]' : 'Value'); const display = (cfg.unit==='EL.m' && isFinite(v)) ? `${Number(v).toFixed(2)} m` : valText(v); tooltipEl.innerHTML = `<div class="ttl">${title}</div><div class="val">${label}: ${display}</div>`; tooltipEl.style.display='block'; const off=12; const vpW = window.innerWidth, vpH = window.innerHeight; let tx = x+off, ty=y+off; const r = tooltipEl.getBoundingClientRect(); if(tx + r.width > vpW-8) tx = x - r.width - off; if(ty + r.height > vpH-8) ty = y - r.height - off; tooltipEl.style.left = `${Math.max(8, tx)}px`; tooltipEl.style.top = `${Math.max(8, ty)}px`; }
                function hideTooltip(){ if(tooltipEl) tooltipEl.style.display='none'; }
                                // Helper: add alpha to a hex color like #16a34a -> rgba(..., a)
                                function hexToRgba(hex, a){ const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex||''); if(!m){ return `rgba(22,163,74,${a})`; } const r=parseInt(m[1],16), g=parseInt(m[2],16), b=parseInt(m[3],16); return `rgba(${r},${g},${b},${a})`; }
                                function onMove(ev){ const rect = modalCanvas.getBoundingClientRect(); const n = vals.length; if(n<=0){ hideTooltip(); return; } const x = ev.clientX - rect.left; const innerW = rect.width - pad.left - pad.right; const stepX = n>1 ? innerW/(n-1) : 0; const xi = n>1 ? Math.round((x - pad.left)/stepX) : 0; const idx = Math.max(0, Math.min(n-1, xi));
                                    // redraw base chart
                                    draw();
                                    // draw a marker dot at nearest point
                                    const dpr = window.devicePixelRatio || 1; const ctx = modalCanvas.getContext('2d'); ctx.setTransform(dpr,0,0,dpr,0,0);
                                    const wCSS = rect.width; const hCSS = rect.height; const innerH = hCSS - pad.top - pad.bottom;
                                    const present = vals.filter(v=>v!==null && isFinite(v)); let minY, maxY; if (present.length>=1){ minY = Math.min(...present); maxY = Math.max(...present); } else { minY=0; maxY=1; }
                                    if (cfg.domain){ if (cfg.domain[0]!=null) minY = cfg.domain[0]; if (cfg.domain[1]!=null) maxY = cfg.domain[1]; if (!cfg.domain[1] && minY===maxY) maxY = minY+1; }
                                    const ySpan = Math.max(1e-6, maxY - minY);
                                    const xv = pad.left + (n>1 ? idx*stepX : innerW/2);
                                    const v = vals[idx]; const yv = (v==null||!isFinite(v)) ? (pad.top + innerH/2) : (hCSS - pad.bottom - ((v-minY)/ySpan)*innerH);
                                    // Bigger, high-contrast marker with soft halo for visibility
                                    const base = cfg.color || '#2563eb';
                                    const halo = hexToRgba(base, 0.22);
                                    ctx.save();
                                    // Outer soft halo
                                    ctx.beginPath(); ctx.fillStyle = halo; ctx.arc(xv, yv, 11, 0, Math.PI*2); ctx.fill();
                                    // Inner solid dot
                                    ctx.beginPath(); ctx.fillStyle = base; ctx.arc(xv, yv, 6, 0, Math.PI*2); ctx.fill();
                                    // Thin white ring to pop on dark/light backgrounds
                                    ctx.lineWidth = 2; ctx.strokeStyle = '#ffffff'; ctx.beginPath(); ctx.arc(xv, yv, 6, 0, Math.PI*2); ctx.stroke();
                                    ctx.restore();
                                    showTooltip(ev.clientX, ev.clientY, idx);
                                }
                modalCanvas.addEventListener('mousemove', onMove);
                modalCanvas.addEventListener('mouseleave', hideTooltip);
                modal._tooltipHandlers = { onMove, hideTooltip };
      }
            function closeGraph(){
                if (modal.hasAttribute('hidden')) return;
                modal.setAttribute('hidden','');
                document.documentElement.classList.remove('modal-open');
                document.body.classList.remove('modal-open');
                if (modal._onResize) window.removeEventListener('resize', modal._onResize);
                modal._onResize = null;
                                if (modal._tooltipHandlers){
                                    modalCanvas.removeEventListener('mousemove', modal._tooltipHandlers.onMove);
                                    modalCanvas.removeEventListener('mouseleave', modal._tooltipHandlers.hideTooltip);
                                    modal._tooltipHandlers = null;
                                }
                                if (tooltipEl) tooltipEl.style.display='none';
            }
      modalClose.addEventListener('click', closeGraph);
      modal.addEventListener('click', e => { if (e.target === modal) closeGraph(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeGraph(); });

            // React to granularity changes while modal is open
                    granularitySelect?.addEventListener('change', () => {
                        const step = granularitySelect.value || 'hour';
                        sessionStorage.setItem('gomk.graph.step', step);
                        const kind = modal.dataset.kind || 'air';
                        if (!modal.hasAttribute('hidden')) { openGraph(kind); }
                    });

      canvases.water?.parentElement.addEventListener('click', () => openGraph('water'));
      canvases.air?.parentElement.addEventListener('click',   () => openGraph('air'));
      canvases.temp?.parentElement.addEventListener('click',  () => openGraph('temp'));
      canvases.humid?.parentElement.addEventListener('click', () => openGraph('humid'));
    // Enable modal for Marikina current level chart
    document.getElementById('riverCurrentChart')?.parentElement.addEventListener('click', () => openGraph('riverCurrent'));
    // Enable modal for river temperature using the shared temperature history
    document.getElementById('riverTempChart')?.parentElement.addEventListener('click', () => openGraph('temp'));

      // Persistence
    const loadHistoryFor = b => (window.GoMKData?.loadHistoryFor(b)) || { water:[], air:[], temp:[], humid:[], times:[] };

      function adoptHistory(hist, isOffline){
        history.water = (hist.water||[]).slice(-MAX_POINTS);
        history.air   = (hist.air||[]).slice(-MAX_POINTS);
        history.temp  = (hist.temp||[]).slice(-MAX_POINTS);
        history.humid = (hist.humid||[]).slice(-MAX_POINTS);
        history.times = (hist.times||[]).slice(-MAX_POINTS);

        const last = arr => { for (let i=arr.length-1;i>=0;i--) if (arr[i]!=null) return arr[i]; return null; };
        const ts = history.times.at(-1) || null;
        const wl = last(history.water), aq = last(history.air), t = last(history.temp), h = last(history.humid);
        
    // If offline, show dash for all metrics
    if (isOffline) {
        qs('waterLevelVal').textContent = '—';
        qs('airQualityVal').textContent = '—';
        qs('temperatureVal').textContent = '—';
        qs('humidityVal').textContent = '—';
        setDeviceStatus('offline');
    } else {
        if (wl!=null) { qs('waterLevelVal').textContent = wl; updateWaterDot(wl); }
        if (aq!=null) { qs('airQualityVal').textContent = aq; updateAQIBadge(aq); updateAirDot(aq); }
        if (t !=null) { qs('temperatureVal').textContent = Number(t).toFixed(1); updateTempDot(t); }
        if (h !=null) { qs('humidityVal').textContent = Number(h).toFixed(1); updateHumidDot(h); }
    }
        setTime('waterTime', ts); setTime('airTime', ts); setTime('tempTime', ts); setTime('humidTime', ts);

                // For compact card sparklines, show 30‑minute averaged values to avoid cramped visuals
                const pickSpark = (vals) => {
                    try {
                        const agg = aggregateBy(vals, history.times, '30min');
                        return (agg.values && agg.values.length >= 2) ? agg.values : vals;
                    } catch { return vals; }
                };

                drawSpark(canvases.water, pickSpark(history.water), '#2563eb');
                drawSpark(canvases.air,   pickSpark(history.air),   '#0ea5e9');
                drawSpark(canvases.temp,  pickSpark(history.temp),  '#f59e0b');
                drawSpark(canvases.humid, pickSpark(history.humid), '#10b981');
      }

      function updateSelection(label){
        const text = label || 'None';
        if (selectedBanner) selectedBanner.textContent = text;
        if (selectedChip) selectedChip.textContent = text;
      }

            function bindBackgroundUpdates(){
                window.addEventListener('gomk:data', (ev) => {
                    const d = ev.detail; if (!d || d.barangay !== currentBrgy) return;
                    
                    // Check if device is offline
                    if (d.status === 'offline') {
                        const waterEl = qs('waterLevelVal');
                        const airEl = qs('airQualityVal');
                        const tempEl = qs('temperatureVal');
                        const humidEl = qs('humidityVal');
                        
                        waterEl.textContent = '—';
                        airEl.textContent = '—';
                        tempEl.textContent = '—';
                        humidEl.textContent = '—';
                        
                        // Add class for styling
                        waterEl.classList.add('unavailable-text');
                        airEl.classList.add('unavailable-text');
                        tempEl.classList.add('unavailable-text');
                        humidEl.classList.add('unavailable-text');
                        
                        setTime('waterTime', d.latest?.ts || null);
                        setTime('airTime', d.latest?.ts || null);
                        setTime('tempTime', d.latest?.ts || null);
                        setTime('humidTime', d.latest?.ts || null);
                        setDeviceStatus('offline');
                        return;
                    }
                    
                    // Remove unavailable class when online
                    qs('waterLevelVal').classList.remove('unavailable-text');
                    qs('airQualityVal').classList.remove('unavailable-text');
                    qs('temperatureVal').classList.remove('unavailable-text');
                    qs('humidityVal').classList.remove('unavailable-text');
                    
                    const { wl, aqi, t, h, ts } = d.latest || {};
                      if (wl!=null) { qs('waterLevelVal').textContent = wl; updateWaterDot(wl); }
                      if (aqi!=null) { qs('airQualityVal').textContent = isFinite(aqi) ? aqi : '—'; updateAQIBadge(aqi); updateAirDot(aqi); }
                      if (t!=null) { qs('temperatureVal').textContent = isFinite(t) ? t.toFixed(1) : '—'; updateTempDot(t); }
                      if (h!=null) { qs('humidityVal').textContent = isFinite(h) ? h.toFixed(1) : '—'; updateHumidDot(h); }
                    setTime('waterTime', ts); setTime('airTime', ts); setTime('tempTime', ts); setTime('humidTime', ts);
                    setDeviceStatus('online');

                    adoptHistory(d.history || loadHistoryFor(currentBrgy), false);
                });
            }

      // Init and selection
                    select.addEventListener('change', e => {
                currentBrgy = e.target.value || '';
                localStorage.setItem(SELECT_KEY, currentBrgy);
                updateSelection(currentBrgy);
                if (!currentBrgy) return;
                adoptHistory(loadHistoryFor(currentBrgy));
                window.GoMKData?.setBarangay(currentBrgy);
                setDeviceStatus('unknown');
            });

            // Initialize selection from saved value if present
                    const saved = localStorage.getItem(SELECT_KEY);
            if (saved) { select.value = saved; }
            if (select.value){
                currentBrgy = select.value;
                updateSelection(currentBrgy);
                adoptHistory(loadHistoryFor(currentBrgy));
                window.GoMKData?.setBarangay(currentBrgy);
                setDeviceStatus('unknown');
            }

            bindBackgroundUpdates();

                    // Provide the list of barangays to background poller so others continue receiving samples
                    try {
                        const ALL = [
                            'Sto. Niño','Malanday','Barangka','San Roque','Jesus Dela Peña','Tañong','Kalumpang','Industrial Valley Complex','Sta. Elena',
                            'Concepcion Uno','Tumana','Concepcion Dos','Marikina Heights','Nangka','Parang','Fortune'
                        ];
                        window.GoMKData?.setTrackedBarangays(ALL);
                    } catch {}

            // Lightweight tooltip toggles for info icons on metric cards
            try {
                const wraps = document.querySelectorAll('.info-tip-wrap');
                const closeAll = () => {
                    document.querySelectorAll('.info-tip-wrap[data-open="true"]').forEach(w => {
                        w.removeAttribute('data-open');
                        const b = w.querySelector('.info-tip');
                        if (b) b.removeAttribute('aria-expanded');
                    });
                };
                // Use the global fixed graphTooltip as the overlay for info tips
                const overlay = document.getElementById('graphTooltip');
                const OFF = 12;
                const place = (x, y) => {
                    if (!overlay) return;
                    const r = overlay.getBoundingClientRect();
                    let tx = x + OFF, ty = y + OFF;
                    const vw = window.innerWidth, vh = window.innerHeight;
                    if (tx + r.width > vw - 8) tx = x - r.width - OFF;
                    if (ty + r.height > vh - 8) ty = y - r.height - OFF;
                    overlay.style.left = Math.max(8, tx) + 'px';
                    overlay.style.top  = Math.max(8, ty) + 'px';
                };
                const showOverlay = (text, nearEl) => {
                    if (!overlay) return;
                    overlay.innerHTML = `<div class="ttl">${text}</div>`;
                    overlay.style.display = 'block';
                    const rect = nearEl.getBoundingClientRect();
                    place(rect.right, rect.top);
                };
                const hideOverlay = () => { if (overlay) overlay.style.display = 'none'; };

                wraps.forEach(wrap => {
                    const icon = wrap.querySelector('.status-icon');
                    const bubble = wrap.querySelector('.info-tip-bubble');
                    if (!icon || !bubble) return;
                    const text = bubble.textContent || '';

                    icon.addEventListener('mouseenter', () => showOverlay(text, icon));
                    icon.addEventListener('mouseleave', hideOverlay);
                    icon.addEventListener('focus', () => showOverlay(text, icon));
                    icon.addEventListener('blur', hideOverlay);
                    icon.addEventListener('click', (e) => {
                        // Support touch: toggle on tap
                        e.stopPropagation();
                        if (overlay && overlay.style.display === 'block') hideOverlay(); else showOverlay(text, icon);
                    });
                });
                document.addEventListener('click', hideOverlay);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideOverlay(); });
            } catch {}
    })();
    </script>
</body>
</html>

