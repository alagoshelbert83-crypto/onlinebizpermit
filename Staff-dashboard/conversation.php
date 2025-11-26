<?php
$page_title = 'Live Chat';
$current_page = 'live_chats';

require_once './staff_header.php';

$chat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$chat_session = null;
$error_message = '';
$staff_id = $_SESSION['user_id'];

if ($chat_id > 0) {
    // Fetch chat session details
    $stmt = $conn->prepare(
        "SELECT lc.*, u.name as applicant_name
         FROM live_chats lc
         JOIN users u ON lc.user_id = u.id
         WHERE lc.id = ?"
    );
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $chat_session = $result->fetch_assoc();

        // If chat is 'Pending', assign it to the current staff member and set status to 'Active'
        if ($chat_session['status'] === 'Pending') {
            $update_stmt = $conn->prepare("UPDATE live_chats SET staff_id = ?, status = 'Active' WHERE id = ?");
            $update_stmt->bind_param("ii", $staff_id, $chat_id);
            $update_stmt->execute();
            $update_stmt->close();
            // Refresh chat session data
            $chat_session['status'] = 'Active';
            $chat_session['staff_id'] = $staff_id;
        }
    } else {
        $error_message = 'Chat session not found.';
    }
    $stmt->close();
} else {
    $error_message = 'No chat ID provided.';
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

require_once './staff_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <?php if ($error_message): ?>
        <div class="message error"><?= $error_message ?></div>
    <?php else: ?>
        <div class="chat-wrapper">
            <div class="chat-header">
                <a href="staff_conversations.php" class="btn-back" title="Back to Chats"><i class="fas fa-arrow-left"></i></a>
                <div class="avatar user-avatar" style="background-color: #<?= substr(md5($chat_session['applicant_name']), 0, 6) ?>;">
                    <?= strtoupper(substr($chat_session['applicant_name'], 0, 1)) ?>
                </div>
                <div class="header-info">
                    <h2><?= htmlspecialchars($chat_session['applicant_name'] ?? 'Applicant') ?></h2>
                    <p id="chatStatusText">Status: <span id="chatStatus"><?= htmlspecialchars(ucfirst($chat_session['status'])) ?></span></p>
                </div>
                <div class="header-actions"></div>
            </div>
            <div id="chatWindow" class="chat-window" aria-live="polite">
                <div class="msg bot">
                    <div class="bubble">
                        <p>You are connected to the chat with <?= htmlspecialchars($chat_session['applicant_name']) ?>.</p>
                    </div>
                </div>
                <!-- Inject existing messages here -->
                <?php foreach ($existing_messages as $msg): ?>
                    <?php $last_message_id = $msg['id']; // Track the last message ID ?>
                    <div class="msg <?= htmlspecialchars($msg['sender_role']) === 'staff' ? 'staff' : 'user' ?>">
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
                <button type="submit" class="btn" title="Send Message" <?= $chat_session['status'] === 'Closed' ? 'disabled' : '' ?>><i class="fas fa-paper-plane"></i></button>
            </form>
            <div id="filePreview" class="file-preview" style="display: none;"></div>
        </div>
    <?php endif; ?>
</div>

<style>
    /* --- Premium Live Chat Styles --- */
    .main { padding: 20px; background-color: #eef2f7; }
    .chat-wrapper {
        max-width: 900px;
        margin: auto;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        border: 1px solid #e5e9f2;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 140px);
        min-height: 600px;
    }
    .chat-header {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        border-bottom: 1px solid #e5e9f2;
        background: #f8fafc;
        border-radius: 16px 16px 0 0;
    }
    .btn-back { background: none; border: 1px solid #dde3ec; color: #555; width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
    .btn-back:hover { background: #e9ecef; }
    .header-info { flex-grow: 1; }
    .header-info h2 { font-size: 1.2rem; margin: 0; color: #333; }
    .header-info p { margin: 0; font-size: 0.9rem; color: #6c757d; }
    .header-actions { margin-left: auto; }
    .header-actions .btn-danger { display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 25px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s ease; }
    .header-actions .btn-danger i { transition: transform 0.3s ease; }
    .header-actions .btn-danger:hover i { transform: rotate(90deg); }

    .chat-window {
        flex-grow: 1;
        padding: 20px 30px;
        overflow-y: auto;
        scroll-behavior: smooth;
        background-image: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23f0f4f9" fill-opacity="0.4"><path d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/></g></g></svg>');
    }
    .msg { display: flex; margin-bottom: 20px; max-width: 90%; align-items: flex-end; gap: 10px; }
    .msg.staff { margin-left: auto; flex-direction: row-reverse; }
    .msg.user { margin-right: auto; } /* Ensure user messages align left */
    .avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 1rem; flex-shrink: 0;
        color: white;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .msg-content { display: flex; flex-direction: column; }
    .sender-name { font-size: 0.8rem; font-weight: 600; color: #6c757d; margin-bottom: 5px; }
    .msg.staff .sender-name { align-self: flex-end; }
    .bubble {
        padding: 12px 18px;
        border-radius: 20px;
        line-height: 1.55;
        white-space: pre-wrap;
        word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .timestamp { font-size: 0.75rem; color: #adb5bd; margin-top: 5px; }
    .msg.staff .timestamp { align-self: flex-end; }

    /* Message bubble styles */
    .msg.bot { max-width: 100%; justify-content: center; }
    .msg.bot .bubble { background: #e9ecef; color: #495057; text-align: center; box-shadow: none; font-size: 0.9rem; }
    .msg.staff .bubble { background: linear-gradient(135deg, #28a745, #218838); color: #fff; border-bottom-right-radius: 5px; }
    .msg.user .bubble { background: linear-gradient(135deg, #4a69bd, #3c5aa6); color: #fff; border-bottom-left-radius: 5px; }

    /* Avatar colors */
    .avatar.user-avatar { background-color: #4a69bd; } /* This is dynamically overridden in the PHP */
    .avatar.staff-avatar { background-color: #28a745; }

    /* Typing Indicator with animation */
    .msg.typing .bubble { background: #f1f3f5; padding: 12px 18px; display: flex; gap: 5px; align-items: center; }
    .typing-dot { width: 8px; height: 8px; background-color: #bdc3c7; border-radius: 50%; animation: typing-blink 1.4s infinite both; }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing-blink { 0% { opacity: 0.2; } 20% { opacity: 1; } 100% { opacity: 0.2; } }

    /* --- Input Area --- */
    .chat-input { display:flex; gap:10px; background:#f8fafc; border-top: 1px solid #e9ecef; padding: 15px 20px; }
    .btn-file-upload { cursor: pointer; color: #6c757d; font-size: 1.2rem; padding: 10px; transition: color 0.2s; }
    .btn-file-upload:hover { color: #4a69bd; }
    .chat-input input[type="text"] { flex:1; padding:14px 18px; border:1px solid #dde3ec; border-radius:25px; font-size:1rem; transition: all 0.2s ease; }
    .chat-input input[type="text"]:focus { border-color:#28a745; outline:none; box-shadow:0 0 0 4px rgba(40,167,69,.1); background-color: #fff; }
    .chat-input .btn {
        padding: 0; width: 50px; height: 50px; border-radius: 50%;
        background: linear-gradient(45deg, #2ecc71, #28a745); color: white;
        font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
        border: none; cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        box-shadow: 0 4px 15px rgba(40,167,69,0.2);
    }
    .chat-input .btn:hover {
        transform: scale(1.1) rotate(-15deg);
        box-shadow: 0 6px 20px rgba(40,167,69,0.35);
    }
    .chat-input .btn:active {
        transform: scale(0.95);
        box-shadow: 0 2px 10px rgba(40,167,69,0.2);
    }
    .chat-input input[disabled] { background-color: #f1f3f5; cursor: not-allowed; }
    .chat-input button[disabled] { background: #95a5a6; cursor: not-allowed; transform: none; box-shadow: none; opacity: 0.7; }

    /* File Preview */
    .file-preview { padding: 5px 20px 10px; background: #f8fafc; font-size: 0.9rem; color: #495057; display: flex; align-items: center; gap: 10px; }
    .file-preview span { font-style: italic; }
    .file-preview button { background: none; border: none; color: #dc3545; cursor: pointer; font-size: 1rem; }

    /* Allow HTML in bubbles */
    .bubble { white-space: normal; }
    .bubble a { color: inherit; font-weight: 600; text-decoration: underline; }
    .bubble a:hover { text-decoration: none; }

    .btn-danger { background-color: #e74c3c; color: white; }
    .btn-danger:hover { background-color: #c0392b; }
    .message.error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatWindow = document.getElementById('chatWindow');
    if (!chatWindow) return;

    const chatForm = document.getElementById('chatForm'), userInput = document.getElementById('userInput');
    const chatId = document.getElementById('chatId').value, chatStatusSpan = document.getElementById('chatStatus'), chatStatusText = document.getElementById('chatStatusText');
    const fileInput = document.getElementById('fileInput'), filePreview = document.getElementById('filePreview');
    let lastMessageId = <?= $last_message_id ?>; // Start polling from the last message loaded by PHP
    let typingTimeout;

    function scrollToBottom() { chatWindow.scrollTop = chatWindow.scrollHeight; }

    function addMessage(sender, text, senderName = null, timestamp = null) {
        removeTypingIndicator(); // Remove any existing typing indicators
        const wrapper = document.createElement('div');
        wrapper.className = 'msg ' + (sender === 'staff' ? 'staff' : (sender === 'user' ? 'user' : 'bot'));

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
            nameSpan.textContent = senderName || (sender === 'staff' ? 'You' : 'Applicant');

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
        if (chatStatusSpan) {
            chatStatusSpan.textContent = 'Closed';
            chatStatusText.style.color = '#dc3545';
        }
    }

    function showTypingIndicator() {
        if (document.querySelector('.msg.typing')) return; // Already showing
        const wrapper = document.createElement('div');
        wrapper.className = 'msg typing';
        wrapper.innerHTML = `
            <div class="avatar user-avatar" style="background-color: #${'<?= substr(md5($chat_session['applicant_name'] ?? 'user'), 0, 6) ?>'}">
                <?= strtoupper(substr($chat_session['applicant_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="bubble">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
        chatWindow.appendChild(wrapper);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const indicator = document.querySelector('.msg.typing');
        if (indicator) indicator.remove();
    }

    async function fetchMessages() {
        try {
            // Note: The API endpoint is in the Applicant-dashboard folder.
            const response = await fetch(`../Applicant-dashboard/chatbot_api.php?action=get_messages&chat_id=${chatId}&last_id=${lastMessageId}`);
            const data = await response.json();

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => { addMessage(msg.sender_role, msg.message, msg.sender_name, msg.created_at); lastMessageId = msg.id; });
            }
            if (data.status) {
                // Handle typing indicator
                data.status.user_is_typing ? showTypingIndicator() : removeTypingIndicator();

                // Handle chat status (Open/Closed)
                if (chatStatusSpan.textContent !== data.status.status && data.status.status === 'Closed') {
                    chatStatusSpan.textContent = data.status.status;
                    addMessage('bot', 'This chat session is now closed.');
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
        formData.append('sender_role', 'staff');
        try { await fetch('../Applicant-dashboard/chatbot_api.php', { method: 'POST', body: formData }); }
        catch (error) { console.error('Error updating typing status:', error); }
    }

    userInput.addEventListener('input', () => {
        clearTimeout(typingTimeout);
        updateTypingStatus(true);
        typingTimeout = setTimeout(() => {
            updateTypingStatus(false);
        }, 2000); // Considered "not typing" after 2 seconds
    });

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = userInput.value.trim();
        if (!message) return;

        clearTimeout(typingTimeout);
        updateTypingStatus(false);

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('chat_id', chatId);
        formData.append('message', message);
        formData.append('sender_role', 'staff');
        formData.append('sender_id', '<?= $staff_id ?>'); // Add sender_id for the API
        if (fileInput.files[0]) {
            formData.append('chat_file', fileInput.files[0]);
        }

        try {
            const response = await fetch('../Applicant-dashboard/chatbot_api.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                // The message will be added via the fetchMessages poll to ensure it has the correct timestamp and content from the server
                userInput.value = '';
                fileInput.value = ''; // Clear file input
                filePreview.style.display = 'none';
                filePreview.innerHTML = '';
            } else {
                addMessage('bot', 'Error: ' + (data.error || 'Could not send message.'));
            }
        } catch (error) {
            console.error('Error sending message:', error); addMessage('bot', 'Error: Could not send message.');
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

<?php require_once './staff_footer.php'; ?>
