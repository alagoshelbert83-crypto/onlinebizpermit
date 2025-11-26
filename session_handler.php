<?php
/**
 * Custom Session Handler for Serverless Environment
 * Stores sessions in database since file-based sessions don't persist
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $conn;
    private $table = 'user_sessions';

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function open($savePath, $sessionName) {
        // Create sessions table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            session_id VARCHAR(255) PRIMARY KEY,
            session_data TEXT,
            session_expires TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->query($sql);
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        $stmt = $this->conn->prepare("SELECT session_data FROM {$this->table} WHERE session_id = ? AND session_expires > NOW()");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $data = '';
        $stmt->bind_result($data);
        if ($stmt->fetch()) {
            $stmt->close();
            return $data;
        }
        $stmt->close();
        return '';
    }

    public function write($sessionId, $data) {
        // Set session to expire in 24 hours
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (session_id, session_data, session_expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), session_expires = VALUES(session_expires)");
        $stmt->bind_param("sss", $sessionId, $data, $expires);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function destroy($sessionId) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE session_id = ?");
        $stmt->bind_param("s", $sessionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function gc($maxLifetime) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE session_expires < NOW()");
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

// Initialize custom session handler if database connection exists
if (isset($conn) && $conn instanceof mysqli) {
    $sessionHandler = new DatabaseSessionHandler($conn);
    session_set_save_handler($sessionHandler, true);
}
?>
