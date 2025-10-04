<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Report - GO! MARIKINA</title>
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
            <header class="auth-header" aria-label="Go Marikina branding">
                <img src="./uploads/go_marikina_logo.png" alt="Go Marikina" class="auth-logo">
                <h1 class="auth-section-title">CREATE A REPORT</h1>
                <span class="auth-header-spacer" aria-hidden="true"></span>
            </header>
            
            <?php include __DIR__ . '/includes/login.php'; ?>

            <!-- Create Report Content (Hidden until login) -->
            <div id="createReportContent" style="display: none;">
                <div class="create-report-container">

                    <form class="create-report-form" id="createReportForm" enctype="multipart/form-data">
                    <!-- Photo Upload Section -->
                    <div class="photo-upload-section">
                        <div class="photo-upload-area" id="photoUploadArea">
                            <div class="upload-icon">
                                <svg viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </div>
                            <div class="upload-text">Upload Photo</div>
                            <div class="upload-hint">Click or drag to upload</div>
                            <img class="photo-preview" id="photoPreview" alt="Preview">
                        </div>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;">
                        
                        <div class="form-buttons">
                            <button type="button" class="btn-cancel" id="clearFormBtn">CANCEL</button>
                            <button type="submit" class="btn-upload">UPLOAD</button>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="details-section">
                        <h2 class="details-title">Details</h2>
                        
                        <div class="form-field">
                            <label class="form-label" for="category">CATEGORY</label>
                            <div class="form-select">
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="public_safety">Public Safety & Infrastructure</option>
                                    <option value="cleanliness">Cleanliness & Environment</option>
                                    <option value="public_facilities">Public Facilities</option>
                                    <option value="community">Community</option>
                                    <option value="other">Other Concerns</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="title">TITLE</label>
                            <input type="text" id="title" name="title" class="form-input" placeholder="Enter a title for your concern..." required>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="description">DESCRIPTION</label>
                            <textarea id="description" name="description" class="form-input form-textarea" placeholder="Describe your concern..." required></textarea>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="location">LOCATION</label>
                            <div class="location-field">
                                <input type="text" id="location" name="location" class="form-input" placeholder="Enter location or click to map" required>
                                <svg class="location-icon" viewBox="0 0 24 24">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Photo Cropping Modal -->
    <div id="cropModal" class="crop-modal" style="display: none;">
        <div class="crop-modal-content">
            <div class="crop-modal-header">
                <h3>Crop Photo</h3>
                <button type="button" class="crop-close" id="cropClose">&times;</button>
            </div>
            <div class="crop-container">
                <div class="crop-area">
                    <canvas id="cropCanvas"></canvas>
                    <div class="crop-overlay">
                        <div class="crop-box" id="cropBox"></div>
                    </div>
                </div>
                <div class="crop-controls">
                    <div class="crop-info">
                        <span>Drag to reposition the crop area</span>
                        <span class="aspect-ratio">8:4 Aspect Ratio (Fixed)</span>
                    </div>
                    <div class="crop-buttons">
                        <button type="button" class="btn-cancel" id="cropCancel">Cancel</button>
                        <button type="button" class="btn-crop" id="cropConfirm">Crop Photo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </div>

    <script src="assets/js/script.js" defer></script>
</body>
</html>
