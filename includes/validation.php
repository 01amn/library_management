<?php
/**
 * Validation utility functions for the Library Management System
 */

/**
 * Validate required fields
 * 
 * @param array $fields Associative array of field names and values
 * @return array Array of error messages for missing fields
 */
function validateRequired($fields) {
    $errors = [];
    
    foreach ($fields as $fieldName => $value) {
        if (empty(trim($value))) {
            $errors[] = ucfirst(str_replace('_', ' ', $fieldName)) . " is required.";
        }
    }
    
    return $errors;
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Basic phone validation - can be customized based on country format
    return preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $phone));
}

/**
 * Validate date format and check if it's a valid date
 * 
 * @param string $date Date string to validate (YYYY-MM-DD)
 * @return bool True if valid, false otherwise
 */
function validateDate($date) {
    if (empty($date)) return false;
    
    $format = 'Y-m-d';
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate if a date is in the future
 * 
 * @param string $date Date string to validate (YYYY-MM-DD)
 * @return bool True if date is in the future, false otherwise
 */
function validateFutureDate($date) {
    if (!validateDate($date)) return false;
    
    $inputDate = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Set time to beginning of day for fair comparison
    
    return $inputDate >= $today;
}

/**
 * Validate if a date is in the past
 * 
 * @param string $date Date string to validate (YYYY-MM-DD)
 * @return bool True if date is in the past, false otherwise
 */
function validatePastDate($date) {
    if (!validateDate($date)) return false;
    
    $inputDate = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Set time to beginning of day for fair comparison
    
    return $inputDate < $today;
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to validate
 * @return bool True if numeric, false otherwise
 */
function validateNumeric($value) {
    return is_numeric($value);
}

/**
 * Validate integer value
 * 
 * @param mixed $value Value to validate
 * @return bool True if integer, false otherwise
 */
function validateInteger($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validate positive integer value
 * 
 * @param mixed $value Value to validate
 * @return bool True if positive integer, false otherwise
 */
function validatePositiveInteger($value) {
    return validateInteger($value) && $value > 0;
}

/**
 * Validate year (4 digits between 1000 and current year + 5)
 * 
 * @param mixed $year Year to validate
 * @return bool True if valid year, false otherwise
 */
function validateYear($year) {
    $currentYear = (int)date('Y');
    return validateInteger($year) && $year >= 1000 && $year <= ($currentYear + 5);
}

/**
 * Validate ISBN (10 or 13 digits)
 * 
 * @param string $isbn ISBN to validate
 * @return bool True if valid ISBN, false otherwise
 */
function validateISBN($isbn) {
    // Remove hyphens and spaces
    $isbn = preg_replace('/[^0-9X]/', '', $isbn);
    
    // Check length
    $length = strlen($isbn);
    if ($length != 10 && $length != 13) {
        return false;
    }
    
    // Basic format check - more complex validation could be added
    return true;
}

/**
 * Sanitize input to prevent XSS
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
if (!function_exists('sanitizeInput')) {
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
}

/**
 * Display validation errors
 * 
 * @param array $errors Array of error messages
 * @return string HTML for displaying errors
 */
function displayErrors($errors) {
    if (empty($errors)) return '';
    
    $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $html .= '<ul class="mb-0">';
    
    foreach ($errors as $error) {
        $html .= '<li>' . $error . '</li>';
    }
    
    $html .= '</ul>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Display success message
 * 
 * @param string $message Success message
 * @return string HTML for displaying success message
 */
function displaySuccess($message) {
    if (empty($message)) return '';
    
    $html = '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    $html .= $message;
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $html .= '</div>';
    
    return $html;
}
?>