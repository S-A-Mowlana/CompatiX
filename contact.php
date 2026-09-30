<?php
/**
 * CompatiX - Contact Form Backend
 * Accepts JSON POST fields: name, email, subject, and message.
 * Returns JSON {success:boolean, message:string}.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

// Get JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON request body.']);
    exit;
}

// Validate input
$errors = [];

if (empty($data['name'] ?? '')) {
    $errors[] = 'Name is required';
}

if (empty($data['email'] ?? '') || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($data['subject'] ?? '')) {
    $errors[] = 'Subject is required';
}

if (empty($data['message'] ?? '')) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitize input
$name = sanitize_input($data['name']);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$subject = sanitize_input($data['subject']);
$message = sanitize_input($data['message']);

// Prepare contact data
$contact_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
];

// Save to log file
$log_file = CONTACT_LOG_FILE;
$log_dir = dirname($log_file);

// Create log directory if it doesn't exist
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Read existing entries
$entries = [];
if (file_exists($log_file)) {
    $existing_data = file_get_contents($log_file);
    if (!empty($existing_data)) {
        $entries = json_decode($existing_data, true) ?? [];
    }
}

// Add new entry
$entries[] = $contact_entry;

// Write back to file
if (file_put_contents($log_file, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    // Optional: Send email (requires mail server to be configured)
    // send_contact_email($name, $email, $subject, $message);

    echo json_encode([
        'success' => true,
        'message' => 'Thank you for contacting us! We have received your message and will get back to you soon.',
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save your message. Please try again later.',
    ]);
}

/**
 * Sanitize input
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Send contact email (optional)
 */
function send_contact_email($name, $email, $subject, $message) {
    $to = CONTACT_EMAIL;
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
                .content { margin-top: 20px; }
                .footer { color: var(--text-muted); margin-top: 20px; border-top: 1px solid var(--border); padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                    <p><strong>Message:</strong></p>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <div class='footer'>
                    <p>This message was submitted through the CompatiX contact form.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    mail($to, "CompatiX Contact: " . $subject, $email_body, $headers);
}
