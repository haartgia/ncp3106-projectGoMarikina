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
                                    <option value="community">Community & Other Concerns</option>
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

    <script>
        // Photo Upload Functionality - Inline for create-report.php
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Create report page loaded');
            
            const photoUploadArea = document.getElementById('photoUploadArea');
            const photoInput = document.getElementById('photoInput');
            const photoPreview = document.getElementById('photoPreview');

            if (photoUploadArea && photoInput && photoPreview) {
                console.log('Photo upload elements found, initializing...');
                
                // Click to upload
                photoUploadArea.addEventListener('click', () => {
                    console.log('Photo upload area clicked');
                    photoInput.click();
                });

                // Drag and drop functionality
                photoUploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    photoUploadArea.classList.add('dragover');
                });

                photoUploadArea.addEventListener('dragleave', () => {
                    photoUploadArea.classList.remove('dragover');
                });

                photoUploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    photoUploadArea.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileSelect(files[0]);
                    }
                });

                // File input change
                photoInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        handleFileSelect(e.target.files[0]);
                    }
                });

                function handleFileSelect(file) {
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            showCropModal(e.target.result);
                        };
                        reader.readAsDataURL(file);
                    }
                }

                // Cropping functionality
                let cropImage = null;
                let cropBox = null;
                let isDragging = false;
                let dragStart = { x: 0, y: 0 };

                function showCropModal(imageSrc) {
                    const modal = document.getElementById('cropModal');
                    const canvas = document.getElementById('cropCanvas');
                    
                    if (!modal || !canvas) return;
                    
                    modal.style.display = 'flex';
                    
                    const img = new Image();
                    img.onload = () => {
                        const ctx = canvas.getContext('2d');
                        
                        const maxWidth = 600;
                        const maxHeight = 400;
                        const scale = Math.min(maxWidth / img.width, maxHeight / img.height);
                        
                        canvas.style.width = (img.width * scale) + 'px';
                        canvas.style.height = (img.height * scale) + 'px';
                        canvas.width = img.width;
                        canvas.height = img.height;
                        
                        ctx.drawImage(img, 0, 0);
                        
                        initializeCropBox(img.width * scale, img.height * scale);
                        cropImage = img;
                    };
                    img.src = imageSrc;
                }

                function initializeCropBox(imageWidth, imageHeight) {
                    const cropBoxElement = document.getElementById('cropBox');
                    
                    if (!cropBoxElement) return;
                    
                    cropBoxElement.innerHTML = '';
                    
                    const aspectRatio = 8/4;
                    
                    // Calculate maximum possible size while maintaining 8:4 ratio
                    let cropWidth, cropHeight;
                    
                    // Try to fit the full width first
                    cropWidth = imageWidth * 0.95; // Use 95% of image width
                    cropHeight = cropWidth / aspectRatio;
                    
                    // If height exceeds image bounds, scale down based on height
                    if (cropHeight > imageHeight * 0.95) {
                        cropHeight = imageHeight * 0.95; // Use 95% of image height
                        cropWidth = cropHeight * aspectRatio;
                    }
                    
                    // Center the crop box
                    const left = (imageWidth - cropWidth) / 2;
                    const top = (imageHeight - cropHeight) / 2;
                    
                    cropBoxElement.style.left = left + 'px';
                    cropBoxElement.style.top = top + 'px';
                    cropBoxElement.style.width = cropWidth + 'px';
                    cropBoxElement.style.height = cropHeight + 'px';
                    cropBoxElement.style.display = 'block';
                    cropBoxElement.style.position = 'absolute';
                    
                    cropBoxElement.addEventListener('mousedown', startDrag);
                    
                    const canvas = document.getElementById('cropCanvas');
                    if (canvas) {
                        canvas.style.pointerEvents = 'none';
                    }
                    
                    cropBox = {
                        element: cropBoxElement,
                        x: left,
                        y: top,
                        width: cropWidth,
                        height: cropHeight
                    };
                }

                function startDrag(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    isDragging = true;
                    
                    const rect = document.querySelector('.crop-area').getBoundingClientRect();
                    dragStart.x = e.clientX - rect.left - cropBox.x;
                    dragStart.y = e.clientY - rect.top - cropBox.y;
                    
                    document.addEventListener('mousemove', dragCropBox);
                    document.addEventListener('mouseup', stopDrag);
                }

                function dragCropBox(e) {
                    if (!isDragging || !cropBox) return;
                    
                    e.preventDefault();
                    const rect = document.querySelector('.crop-area').getBoundingClientRect();
                    const newX = e.clientX - rect.left - dragStart.x;
                    const newY = e.clientY - rect.top - dragStart.y;
                    
                    const maxX = rect.width - cropBox.width;
                    const maxY = rect.height - cropBox.height;
                    
                    cropBox.x = Math.max(0, Math.min(newX, maxX));
                    cropBox.y = Math.max(0, Math.min(newY, maxY));
                    
                    updateCropBoxElement();
                }

                function stopDrag() {
                    isDragging = false;
                    document.removeEventListener('mousemove', dragCropBox);
                    document.removeEventListener('mouseup', stopDrag);
                }

                function updateCropBoxElement() {
                    if (!cropBox) return;
                    
                    cropBox.element.style.left = cropBox.x + 'px';
                    cropBox.element.style.top = cropBox.y + 'px';
                    cropBox.element.style.width = cropBox.width + 'px';
                    cropBox.element.style.height = cropBox.height + 'px';
                }

                // Modal event listeners
                const cropClose = document.getElementById('cropClose');
                const cropCancel = document.getElementById('cropCancel');
                const cropConfirm = document.getElementById('cropConfirm');

                if (cropClose) cropClose.addEventListener('click', hideCropModal);
                if (cropCancel) cropCancel.addEventListener('click', hideCropModal);
                if (cropConfirm) cropConfirm.addEventListener('click', confirmCrop);

                function hideCropModal() {
                    const modal = document.getElementById('cropModal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    cropImage = null;
                    cropBox = null;
                }

                function confirmCrop() {
                    if (!cropImage || !cropBox) return;
                    
                    const canvas = document.getElementById('cropCanvas');
                    const scaleX = cropImage.width / canvas.offsetWidth;
                    const scaleY = cropImage.height / canvas.offsetHeight;
                    
                    const actualX = cropBox.x * scaleX;
                    const actualY = cropBox.y * scaleY;
                    const actualWidth = cropBox.width * scaleX;
                    const actualHeight = cropBox.height * scaleY;
                    
                    const croppedCanvas = document.createElement('canvas');
                    const ctx = croppedCanvas.getContext('2d');
                    
                    croppedCanvas.width = actualWidth;
                    croppedCanvas.height = actualHeight;
                    
                    ctx.drawImage(
                        cropImage,
                        actualX, actualY, actualWidth, actualHeight,
                        0, 0, actualWidth, actualHeight
                    );
                    
                    croppedCanvas.toBlob((blob) => {
                        const url = URL.createObjectURL(blob);
                        photoPreview.src = url;
                        photoUploadArea.classList.add('has-photo');
                        
                        const file = new File([blob], 'cropped-image.jpg', { type: 'image/jpeg' });
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        photoInput.files = dataTransfer.files;
                        
                        hideCropModal();
                    }, 'image/jpeg', 0.9);
                }

                // Form submission
                const createReportForm = document.getElementById('createReportForm');
                if (createReportForm) {
                    createReportForm.addEventListener('submit', (e) => {
                        e.preventDefault();
                        
                        const formData = new FormData(e.target);
                        const category = formData.get('category');
                        const title = formData.get('title');
                        const description = formData.get('description');
                        const location = formData.get('location');
                        
                        if (!category) {
                            alert('Please select a category');
                            return;
                        }
                        
                        if (!title.trim()) {
                            alert('Please enter a title for your concern');
                            return;
                        }
                        
                        if (!description.trim()) {
                            alert('Please enter a description');
                            return;
                        }
                        
                        if (!location.trim()) {
                            alert('Please enter a location');
                            return;
                        }
                        
                        alert('Report submitted successfully!\\n\\nTitle: ' + title + '\\nCategory: ' + category + '\\nDescription: ' + description + '\\nLocation: ' + location);
                        clearForm();
                    });
                }

                function clearForm() {
                    const form = document.getElementById('createReportForm');
                    if (form) {
                        form.reset();
                    }
                    photoUploadArea.classList.remove('has-photo');
                    photoPreview.src = '';
                }

                // Clear form button handler
                const clearFormBtn = document.getElementById('clearFormBtn');
                if (clearFormBtn) {
                    clearFormBtn.addEventListener('click', clearForm);
                }

                // Location field click handler
                const locationField = document.getElementById('location');
                if (locationField) {
                    locationField.addEventListener('click', () => {
                        console.log('Location field clicked - map integration coming soon');
                    });
                }

                console.log('Photo upload functionality fully initialized');
            } else {
                console.log('Photo upload elements not found');
            }
        });
    </script>
    <script src="assets/js/script.js" defer></script>
</body>
</html>
