<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../db.php';

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        echo json_encode($response);
        exit;
    }

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response['message'] = 'Please provide both email and password';
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT id, name, password, role, is_approved FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Check if the user is an applicant (role = 'user')
                if ($user['role'] === 'user') {
                    // Check if user is approved
                    $is_approved = (int)$user['is_approved'];

                    if ($is_approved === 0) {
                        $response['message'] = 'Your account is pending admin approval. Please wait for approval before logging in.';
                    } elseif ($is_approved === 1) {
                        // Generate session token (simplified for API)
                        $session_token = bin2hex(random_bytes(32));

                        // Store session in database
                        $stmt = $conn->prepare("INSERT INTO user_sessions (session_id, session_data, session_expires) VALUES (?, ?, ?)
                                               ON CONFLICT (session_id) DO UPDATE SET session_data = EXCLUDED.session_data, session_expires = EXCLUDED.session_expires");
                        $session_data = json_encode([
                            'user_id' => $user['id'],
                            'user_name' => $user['name'],
                            'role' => $user['role']
                        ]);
                        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                        $stmt->execute([$session_token, $session_data, $expires]);

                        $response['success'] = true;
                        $response['message'] = 'Login successful';
                        $response['data'] = [
                            'token' => $session_token,
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $email,
                                'role' => $user['role']
                            ]
                        ];
                    } else {
                        $response['message'] = 'Your account has been rejected. Please contact support for more information.';
                    }
                } else {
                    $response['message'] = 'This login is for applicants only. Please use the appropriate login portal.';
                }
            } else {
                $response['message'] = 'Invalid email or password.';
            }
        } else {
            $response['message'] = 'Invalid email or password.';
        }
    } catch(PDOException $e) {
        $response['message'] = 'Database error occurred. Please try again.';
        error_log("Login API database error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Method not allowed';
}

echo json_encode($response);
?>
