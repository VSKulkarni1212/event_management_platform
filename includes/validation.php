<?php
/**
 * Input Validation and Sanitization Functions
 */

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @param array &$errors Array to store error messages
 * @return bool True if valid
 */
function validatePassword($password, &$errors = []) {
    $valid = true;
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
        $valid = false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
        $valid = false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
        $valid = false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
        $valid = false;
    }
    
    return $valid;
}

/**
 * Sanitize string input
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize file upload
 * @param array $file $_FILES array element
 * @param array &$errors Array to store error messages
 * @return bool True if valid
 */
function validateImageUpload($file, &$errors = []) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return true; // No file uploaded, which might be optional
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error. Please try again.";
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $maxMB = MAX_FILE_SIZE / 1048576;
        $errors[] = "File size must not exceed {$maxMB}MB.";
        return false;
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        $errors[] = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
        return false;
    }
    
    // Check file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = "Invalid file extension. Only .jpg, .jpeg, .png, and .gif are allowed.";
        return false;
    }
    
    // Verify it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = "File is not a valid image.";
        return false;
    }
    
    return true;
}

/**
 * Validate date format and ensure it's not in the past
 * @param string $date Date string
 * @param bool $allowPast Allow past dates
 * @return bool True if valid
 */
function validateDate($date, $allowPast = false) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        return false;
    }
    
    if (!$allowPast) {
        $now = new DateTime();
        $now->setTime(0, 0, 0);
        return $dateObj >= $now;
    }
    
    return true;
}

/**
 * Validate integer and ensure it's within range
 * @param mixed $value Value to validate
 * @param int $min Minimum value
 * @param int $max Maximum value (optional)
 * @return bool True if valid
 */
function validateInteger($value, $min = 1, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $intValue = (int)$value;
    
    if ($intValue < $min) {
        return false;
    }
    
    if ($max !== null && $intValue > $max) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize filename for safe storage
 * @param string $filename Original filename
 * @return string Safe filename
 */
function sanitizeFilename($filename) {
    // Get extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Create safe filename with timestamp
    $safeName = time() . '_' . uniqid() . '.' . $extension;
    
    return $safeName;
}

/**
 * Display error messages in HTML
 * @param array $errors Array of error messages
 * @return string HTML output
 */
function displayErrors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="error-messages" style="color: #d32f2f; background: #ffebee; padding: 15px; border-radius: 4px; margin-bottom: 15px;">';
    $html .= '<ul style="margin: 0; padding-left: 20px;">';
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    $html .= '</ul></div>';
    
    return $html;
}

/**
 * Display success message in HTML
 * @param string $message Success message
 * @return string HTML output
 */
function displaySuccess($message) {
    return '<div class="success-message" style="color: #2e7d32; background: #e8f5e9; padding: 15px; border-radius: 4px; margin-bottom: 15px;">' 
           . htmlspecialchars($message) . '</div>';
}
?>
