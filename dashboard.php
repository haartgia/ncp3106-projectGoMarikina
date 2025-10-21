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
                            <p class="city-metric-title">Water level <span id="waterStatusIcon" class="status-icon status-gray" title="Water level icon"><i class="bi bi-moisture" aria-hidden="true"></i></span></p>
                            <p class="city-metric-value">
                                <span id="waterLevelVal" data-city-metric-value data-empty="true">—</span>
                                <span class="city-metric-unit">LEVEL</span>
                            </p>
                        </div>
                        <p class="city-metric-description">
                            River gauges reporting by alert level (1–3): L1 Gutter‑deep · L2 Knee‑deep · L3 Waist‑deep.
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
                            <p class="city-metric-title">Air quality <span id="airStatusIcon" class="status-icon status-gray" title="Air quality icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h8M6 12h12M10 16h8"/></svg></span></p>
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
                            <p class="city-metric-title">Temperature <span id="tempStatusIcon" class="status-icon status-gray" title="Temperature icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a2 2 0 0 1 2 2v7.17a4 4 0 1 1-4 0V4a2 2 0 0 1 2-2z"/></svg></span></p>
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
                            <p class="city-metric-title">Humidity <span id="humidStatusIcon" class="status-icon status-gray" title="Humidity icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c3.5 5.5 7 8 7 12a7 7 0 1 1-14 0c0-4 3.5-6.5 7-12z"/></svg></span></p>
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

    <script src="<?= $BASE ?>/assets/js/script.js?v=<?= time() ?>" defer></script>
    <script>
    (() => {
      'use strict';

      const BASE = '<?= $BASE ?>';
      const qs = id => document.getElementById(id);

      const select = qs('barangaySelect');
      const selectedBanner = qs('selectedBarangay');
      const selectedChip = qs('selectedBarangayChip');
    const airBadge = qs('airQualityBadge');
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

      // Modal refs
      const modal = qs('graphModal');
      const modalClose = qs('graphModalClose');
      const modalCanvas = qs('graphModalCanvas');
      const modalTitle = qs('graphModalTitle');
      const modalList = qs('graphModalList');
      const modalMeta = qs('graphModalMeta');
    const granularitySelect = qs('graphGranularity');

    if (!select) return;

    let currentBrgy = '';
    const MAX_POINTS = (window.GoMKData?.MAX_POINTS) || 720;
    const SELECT_KEY = 'gomk.selectedBarangay';

      const history = { water:[], air:[], temp:[], humid:[], times:[] };

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
                            // point marker
                            ctx.fillStyle = color;
                    ctx.beginPath(); ctx.arc(x, y, 4, 0, Math.PI*2); ctx.fill();
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
          humid:{ title:'Humidity (%)',         values: history.humid, unit:'%',   color:'#10b981', domain:[0,100], ticks:5 }
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
      }
            function closeGraph(){
                if (modal.hasAttribute('hidden')) return;
                modal.setAttribute('hidden','');
                document.documentElement.classList.remove('modal-open');
                document.body.classList.remove('modal-open');
                if (modal._onResize) window.removeEventListener('resize', modal._onResize);
                modal._onResize = null;
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

      // Persistence
    const loadHistoryFor = b => (window.GoMKData?.loadHistoryFor(b)) || { water:[], air:[], temp:[], humid:[], times:[] };

      function adoptHistory(hist){
        history.water = (hist.water||[]).slice(-MAX_POINTS);
        history.air   = (hist.air||[]).slice(-MAX_POINTS);
        history.temp  = (hist.temp||[]).slice(-MAX_POINTS);
        history.humid = (hist.humid||[]).slice(-MAX_POINTS);
        history.times = (hist.times||[]).slice(-MAX_POINTS);

        const last = arr => { for (let i=arr.length-1;i>=0;i--) if (arr[i]!=null) return arr[i]; return null; };
        const ts = history.times.at(-1) || null;
        const wl = last(history.water), aq = last(history.air), t = last(history.temp), h = last(history.humid);
    if (wl!=null) { qs('waterLevelVal').textContent = wl; updateWaterDot(wl); }
    if (aq!=null) { qs('airQualityVal').textContent = aq; updateAQIBadge(aq); updateAirDot(aq); }
    if (t !=null) { qs('temperatureVal').textContent = Number(t).toFixed(1); updateTempDot(t); }
    if (h !=null) { qs('humidityVal').textContent = Number(h).toFixed(1); updateHumidDot(h); }
        setTime('waterTime', ts); setTime('airTime', ts); setTime('tempTime', ts); setTime('humidTime', ts);

        drawSpark(canvases.water, history.water, '#2563eb');
        drawSpark(canvases.air,   history.air,   '#0ea5e9');
        drawSpark(canvases.temp,  history.temp,  '#f59e0b');
        drawSpark(canvases.humid, history.humid, '#10b981');
      }

      function updateSelection(label){
        const text = label || 'None';
        if (selectedBanner) selectedBanner.textContent = text;
        if (selectedChip) selectedChip.textContent = text;
      }

            function bindBackgroundUpdates(){
                window.addEventListener('gomk:data', (ev) => {
                    const d = ev.detail; if (!d || d.barangay !== currentBrgy) return;
                    const { wl, aqi, t, h, ts } = d.latest || {};
                      if (wl!=null) { qs('waterLevelVal').textContent = wl; updateWaterDot(wl); }
                      if (aqi!=null) { qs('airQualityVal').textContent = isFinite(aqi) ? aqi : '—'; updateAQIBadge(aqi); updateAirDot(aqi); }
                      if (t!=null) { qs('temperatureVal').textContent = isFinite(t) ? t.toFixed(1) : '—'; updateTempDot(t); }
                      if (h!=null) { qs('humidityVal').textContent = isFinite(h) ? h.toFixed(1) : '—'; updateHumidDot(h); }
                    setTime('waterTime', ts); setTime('airTime', ts); setTime('tempTime', ts); setTime('humidTime', ts);

                    adoptHistory(d.history || loadHistoryFor(currentBrgy));
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
            });

            // Initialize selection from saved value if present
                    const saved = localStorage.getItem(SELECT_KEY);
            if (saved) { select.value = saved; }
            if (select.value){
                currentBrgy = select.value;
                updateSelection(currentBrgy);
                adoptHistory(loadHistoryFor(currentBrgy));
                window.GoMKData?.setBarangay(currentBrgy);
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
    })();
    </script>
</body>
</html>

