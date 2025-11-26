<?php
// Page-specific variables
$page_title = 'FAQ';
$current_page = 'faq';

// Include Header
require_once __DIR__ . '/applicant_header.php';
require_once __DIR__ . '/faq-data.php';

// Build a flattened list of visible FAQs for the accordion (top-level relevant entries)
$accordionItems = [];
foreach ($faqs as $item) {
    // Skip very granular steps to keep page concise
    if (in_array($item['id'], ['step1','step2','step3','step4','how_to_pay','payment_issues','resubmit_process'])) {
        continue;
    }
    $accordionItems[] = $item;
}

// Include Sidebar
require_once __DIR__ . '/applicant_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <div class="form-container" style="max-width: 900px;">
        <header class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="applicant_dashboard.php" class="btn" style="padding: 8px 12px;"><i class="fas fa-arrow-left"></i> Back</a>
                <h1>Help & FAQ Assistant</h1>
            </div>
        </header>

        <div class="chat-wrapper">
            <div id="chatWindow" class="chat-window" aria-live="polite" aria-label="FAQ conversation">
                <!-- Messages injected here -->
            </div>
            <div id="quickReplies" class="quick-replies" role="toolbar" aria-label="Quick replies">
                <!-- Quick replies injected here -->
            </div>
            <form id="chatForm" class="chat-input" autocomplete="off">
                <input type="text" id="userInput" name="q" placeholder="Ask about applications, requirements, payments..." aria-label="Type your question" required>
                <button type="submit" class="btn"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<style>
    .chat-wrapper { display:flex; flex-direction: column; gap: 12px; }
    .chat-header { font-size: 1.2rem; font-weight: 600; color: #333; text-align: center; padding: 10px; background: #f9f9f9; border-radius: 12px 12px 0 0; border-bottom: 1px solid #e6ebf2;}
    .chat-window {
        background: linear-gradient(180deg, #f8fafc 0%, #f2f5fa 100%);
        border: 1px solid #e6ebf2; border-radius: 16px; padding: 18px;
        height: 560px; overflow-y: auto; scroll-behavior: smooth;
        display: flex;
        flex-direction: column;
    }
    /* Polished scrollbar */
    .chat-window::-webkit-scrollbar { width: 10px; }
    .chat-window::-webkit-scrollbar-track { background: transparent; }
    .chat-window::-webkit-scrollbar-thumb { background: #d6deea; border-radius: 10px; border: 2px solid #f8fafc; }
    .chat-window::-webkit-scrollbar-thumb:hover { background: #c5d1e3; }

    .msg { display:flex; margin-bottom: 14px; gap:10px; align-items:flex-end; }
    .msg .bubble {
        padding: 12px 14px; border-radius: 16px; max-width: 78%; white-space: pre-wrap; word-wrap: break-word;
        box-shadow: 0 6px 20px rgba(16, 24, 40, 0.06);
        line-height: 1.5;
    }
    .msg.bot .bubble {
        background:#ffffff; border:1px solid #e6ebf2; color:#2f3a4a; border-radius: 16px 16px 16px 4px;
    }
    .msg.user { justify-content: flex-end; }
    .msg.user .bubble {
        background: linear-gradient(135deg, #4a69bd, #3e5aa2);
        color:#fff;
    }
    .typing { display:flex; gap:6px; align-items:center; margin: 6px 0 14px; padding: 6px 0; }
    .dot { width: 7px; height: 7px; background:#c3cada; border-radius:50%; animation: blink 1.4s infinite; }
    .dot:nth-child(2){ animation-delay: .2s } .dot:nth-child(3){ animation-delay: .4s }
    @keyframes blink { 0%, 80%, 100% { opacity:.2 } 40% { opacity:1 } }

    .quick-replies { display:flex; gap:10px; flex-wrap:wrap; padding: 10px 0; }
    .chip { padding:9px 14px; background:#eef2ff; border:1px solid #d6e0ff; color:#2f4c9a; border-radius:18px; font-size:13px; cursor:pointer; transition: all .15s ease; }
    .chip:hover { background:#e2e9ff; box-shadow: 0 2px 8px rgba(17,24,39,.08); transform: translateY(-1px); }

    .chat-input { display:flex; gap:10px; background:#fff; border:1px solid #e6ebf2; border-radius: 12px; padding: 8px; margin-top: auto; }
    .chat-input input[type="text"] {
        flex:1; padding:12px 14px; border:1px solid #dde3ec; border-radius:8px; font-size:1rem; background:#fff; color:#2f3a4a;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .chat-input input[type="text"]::placeholder { color:#9aa7b8; }
    .chat-input input[type="text"]:focus { border-color:#4a69bd; outline:none; box-shadow:0 0 0 3px rgba(74,105,189,.18); }
    .chat-input .btn { display:flex; align-items:center; gap:8px; padding: 12px 16px; border-radius: 10px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatWindow = document.getElementById('chatWindow');
    const quickReplies = document.getElementById('quickReplies');
    const chatForm = document.getElementById('chatForm');
    const userInput = document.getElementById('userInput');

    function scrollToBottom() { chatWindow.scrollTop = chatWindow.scrollHeight; }

    function showTyping() {
        const t = document.createElement('div');
        t.className = 'typing';
        t.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
        chatWindow.appendChild(t);
        scrollToBottom();
        return t;
    }

    function addMessage(role, text) {
        const wrapper = document.createElement('div');
        wrapper.className = 'msg ' + role;
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = text;
        wrapper.appendChild(bubble); 
        chatWindow.appendChild(wrapper);
        scrollToBottom();
    }

    function renderChoices(choices) {
        quickReplies.innerHTML = '';
        (choices || []).forEach(c => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip';
            chip.textContent = c.text;
            chip.addEventListener('click', () => handleAction(c.action));
            quickReplies.appendChild(chip);
        });
    }

    async function handleAction(actionId) {
        // Special case for live chat request
        if (actionId === 'live_chat_request') {
            addMessage('bot', 'I am creating a live chat request for you now. Please wait a moment...');
            const typing = showTyping();
            
            try {
                // Use API to create chat session
                const response = await fetch('chatbot_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create_live_chat'
                });

                const data = await response.json();
                typing.remove();

                if (data.success && data.chat_id) {
                    addMessage('bot', 'Chat request created! Redirecting you to the live chat room...');
                    setTimeout(() => {
                        window.location.href = `applicant_conversation.php?id=${data.chat_id}`;
                    }, 1500);
                } else {
                    // Display the specific error from the server if available
                    const errorMessage = data.error || 'I was unable to create a live chat session. Please try again or contact support directly.';
                    addMessage('bot', 'Sorry, ' + errorMessage);
                    // Directly show the welcome menu instead of re-calling handleAction to avoid cascading errors
                    await showWelcomeMessage();
                }
            } catch (error) {
                typing.remove();
                addMessage('bot', 'Sorry, something went wrong while trying to connect to live chat. Please check your connection and try again.');
                console.error('Error creating live chat:', error);
                // Directly show the welcome menu instead of re-calling handleAction
                await showWelcomeMessage();
            }
            return;
        }

        // For all other actions, use the API
        const typing = showTyping();
        try {
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${encodeURIComponent(actionId)}`
            });
            const data = await response.json();
            typing.remove();
            addMessage('bot', data.reply);
            renderChoices(data.choices);
        } catch (error) {
            typing.remove();
            addMessage('bot', 'Sorry, something went wrong. Please try again.');
            console.error('Error handling action:', error);
        }
    }

    async function showWelcomeMessage() {
        const typing = showTyping();
        try {
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=welcome`
            });
            const data = await response.json();
            typing.remove();
            addMessage('bot', data.reply);
            renderChoices(data.choices);
        } catch (error) {
            typing.remove();
            addMessage('bot', 'Sorry, something went wrong. Please try again.');
        }
    }

    // Check if we need to auto-start a live chat
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'start_chat') {
        // Directly trigger the live chat creation process
        handleAction('live_chat_request');
    } else {
        // Normal welcome
        showWelcomeMessage();
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const q = userInput.value.trim();
        if (!q) return;
        addMessage('user', q);
        userInput.value = '';
        quickReplies.innerHTML = ''; // Clear quick replies on user input
        
        const typing = showTyping();
        try {
            const response = await fetch('chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `message=${encodeURIComponent(q)}`
            });
            const data = await response.json();
            typing.remove();
            addMessage('bot', data.reply);
            renderChoices(data.choices);
        } catch (error) {
            typing.remove();
            addMessage('bot', 'Sorry, I could not process your request. Please try again.');
            console.error('Error sending message:', error);
        }
    });
});
</script>

<?php
// Include Footer
require_once __DIR__ . '/applicant_footer.php';
?>