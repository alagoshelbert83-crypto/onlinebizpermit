<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../db.php';

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get token from Authorization header
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    $token = '';

    if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
    }

    if (empty($token)) {
        $response['message'] = 'No authorization token provided';
        echo json_encode($response);
        exit;
    }

    try {
        // Get session data
        $stmt = $conn->prepare("SELECT session_data FROM user_sessions WHERE session_id = ? AND session_expires > CURRENT_TIMESTAMP");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if (!$session) {
            $response['message'] = 'Invalid or expired session';
            echo json_encode($response);
            exit;
        }

        $session_data = json_decode($session['session_data'], true);
        $user_id = $session_data['user_id'];

        // Get user applications
        $stmt = $conn->prepare("SELECT id, business_name, status, submitted_at, application_type FROM applications WHERE user_id = ? ORDER BY submitted_at DESC");
        $stmt->execute([$user_id]);
        $applications = $stmt->fetchAll();

        // Get user notifications
        $stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();

        $response['success'] = true;
        $response['message'] = 'Dashboard data retrieved successfully';
        $response['data'] = [
            'user' => $session_data,
            'applications' => $applications,
            'notifications' => $notifications
        ];

    } catch(PDOException $e) {
        $response['message'] = 'Database error occurred';
        error_log("Dashboard API database error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Method not allowed';
}

echo json_encode($response);
?>
