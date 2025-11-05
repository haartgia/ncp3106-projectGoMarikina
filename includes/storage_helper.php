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
    
    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = @finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
        }
    }
    if (!$mime && !empty($file['type'])) {
        $mime = $file['type'];
    }
    if (!$mime) { $mime = 'application/octet-stream'; }
    
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
 * Uses direct HTTP PUT request (no SDK required)
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
    
    // Read file content
    $fileContent = file_get_contents($file['tmp_name']);
    if ($fileContent === false) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to read file'];
    }
    
    // Determine content type
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif'
    ];
    $contentType = $contentTypes[$ext] ?? 'application/octet-stream';
    
    // Build S3 URL
    if ($endpoint) {
        // Custom endpoint (for S3-compatible services)
        $host = parse_url($endpoint, PHP_URL_HOST);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME) ?: 'https';
        $url = "$scheme://$host/$bucket/$filename";
    } else {
        // Standard AWS S3
        $host = "$bucket.s3.$region.amazonaws.com";
        $url = "https://$host/$filename";
    }
    
    // Create signature for AWS v4
    $timestamp = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $contentHash = hash('sha256', $fileContent);
    
    // Create canonical request
    $canonicalUri = '/' . $filename;
    $canonicalQueryString = '';
    $canonicalHeaders = "content-type:$contentType\n" .
                        "host:$host\n" .
                        "x-amz-content-sha256:$contentHash\n" .
                        "x-amz-date:$timestamp\n";
    $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';
    
    $canonicalRequest = "PUT\n$canonicalUri\n$canonicalQueryString\n$canonicalHeaders\n$signedHeaders\n$contentHash";
    
    // Create string to sign
    $credentialScope = "$dateStamp/$region/s3/aws4_request";
    $stringToSign = "AWS4-HMAC-SHA256\n$timestamp\n$credentialScope\n" . hash('sha256', $canonicalRequest);
    
    // Calculate signature
    $kDate = hash_hmac('sha256', $dateStamp, "AWS4$secretKey", true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    // Build authorization header
    $authorization = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
    
    // Upload to S3 using cURL
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContent,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: $authorization",
            "Content-Type: $contentType",
            "x-amz-content-sha256: $contentHash",
            "x-amz-date: $timestamp",
            "Content-Length: " . strlen($fileContent)
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        // Success! Return public URL
        $publicUrl = $url;
        return ['success' => true, 'path' => $publicUrl, 'error' => null];
    } else {
        error_log("S3 upload failed: HTTP $httpCode - $response - $curlError");
        return ['success' => false, 'path' => null, 'error' => "S3 upload failed: HTTP $httpCode"];
    }
}

/**
 * Store to Cloudinary
 * Uses Cloudinary Upload API with direct HTTP POST
 */
function store_to_cloudinary($file, $context, $ext) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');
    
    if (!$cloudName || !$apiKey || !$apiSecret) {
        error_log('Cloudinary credentials not configured, falling back to local storage');
        return store_locally($file, $context, $ext);
    }
    
    // Generate unique public ID
    $publicId = $context . '/' . bin2hex(random_bytes(8));
    
    // Create timestamp for signature
    $timestamp = time();
    
    // Create signature (SHA1 hash of parameters + secret)
    $paramsToSign = "folder=$context&public_id=$publicId&timestamp=$timestamp";
    $signature = sha1($paramsToSign . $apiSecret);
    
    // Prepare upload
    $url = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";
    
    // Prefer binary upload via cURL when available, otherwise fall back to data URI with stream context
    $mime = $file['type'] ?? 'application/octet-stream';
    $tmpPath = $file['tmp_name'];
    
    if (function_exists('curl_init') && class_exists('CURLFile')) {
        // Create POST data using multipart/form-data
        $postData = [
            'file' => new CURLFile($tmpPath, $mime, $file['name'] ?? 'upload'),
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $context,
            'public_id' => $publicId
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    } else {
        // Fallback: send data URI via application/x-www-form-urlencoded using native streams (no cURL)
        $bytes = @file_get_contents($tmpPath);
        if ($bytes === false) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to read uploaded file'];
        }
        $dataUri = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        
        $fields = http_build_query([
            'file' => $dataUri,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $context,
            'public_id' => $publicId
        ]);
        
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                            "Content-Length: " . strlen($fields) . "\r\n",
                'content' => $fields,
                'timeout' => 30
            ]
        ];
        $contextRes = stream_context_create($opts);
        $response = @file_get_contents($url, false, $contextRes);
        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $hdr, $m)) {
                    $httpCode = (int)$m[1];
                    break;
                }
            }
        }
        $curlError = '';
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        if (isset($result['secure_url'])) {
            return ['success' => true, 'path' => $result['secure_url'], 'error' => null];
        } else {
            error_log("Cloudinary upload succeeded but no URL returned: $response");
            return ['success' => false, 'path' => null, 'error' => 'Upload succeeded but no URL returned'];
        }
    } else {
        error_log("Cloudinary upload failed: HTTP $httpCode - $response - $curlError");
        $msg = $httpCode ? "Cloudinary upload failed: HTTP $httpCode" : 'Cloudinary upload failed';
        return ['success' => false, 'path' => null, 'error' => $msg];
    }
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
    
    // If it's an S3 URL, delete from S3
    if (strpos($path, 'https://') === 0 && strpos($path, '.s3.') !== false) {
        return delete_from_s3($path);
    }
    
    // If it's a Cloudinary URL, delete from Cloudinary
    if (strpos($path, 'cloudinary.com') !== false) {
        return delete_from_cloudinary($path);
    }
    
    return true;
}

/**
 * Delete a file from S3
 * 
 * @param string $url The S3 URL
 * @return bool Success status
 */
function delete_from_s3($url) {
    $bucket = getenv('S3_BUCKET');
    $region = getenv('S3_REGION') ?: 'us-east-1';
    $accessKey = getenv('S3_ACCESS_KEY');
    $secretKey = getenv('S3_SECRET_KEY');
    
    if (!$bucket || !$accessKey || !$secretKey) {
        error_log('S3 credentials not configured, cannot delete');
        return false;
    }
    
    // Extract key from URL
    $host = "$bucket.s3.$region.amazonaws.com";
    if (strpos($url, $host) === false) {
        error_log("URL doesn't match expected S3 bucket: $url");
        return false;
    }
    
    $key = str_replace("https://$host/", '', $url);
    
    // Create signature for DELETE request
    $timestamp = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $contentHash = hash('sha256', '');
    
    $canonicalUri = '/' . $key;
    $canonicalHeaders = "host:$host\n" .
                        "x-amz-content-sha256:$contentHash\n" .
                        "x-amz-date:$timestamp\n";
    $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
    
    $canonicalRequest = "DELETE\n$canonicalUri\n\n$canonicalHeaders\n$signedHeaders\n$contentHash";
    
    $credentialScope = "$dateStamp/$region/s3/aws4_request";
    $stringToSign = "AWS4-HMAC-SHA256\n$timestamp\n$credentialScope\n" . hash('sha256', $canonicalRequest);
    
    $kDate = hash_hmac('sha256', $dateStamp, "AWS4$secretKey", true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    $authorization = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: $authorization",
            "x-amz-content-sha256: $contentHash",
            "x-amz-date: $timestamp"
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 300) || $httpCode === 404;
}

/**
 * Delete a file from Cloudinary
 * 
 * @param string $url The Cloudinary URL
 * @return bool Success status
 */
function delete_from_cloudinary($url) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');
    
    if (!$cloudName || !$apiKey || !$apiSecret) {
        error_log('Cloudinary credentials not configured, cannot delete');
        return false;
    }
    
    // Extract public_id from URL
    // Example: https://res.cloudinary.com/demo/image/upload/v1234567890/reports/abc123.jpg
    // public_id would be: reports/abc123
    
    $pattern = '/\/upload\/(?:v\d+\/)?(.+)\.\w+$/';
    if (preg_match($pattern, $url, $matches)) {
        $publicId = $matches[1];
    } else {
        error_log("Could not extract public_id from Cloudinary URL: $url");
        return false;
    }
    
    // Create timestamp and signature
    $timestamp = time();
    $paramsToSign = "public_id=$publicId&timestamp=$timestamp";
    $signature = sha1($paramsToSign . $apiSecret);
    
    // Delete from Cloudinary
    $deleteUrl = "https://api.cloudinary.com/v1_1/$cloudName/image/destroy";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $deleteUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'public_id' => $publicId,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        return isset($result['result']) && ($result['result'] === 'ok' || $result['result'] === 'not found');
    }
    
    return false;
}
?>
