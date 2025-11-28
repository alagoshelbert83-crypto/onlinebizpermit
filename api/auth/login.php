<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../db.php';

// Define session lifetime in seconds (e.g., 1 hour)
define('SESSION_LIFETIME', 3600);

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

        // To mitigate timing attacks, we perform a password check even if the user is not found.
        // If no user, we hash a dummy password.
        $password_hash = $user ? $user['password'] : password_hash('dummy_password_for_timing_attack_mitigation', PASSWORD_DEFAULT);

        if (!$user || !password_verify($password, $password_hash)) {
            $response['message'] = 'Invalid email or password.';
            echo json_encode($response);
            exit;
        }

        // At this point, the user is authenticated. Now check authorization.
        if ($user['role'] !== 'user') {
            $response['message'] = 'This login is for applicants only. Please use the appropriate login portal.';
            echo json_encode($response);
            exit;
        }

        $is_approved = (int)$user['is_approved'];
        if ($is_approved === 0) {
            $response['message'] = 'Your account is pending admin approval. Please wait for approval before logging in.';
            echo json_encode($response);
            exit;
        }

        if ($is_approved !== 1) {
            $response['message'] = 'Your account has been rejected. Please contact support for more information.';
            echo json_encode($response);
            exit;
        }

        // --- Login successful, create session ---
        $session_token = bin2hex(random_bytes(32));

        // Store session in database
        $stmt = $conn->prepare("INSERT INTO user_sessions (session_id, session_data, session_expires) VALUES (?, ?, ?)
                               ON CONFLICT (session_id) DO UPDATE SET session_data = EXCLUDED.session_data, session_expires = EXCLUDED.session_expires");
        $session_data = json_encode([
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'role' => $user['role']
        ]);
        $expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
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

    } catch(PDOException $e) {
        $response['message'] = 'Database error occurred. Please try again.';
        error_log("Login API database error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Method not allowed';
    http_response_code(405);
}

echo json_encode($response);
?>
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
