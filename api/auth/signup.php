<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../db.php';

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        echo json_encode($response);
        exit;
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    $phone = trim($input['phone'] ?? '');
    $terms = $input['terms'] ?? false;

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone)) {
        $response['message'] = 'Please enter a valid phone number.';
        echo json_encode($response);
        exit;
    }

    if (!$terms) {
        $response['message'] = 'Please accept the terms and conditions.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $response['message'] = 'An account with this email already exists.';
            echo json_encode($response);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with pending approval (is_approved = 0)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, is_approved, created_at) VALUES (?, ?, ?, 'user', ?, 0, CURRENT_TIMESTAMP)");
        $stmt->execute([$name, $email, $hashed_password, $phone]);

        $response['success'] = true;
        $response['message'] = 'Registration successful! Your account is pending admin approval. You will be notified once approved.';
        $response['data'] = ['email' => $email];

    } catch(PDOException $e) {
        $response['message'] = 'Registration failed. Please try again.';
        error_log("Signup API database error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Method not allowed';
}

echo json_encode($response);
?>
