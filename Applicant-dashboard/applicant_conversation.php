<?php
session_start();
require './db.php';

// Security check: ensure user is a logged-in applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$chat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$chat_session = null;
$error_message = '';

if ($chat_id > 0) {
    // Fetch chat session and verify ownership
    $stmt = $conn->prepare(
        "SELECT lc.*, u.name as applicant_name 
         FROM live_chats lc
         JOIN users u ON lc.user_id = u.id
         WHERE lc.id = ? AND lc.user_id = ?"
    );
    $stmt->bind_param("ii", $chat_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $chat_session = $result->fetch_assoc();

        // If chat is 'Pending', we just display it. Only staff can change the status.
        if ($chat_session['status'] === 'Pending') {
            // No action needed from applicant side
        }
    }
    $stmt->close();
}

if (!$chat_session) {
    $error_message = 'Chat session not found or access denied.';
}

// --- Fetch existing messages for this chat ---
$existing_messages = [];
$last_message_id = 0;
if ($chat_id > 0) {
    $msg_stmt = $conn->prepare(
        "SELECT cm.*, u.name as sender_name 
         FROM chat_messages cm
         JOIN users u ON cm.sender_id = u.id
         WHERE cm.chat_id = ? ORDER BY cm.id ASC"
    );
    $msg_stmt->bind_param("i", $chat_id);
    $msg_stmt->execute();
    $existing_messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $msg_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Chat Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="applicant_style.css">
    <style>
        .main { padding: 20px; background-color: #3e88f7ff; }
        .chat-wrapper { max-width: 800px; margin: auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: calc(100vh - 120px); min-height: 500px; }
        .chat-window { flex-grow: 1; padding: 20px; overflow-y: auto; scroll-behavior: smooth; }
        .msg { display: flex; margin-bottom: 15px; max-width: 80%; align-items: flex-end; gap: 10px; }
        .msg.user { margin-left: auto; flex-direction: row-reverse; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: #e9ecef; color: #495057; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; flex-shrink: 0; }
        .msg-content { display: flex; flex-direction: column; }
        .sender-name { font-size: 0.8rem; font-weight: 600; color: #6c757d; margin-bottom: 5px; }
        .msg.user .sender-name { align-self: flex-end; }
        .bubble { padding: 10px 15px; border-radius: 18px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; }
        .timestamp { font-size: 0.75rem; color: #adb5bd; margin-top: 5px; }
        .msg.user .timestamp { align-self: flex-end; }

        /* Message bubble styles */
        .msg.bot .bubble { background: #f1f3f5; color: #343a40; text-align: center; }
        .msg.bot { max-width: 100%; justify-content: center; }
        .msg.user .bubble { background: #4a69bd; color: #fff; border-bottom-right-radius: 4px; }
        .msg.staff .bubble { background: #e9ecef; color: #212529; border-bottom-left-radius: 4px; }

        /* Allow HTML in bubbles */
        .bubble { white-space: normal; }
        .bubble a { color: inherit; font-weight: 600; text-decoration: underline; }
        .bubble a:hover { text-decoration: none; }

        /* Avatar colors */
        .avatar.user-avatar { background-color: #4a69bd; color: white; }
        .avatar.staff-avatar { background-color: #28a745; color: white; }

        /* Typing Indicator */
        .typing-indicator { padding: 5px 20px; font-style: italic; color: #6c757d; font-size: 0.9rem; background: #fff; }
        .typing-indicator p { margin: 0; } /* Kept for simplicity, can be enhanced */

        /* Input area */
        .chat-input { display:flex; gap:10px; background:#fff; border-top: 1px solid #e9ecef; padding: 12px 20px; align-items: center; }
        .btn-file-upload { cursor: pointer; color: #6c757d; font-size: 1.2rem; padding: 10px; transition: color 0.2s; }
        .btn-file-upload:hover { color: #4a69bd; }
        .chat-input input[type="text"] { flex:1; padding:12px 14px; border:1px solid #dde3ec; border-radius:8px; font-size:1rem; }
        .chat-input input[type="text"]:focus { border-color:#4a69bd; outline:none; box-shadow:0 0 0 3px rgba(74,105,189,.18); }
        .chat-input .btn { padding: 12px 16px; border-radius: 10px; }
        .message.error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; }

        /* File Preview */
        .file-preview { padding: 5px 20px 10px; background: #fff; font-size: 0.9rem; color: #495057; display: flex; align-items: center; gap: 10px; }
        .file-preview span { font-style: italic; }
        .file-preview button { background: none; border: none; color: #dc3545; cursor: pointer; font-size: 1rem; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php require_once './applicant_sidebar.php'; ?>

    <div class="main">
        <header class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="applicant_dashboard.php" class="btn" style="padding: 8px 12px;"><i class="fas fa-arrow-left"></i> Back</a>
                <h1>Live Chat Support</h1>
            </div>
        </header>

        <?php if ($error_message): ?>
            <p class="message error"><?= $error_message ?></p>
        <?php else: ?>
            <div class="chat-wrapper">
                <div id="chatWindow" class="chat-window" aria-live="polite">
                    <div class="msg bot">
                        <div class="bubble">
                            <p>You are connected to live chat support. A staff member will be with you shortly.</p>
                            <p><strong>Status:</strong> <span id="chatStatus"><?= htmlspecialchars(ucfirst($chat_session['status'])) ?></span></p>
                        </div>
                    </div>
                    <!-- Inject existing messages here -->
                    <?php foreach ($existing_messages as $msg): ?>
                        <?php $last_message_id = $msg['id']; // Track the last message ID ?>
                        <div class="msg <?= htmlspecialchars($msg['sender_role']) === 'user' ? 'user' : 'staff' ?>">
                            <div class="avatar <?= htmlspecialchars($msg['sender_role']) ?>-avatar"><?= strtoupper(substr($msg['sender_name'], 0, 1)) ?></div>
                            <div class="msg-content">
                                <div class="sender-name"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                <div class="bubble"><?= $msg['message'] // Message is pre-sanitized in the API ?></div>
                                <div class="timestamp"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div id="initial-load-complete" style="display:none;">
                    </div>
                </div>
                <form id="chatForm" class="chat-input" autocomplete="off">
                    <input type="hidden" id="chatId" value="<?= $chat_id ?>">
                    <label for="fileInput" class="btn-file-upload" title="Attach File">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <input type="file" id="fileInput" style="display: none;" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <input type="text" id="userInput" placeholder="Type your message..." required <?= $chat_session['status'] === 'Closed' ? 'disabled' : '' ?>>
                    <button type="submit" class="btn" <?= $chat_session['status'] === 'Closed' ? 'disabled' : '' ?>><i class="fas fa-paper-plane"></i></button>
                </form>
                <div id="filePreview" class="file-preview" style="display: none;"></div>
                <div id="typingIndicator" class="typing-indicator" style="display: none;"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatWindow = document.getElementById('chatWindow');
    if (!chatWindow) return;

    const chatForm = document.getElementById('chatForm'), userInput = document.getElementById('userInput');
    const chatId = document.getElementById('chatId').value, chatStatusSpan = document.getElementById('chatStatus');
    const fileInput = document.getElementById('fileInput'), filePreview = document.getElementById('filePreview');
    let lastMessageId = <?= $last_message_id ?>; // Start polling from the last message loaded by PHP
    let typingTimeout;

    function scrollToBottom() { chatWindow.scrollTop = chatWindow.scrollHeight; }

    function addMessage(sender, text, senderName = null, timestamp = null) {
        const wrapper = document.createElement('div');
        wrapper.className = 'msg ' + (sender === 'user' ? 'user' : (sender === 'staff' ? 'staff' : 'bot'));

        if (sender === 'bot') {
            wrapper.innerHTML = `<div class="bubble">${text}</div>`;
        } else {
            const avatar = document.createElement('div');
            avatar.className = `avatar ${sender}-avatar`;
            avatar.textContent = (senderName || 'U').charAt(0).toUpperCase();

            const msgContent = document.createElement('div');
            msgContent.className = 'msg-content';

            const nameSpan = document.createElement('div');
            nameSpan.className = 'sender-name';
            nameSpan.textContent = senderName || (sender === 'user' ? 'You' : 'Staff');

            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.innerHTML = text; // Use innerHTML to render links

            const timeSpan = document.createElement('div');
            timeSpan.className = 'timestamp';
            timeSpan.textContent = timestamp ? new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            msgContent.append(nameSpan, bubble, timeSpan);
            wrapper.append(avatar, msgContent);
        }

        chatWindow.appendChild(wrapper);
        scrollToBottom();
    }

    function disableChatInput() {
        userInput.disabled = true;
        chatForm.querySelector('button').disabled = true;
        userInput.placeholder = "This chat has been closed.";
    }

    async function fetchMessages() {
        try {
            const response = await fetch(`./chatbot_api.php?action=get_messages&chat_id=${chatId}&last_id=${lastMessageId}`);
            const data = await response.json();
            const typingIndicator = document.getElementById('typingIndicator');

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => { addMessage(msg.sender_role, msg.message, msg.sender_name, msg.created_at); lastMessageId = msg.id; });
            } 

            if (data.status) {
                // Handle typing indicator
                if (data.status.staff_is_typing) {
                    typingIndicator.style.display = 'block';
                } else {
                    typingIndicator.style.display = 'none';
                }
                // Handle chat status (Open/Closed)
                if (chatStatusSpan.textContent !== data.status.status && data.status.status === 'Closed') {
                    chatStatusSpan.textContent = data.status.status;
                    addMessage('bot', 'This chat has been closed by staff.');
                    disableChatInput();
                }
            }
        } catch (error) { console.error('Error fetching messages:', error); }
    }

    async function updateTypingStatus(isTyping) {
        const formData = new FormData();
        formData.append('action', 'update_typing');
        formData.append('chat_id', chatId);
        formData.append('is_typing', isTyping);
        formData.append('sender_role', 'user');
        try { await fetch('./chatbot_api.php', { method: 'POST', body: formData }); }
        catch (error) { console.error('Error updating typing status:', error); }
    }

    userInput.addEventListener('input', () => {
        clearTimeout(typingTimeout);
        updateTypingStatus(true);
        typingTimeout = setTimeout(() => {
            updateTypingStatus(false);
        }, 2000); // User is considered "not typing" after 2 seconds of inactivity
    });


    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = userInput.value.trim();
        if (!message && !fileInput.files[0]) return;

        const formData = new FormData();

        clearTimeout(typingTimeout);
        updateTypingStatus(false);

        formData.append('action', 'send_message');
        formData.append('chat_id', chatId);
        formData.append('message', message);
        formData.append('sender_role', 'user');
        formData.append('sender_id', '<?= $current_user_id ?>'); // Add sender_id for the API
        if (fileInput.files[0]) {
            formData.append('chat_file', fileInput.files[0]);
        }

        try {
            const response = await fetch('./chatbot_api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                // The message will be added via the fetchMessages poll
                userInput.value = '';
                fileInput.value = ''; // Clear file input
                filePreview.style.display = 'none';
                filePreview.innerHTML = '';
            } else {
                addMessage('bot', 'Error: ' + (data.error || 'Could not send message.'));
            }
        } catch (error) {
            console.error('Error sending message:', error);
            addMessage('bot', 'Error: Could not send message.');
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files[0]) {
            filePreview.innerHTML = `<span>Selected: ${this.files[0].name}</span><button type="button" id="removeFileBtn">&times;</button>`;
            filePreview.style.display = 'flex';

            document.getElementById('removeFileBtn').addEventListener('click', () => {
                fileInput.value = '';
                filePreview.style.display = 'none';
                filePreview.innerHTML = '';
            });
        }
    });

    fetchMessages();
    setInterval(fetchMessages, 1500); // Reduced polling time for faster updates
});
</script>
</body>
</html>