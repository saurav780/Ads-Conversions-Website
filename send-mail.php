<?php
header('Content-Type: application/json');

$to = 'saurav@adsconversions.com,connect@adsconversions.com';
$subject = 'New Lead from Ads Conversions Contact Form';

function clean_field($value) {
    return trim(str_replace(["\r", "\n"], ' ', (string) $value));
}

$name = clean_field($_POST['name'] ?? '');
$phone = clean_field($_POST['phone'] ?? '');
$emailRaw = clean_field($_POST['email'] ?? '');
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : '';
$business = clean_field($_POST['business'] ?? '');
$source = clean_field($_POST['source'] ?? 'Website form');
$page = clean_field($_POST['page'] ?? '');
$message = trim((string) ($_POST['message'] ?? ''));

if (!$emailRaw && !$phone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide an email or phone number.']);
    exit;
}

if ($emailRaw && !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

$submissionDir = __DIR__ . '/submissions';
$saved = false;

$submissionBody = "Source: $source\n";
$submissionBody .= "Page: $page\n";
$submissionBody .= "Name: $name\n";
$submissionBody .= "Phone: $phone\n";
$submissionBody .= "Email: $emailRaw\n";
$submissionBody .= "Business / Website: $business\n\n";
$submissionBody .= "Message:\n$message\n";

if (is_dir($submissionDir) || mkdir($submissionDir, 0777, true)) {
    $submissionFile = $submissionDir . '/submission-' . time() . '-' . bin2hex(random_bytes(4)) . '.txt';
    $saved = file_put_contents($submissionFile, $submissionBody) !== false;
}

$body = "You have received a new contact form submission from Ads Conversions.\n\n";
$body .= $submissionBody;

$headers = [];
$headers[] = 'From: Ads Conversions <noreply@adsconversions.com>';
if ($email) {
    $headers[] = 'Reply-To: ' . $email;
}
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$mailSent = @mail($to, $subject, $body, implode("\r\n", $headers));

if (!$saved && !$mailSent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process the submission right now.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Message received successfully.',
    'mailSent' => $mailSent,
    'saved' => $saved
]);