<?php
/**
 * Storage Helper for Wasmer Hosting
 * 
 * Handles file uploads to external storage since Wasmer ephemeral filesystem
 * doesn't persist uploads between deployments.
 * 
 * Current implementation: Base64 encoding stored in database
 * Alternative: AWS S3, Cloudinary, or other cloud storage
 */

/**
 * Upload an image and return a storage reference
 * 
 * @param array $file The $_FILES array element
 * @param string $context Context for storage (e.g., 'reports', 'announcements')
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function store_image($file, $context = 'general') {
    // Validate file upload
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'path' => null, 'error' => 'No file uploaded'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Upload error: ' . $file['error']];
    }
    
    // Validate file type
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png', 
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!isset($allowed[$mime])) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file type. Only JPG, PNG, WEBP, and GIF allowed.'];
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'error' => 'File too large. Maximum 5MB allowed.'];
    }
    
    $ext = $allowed[$mime];
    
    // Check if we should use cloud storage or local fallback
    $storageMethod = getenv('STORAGE_METHOD') ?: 'local';
    
    switch ($storageMethod) {
        case 's3':
        case 'aws':
            return store_to_s3($file, $context, $ext);
            
        case 'cloudinary':
            return store_to_cloudinary($file, $context, $ext);
            
        case 'base64':
            return store_as_base64($file, $mime);
            
        case 'local':
        default:
            return store_locally($file, $context, $ext);
    }
}

/**
 * Store file locally (works for development, not for Wasmer production)
 */
function store_locally($file, $context, $ext) {
    $uploadDir = __DIR__ . '/../uploads/' . $context;
    $publicBase = 'uploads/' . $context;
    
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $target = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
    
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to store file'];
    }
    
    return ['success' => true, 'path' => $publicBase . '/' . $basename, 'error' => null];
}

/**
 * Store as base64 data URI (for small images in database)
 * Note: This increases database size but works on ephemeral filesystems
 */
function store_as_base64($file, $mime) {
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to read file'];
    }
    
    $base64 = base64_encode($imageData);
    $dataUri = "data:$mime;base64,$base64";
    
    return ['success' => true, 'path' => $dataUri, 'error' => null];
}

/**
 * Store to AWS S3 or S3-compatible storage
 * Requires: AWS SDK or cURL
 */
function store_to_s3($file, $context, $ext) {
    // Configuration from environment variables
    $bucket = getenv('S3_BUCKET');
    $region = getenv('S3_REGION') ?: 'us-east-1';
    $accessKey = getenv('S3_ACCESS_KEY');
    $secretKey = getenv('S3_SECRET_KEY');
    $endpoint = getenv('S3_ENDPOINT'); // For S3-compatible services
    
    if (!$bucket || !$accessKey || !$secretKey) {
        // Fallback to local storage if S3 not configured
        error_log('S3 credentials not configured, falling back to local storage');
        return store_locally($file, $context, $ext);
    }
    
    // Generate unique filename
    $filename = $context . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    
    // TODO: Implement S3 upload using AWS SDK or cURL
    // For now, fallback to local
    error_log('S3 upload not yet implemented, falling back to local storage');
    return store_locally($file, $context, $ext);
}

/**
 * Store to Cloudinary
 * Requires: Cloudinary PHP SDK or cURL
 */
function store_to_cloudinary($file, $context, $ext) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');
    
    if (!$cloudName || !$apiKey || !$apiSecret) {
        error_log('Cloudinary credentials not configured, falling back to local storage');
        return store_locally($file, $context, $ext);
    }
    
    // TODO: Implement Cloudinary upload
    error_log('Cloudinary upload not yet implemented, falling back to local storage');
    return store_locally($file, $context, $ext);
}

/**
 * Delete a stored image
 * 
 * @param string $path The storage path/reference
 * @return bool Success status
 */
function delete_image($path) {
    if (!$path) return true;
    
    // If it's a data URI, nothing to delete
    if (strpos($path, 'data:') === 0) {
        return true;
    }
    
    // If it's a local file
    if (strpos($path, 'uploads/') === 0) {
        $filePath = __DIR__ . '/../' . $path;
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }
    
    // For cloud storage, implement deletion
    // TODO: S3/Cloudinary deletion
    
    return true;
}
?>
