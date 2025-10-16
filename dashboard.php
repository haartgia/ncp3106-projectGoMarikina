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
    <title>Dashboard - GO! MARIKINA</title>
    <?php $BASE = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/'); ?>
    <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #1a1a1a;
        }

        /* Main Content */
        .dashboard-main {
            max-width: 100%;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* IoT Dashboard Section */
        .iot-dashboard {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Barangay Selector - 50% shorter, 50% wider */
        .barangay-selector {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 2rem 6rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(30, 64, 175, 0.3);
            min-height: 150px;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .selector-content {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6rem;
        }
        .city-dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            letter-spacing: 0.02em;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
        }
        .selector-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
            flex-shrink: 0;
        }
        .selector-label {
            display: block;
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.02em;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
            align-self: flex-start;
        }
        .selector-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .select-wrapper {
            position: relative;
            width: 300px;
        }
        .barangay-select {
            appearance: none;
            -webkit-appearance: none;
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: #133c7a;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }
        .barangay-select:hover {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        .barangay-select:focus {
            outline: none;
            background: rgba(255, 255, 255, 1);
            border-color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
        }
        .barangay-select option,
        .barangay-select optgroup {
            background: #fff;
            color: #133c7a;
            padding: 0.75rem;
        }
        .select-arrow {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #133c7a;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .selected-info {
            padding: 0.5rem 1.5rem;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border-radius: 999px;
            color: #ffffff;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            white-space: nowrap;
        }
        .selected-info strong {
            font-weight: 700;
            color: #ffffff;
        }

        /* IoT Cards Grid - 2 per row, 200% wider */
        .iot-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
            margin-bottom: 2rem;
            max-width: 2400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Individual IoT Card - 200% wider, 50% shorter */
        .iot-card {
            background: #fff;
            border-radius: 20px;
            padding: 2rem 8rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(19,60,122,0.08);
            text-align: center;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .iot-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(19,60,122,0.15);
        }
        .iot-card-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .iot-card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #133c7a;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .iot-card-value {
            font-size: 3.5rem;
            font-weight: 900;
            color: #1a1a1a;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .iot-card-unit {
            font-size: 1.1rem;
            color: #667085;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .iot-card-status {
            margin-top: 1rem;
            padding: 0.6rem 1.5rem;
            background: #f3f4f6;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #667085;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .selector-content {
                gap: 3rem;
            }
            .barangay-selector {
                padding: 2rem 4rem;
            }
        }

        @media (max-width: 768px) {
            .selector-content {
                flex-direction: column;
                gap: 1.5rem;
            }
            .city-dashboard-title {
                font-size: 1.5rem;
                text-align: center;
            }
            .selector-right {
                width: 100%;
                align-items: center;
            }
            .selector-label { 
                font-size: 1.1rem;
                text-align: center;
                align-self: center;
            }
            .selector-controls {
                flex-direction: column;
                width: 100%;
            }
            .select-wrapper {
                width: 100%;
            }
            .barangay-selector { 
                padding: 1.5rem;
                min-height: auto;
            }
            .barangay-select {
                font-size: 0.95rem;
                padding: 0.75rem 2.5rem 0.75rem 1.25rem;
            }
            .select-arrow { font-size: 1rem; }
            .selected-info { 
                font-size: 0.85rem;
                text-align: center;
            }
            .iot-grid { 
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .iot-card {
                padding: 2rem;
                min-height: 200px;
            }
            .iot-card-value { font-size: 2.5rem; }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include __DIR__ . '/includes/navbar.php'; ?>

        <main class="dashboard-main">
            <section class="iot-dashboard">
                <div class="barangay-selector">
                    <div class="selector-content">
                        <h1 class="city-dashboard-title">City Dashboard</h1>
                        
                        <div class="selector-right">
                            <label for="barangaySelect" class="selector-label">Select a Barangay</label>
                            <div class="selector-controls">
                                <div class="select-wrapper">
                                    <select id="barangaySelect" class="barangay-select">
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
                                    <span class="select-arrow">▾</span>
                                </div>
                                <div class="selected-info">
                                    <span>Selected: </span>
                                    <strong id="selectedBarangay">None</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="iot-grid">
                    <article class="iot-card">
                        <div class="iot-card-icon">💧</div>
                        <h3 class="iot-card-title">Water Level</h3>
                        <div class="iot-card-value" id="waterLevelVal">—</div>
                        <div class="iot-card-unit">mm</div>
                        <div class="iot-card-status">No data yet</div>
                    </article>

                    <article class="iot-card">
                        <div class="iot-card-icon">🌬️</div>
                        <h3 class="iot-card-title">Air Quality</h3>
                        <div class="iot-card-value" id="airQualityVal">—</div>
                        <div class="iot-card-unit">AQI</div>
                        <div class="iot-card-status">No data yet</div>
                    </article>

                    <article class="iot-card">
                        <div class="iot-card-icon">🌡️</div>
                        <h3 class="iot-card-title">Temperature</h3>
                        <div class="iot-card-value" id="temperatureVal">—</div>
                        <div class="iot-card-unit">°C</div>
                        <div class="iot-card-status">No data yet</div>
                    </article>

                    <article class="iot-card">
                        <div class="iot-card-icon">💨</div>
                        <h3 class="iot-card-title">Humidity</h3>
                        <div class="iot-card-value" id="humidityVal">—</div>
                        <div class="iot-card-unit">%</div>
                        <div class="iot-card-status">No data yet</div>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="<?= $BASE ?>/assets/js/script.js?v=<?= time() ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('barangaySelect');
            const selectedDisplay = document.getElementById('selectedBarangay');
            
            if (select && selectedDisplay) {
                select.addEventListener('change', function() {
                    selectedDisplay.textContent = this.value || 'None';
                    
                    // Reset all values when barangay changes
                    ['waterLevelVal', 'airQualityVal', 'temperatureVal', 'humidityVal'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = '—';
                    });
                });
            }
        });
    </script>
</body>
</html>

