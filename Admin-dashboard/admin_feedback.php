<?php
// Page-specific variables
$page_title = 'Feedback';
$current_page = 'feedback';

// Include Header
require_once __DIR__ . '/admin_header.php';
require_once __DIR__ . '/functions.php';

$message = '';

// --- Handle Delete Feedback (Admin Only) ---
if ($current_user_role === 'admin' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $feedbackIdToDelete = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $feedbackIdToDelete);
    if ($stmt->execute()) {
        $message = '<div class="message success">Feedback deleted successfully.</div>';
    } else {
        $message = '<div class="message error">Failed to delete feedback.</div>';
    }
}

// --- Fetch feedback with Search and Sorting ---
$search_term = trim($_GET['search'] ?? '');
$feedback_items = [];

// Sorting logic
$allowed_sort_cols = ['name', 'message', 'created_at'];
$sort_col = in_array($_GET['sort'] ?? '', $allowed_sort_cols) ? $_GET['sort'] : 'created_at';
$sort_order = (strtolower($_GET['order'] ?? '') === 'asc') ? 'asc' : 'desc';

// Build SQL query
$sql = "SELECT f.id, f.message, f.created_at, u.name, u.email 
        FROM feedback f
        JOIN users u ON f.user_id = u.id";
$params = [];
$types = "";

if (!empty($search_term)) {
    $sql .= " WHERE (u.name LIKE ? OR f.message LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types = "ss";
}

$sql .= " ORDER BY " . $sort_col . " " . $sort_order;

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$feedback_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <div class="header-left">
            <button id="hamburger"><i class="fas fa-bars"></i></button>
            <h1>User Feedback</h1>
        </div>
        <div class="header-right">
            <form action="feedback.php" method="GET" class="filter-controls">
                <div class="search-form">
                <input type="text" name="search" placeholder="Search by user or message..." value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                </div>
                <select name="sort" class="sort-filter" onchange="this.form.submit()">
                    <option value="created_at" <?= ($sort_col === 'created_at') ? 'selected' : '' ?>>Sort by Date</option>
                    <option value="name" <?= ($sort_col === 'name') ? 'selected' : '' ?>>Sort by User</option>
                </select>
                <select name="order" class="sort-filter" onchange="this.form.submit()">
                    <option value="desc" <?= ($sort_order === 'desc') ? 'selected' : '' ?>>Descending</option>
                    <option value="asc" <?= ($sort_order === 'asc') ? 'selected' : '' ?>>Ascending</option>
                </select>
            </form>
        </div>
    </header>

    <?php if (!empty($message)) echo $message; ?>

    <div class="feedback-list">
        <?php if (empty($feedback_items)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <h3>No Feedback Found</h3>
                <p>There is no user feedback matching your criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($feedback_items as $item): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div class="user-info">
                            <div class="user-avatar" style="background-color: #<?= substr(md5($item['name']), 0, 6) ?>;">
                                <span><?= strtoupper(substr($item['name'], 0, 1)) ?></span>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small><a href="mailto:<?= htmlspecialchars($item['email']) ?>"><?= htmlspecialchars($item['email']) ?></a></small>
                            </div>
                        </div>
                        <span class="timestamp"><i class="fas fa-clock"></i> <?= time_ago($item['created_at']) ?></span>
                    </div>
                    <div class="feedback-body">
                        <blockquote><?= nl2br(htmlspecialchars($item['message'])) ?></blockquote>
                    </div>
                    <div class="feedback-footer">
                        <a href="mailto:<?= htmlspecialchars($item['email']) ?>?subject=Re: Your Feedback" class="btn btn-sm btn-outline">
                            <i class="fas fa-reply"></i> Reply via Email
                        </a>
                        <?php if ($current_user_role === 'admin'): ?>
                            <a href="feedback.php?action=delete&id=<?= $item['id'] ?>" class="btn btn-sm btn-danger-outline" onclick="return confirm('Are you sure you want to delete this feedback?');" title="Delete Feedback">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Professional Admin Design System */
/* Sidebar styles copied from Staff dashboard for consistency */
:root {
    --sidebar-bg: #232a3b;
    --sidebar-text: #d0d2d6;
    --sidebar-hover-bg: #3c4b64;
    --sidebar-active-bg: #4a69bd; /* Primary color */
    --sidebar-active-text: #fff;
    --admin-bg: #f1f5f9;
    --card-bg: #ffffff;
    --border-color: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #475569;
    --primary: #4a69bd;
    --danger: #ef4444;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --border-radius: 0.75rem;
}

/* Sidebar Styles */
.sidebar { width: 80px; background: var(--sidebar-bg); padding: 25px 10px; display: flex; flex-direction: column; justify-content: space-between; color: var(--sidebar-text); flex-shrink: 0; transition: width 0.3s ease; overflow-x: hidden; position: fixed; height: 100%; z-index: 100; }
.sidebar:hover { width: 240px; }
.sidebar h2 { margin-bottom: 35px; position: relative; height: 24px; display: flex; align-items: center; }
.sidebar h2 span { font-size: 18px; font-weight: 700; letter-spacing: 1px; color: #fff; white-space: nowrap; opacity: 0; transition: opacity 0.2s ease 0.1s; margin-left: 52px; }
.sidebar h2::before { content: '\f1ad'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 24px; color: #fff; position: absolute; left: 50%; transform: translateX(-50%); transition: left 0.3s ease; }
.sidebar:hover h2 span { opacity: 1; }
.sidebar:hover h2::before { left: 28px; }
.btn-nav { display: flex; align-items: center; justify-content: center; padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; text-decoration: none; background: transparent; color: var(--sidebar-text); font-weight: 600; transition: all 0.2s ease; position: relative; }
.btn-nav i { min-width: 20px; text-align: center; font-size: 1.1em; flex-shrink: 0; }
.btn-nav span { white-space: nowrap; opacity: 0; max-width: 0; overflow: hidden; transition: opacity 0.1s ease, max-width 0.2s ease 0.1s, margin-left 0.2s ease 0.1s; }
.sidebar:hover .btn-nav { justify-content: flex-start; }
.sidebar:hover .btn-nav span { opacity: 1; max-width: 150px; margin-left: 12px; }
.btn-nav:hover { background: var(--sidebar-hover-bg); color: #fff; }
.btn-nav.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-text); }
.btn-nav.logout { margin-top: 20px; color: #e74c3c; }
.btn-nav.logout:hover { background: #e74c3c; color: #fff; }
.notification-badge { background-color: var(--danger); color: white; border-radius: 10px; padding: 2px 6px; font-size: 11px; font-weight: bold; position: absolute; top: 8px; right: 12px; transition: opacity 0.2s, transform 0.2s; transform: scale(1); }
.sidebar:not(:hover) .notification-badge { transform: scale(0.8) translate(8px, -8px); }

/* Main content adjustments for fixed sidebar */
.main { margin-left: 80px; transition: margin-left 0.3s ease; padding: 1.5rem; }

/* General page layout styles from other admin pages for consistency */
.header-right { display: flex; align-items: center; gap: 1rem; }
.filter-controls { display: flex; align-items: center; gap: 0.75rem; }
.search-form { position: relative; }
.search-form input { padding: 0.6rem 1rem 0.6rem 2.5rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 0.875rem; background-color: var(--card-bg); }
.search-form input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2); outline: none; }
.search-form button { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; }
.sort-filter { padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 0.875rem; background-color: var(--card-bg); color: var(--text-primary); }

/* New Professional Feedback List Design */
.feedback-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 1.5rem;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}
.feedback-card {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s ease;
    border-left: 4px solid var(--primary); /* Accent border */
}
.feedback-card:hover {
    box-shadow: var(--shadow-md);
}
.feedback-header {
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}
.user-info { display: flex; align-items: center; gap: 0.75rem; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
.user-info strong { font-weight: 600; color: var(--text-primary); }
.user-info small a { color: var(--text-secondary); font-size: 0.875rem; text-decoration: none; }
.user-info small a:hover { text-decoration: underline; color: var(--primary); }

.timestamp { font-size: 0.875rem; color: var(--text-secondary); }
.timestamp i { margin-right: 4px; }

.feedback-body {
    padding: 1.5rem;
    flex-grow: 1;
}
.feedback-body blockquote {
    margin: 0;
    padding: 0;
    font-size: 1rem;
    line-height: 1.7;
    color: var(--text-primary);
    position: relative;
    padding-left: 2rem;
    border-left: 3px solid #e9ecef;
}
.feedback-body blockquote::before {
    content: '\f10d'; /* Font Awesome quote-left icon */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    left: 0;
    top: -5px;
    font-size: 1.2rem;
    color: #cbd5e1;
}

.feedback-footer {
    padding: 1rem 1.25rem;
    background-color: #f8fafc;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
.btn-danger-outline { background-color: transparent; border-color: var(--danger); color: var(--danger); }
.btn-danger-outline:hover { background-color: var(--danger); color: white; border-color: var(--danger); }
.empty-state { text-align: center; padding: 4rem 1rem; color: var(--text-secondary); grid-column: 1 / -1; }
.empty-state i { font-size: 3.5rem; margin-bottom: 1.5rem; color: #cbd5e1; }
.empty-state h3 { font-size: 1.5rem; color: var(--text-primary); }
.empty-state p { font-size: 1rem; }
</style>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>
