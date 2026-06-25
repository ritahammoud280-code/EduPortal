<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli('localhost', 'webuser', 'webpass', 'eduportal');

// Update bio — no sanitization, stores raw input directly (XSS vulnerability)
// After saving redirect immediately so script doesn't fire on attacker's browser
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'];
    $id  = $_SESSION['user_id'];
    $conn->query("UPDATE users SET bio='$bio' WHERE id=$id");
    header('Location: dashboard.php?id=' . base64_encode($id) . '&saved=1');
    exit;
}

// Fetch current profile
$id     = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$user   = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduPortal — Edit Profile</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #060a0f;
    --bg-panel: #0b1017;
    --bg-card: #0f1620;
    --border: #1a2535;
    --accent: #00d4ff;
    --accent2: #00ff9d;
    --danger: #ff3355;
    --warn: #ffaa00;
    --text-hi: #e8f4ff;
    --text-mid: #7a9bbf;
    --text-dim: #3d5472;
    --mono: 'JetBrains Mono', monospace;
    --display: 'Syne', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    min-height: 100%;
    background: var(--bg);
    color: var(--text-hi);
    font-family: var(--mono);
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,212,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,212,255,0.025) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
}
.layout {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: 100vh;
}
.sidebar {
    background: var(--bg-panel);
    border-right: 1px solid var(--border);
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 32px;
}
.sidebar-logo {
    font-family: var(--display);
    font-size: 18px;
    font-weight: 800;
    color: var(--accent);
}
.sidebar-logo span {
    color: var(--text-dim);
    font-size: 11px;
    display: block;
    font-family: var(--mono);
    font-weight: 400;
    margin-top: 2px;
}
.nav { display: flex; flex-direction: column; gap: 4px; }
.nav a {
    display: block;
    padding: 9px 12px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--text-mid);
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.15s;
}
.nav a:hover { color: var(--text-hi); border-color: var(--border); background: var(--bg-card); }
.nav a.active { color: var(--accent); border-color: rgba(0,212,255,0.2); background: rgba(0,212,255,0.05); }
.nav-label {
    font-size: 9px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin: 8px 12px 4px;
}
.sidebar-footer {
    margin-top: auto;
    font-size: 10px;
    color: var(--text-dim);
    line-height: 1.8;
}
.sidebar-footer span { color: var(--accent2); }
.main { padding: 40px 48px; }
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
}
.topbar-title { font-family: var(--display); font-size: 24px; font-weight: 700; }
.topbar-title span { color: var(--accent); }
.topbar-meta { font-size: 11px; color: var(--text-dim); text-align: right; line-height: 1.8; }
.form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 28px 32px;
    max-width: 680px;
    margin-bottom: 24px;
}
.form-section-title {
    font-size: 10px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
}
.field { margin-bottom: 20px; }
label {
    font-size: 10px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 8px;
}
input[type="text"], textarea {
    width: 100%;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 11px 14px;
    color: var(--text-hi);
    font-family: var(--mono);
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
}
input[type="text"]:focus,
textarea:focus { border-color: var(--accent); }
input[type="text"][disabled] {
    color: var(--text-dim);
    cursor: not-allowed;
}
textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}
.field-hint {
    font-size: 10px;
    color: var(--text-dim);
    margin-top: 6px;
    line-height: 1.6;
}
.btn-submit {
    padding: 11px 28px;
    background: rgba(0,212,255,0.08);
    border: 1px solid var(--accent);
    border-radius: 4px;
    color: var(--accent);
    font-family: var(--mono);
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-submit:hover { background: rgba(0,212,255,0.15); }
.warn-box {
    background: rgba(255,170,0,0.05);
    border: 1px solid rgba(255,170,0,0.2);
    border-radius: 6px;
    padding: 14px 20px;
    font-size: 11px;
    color: var(--warn);
    margin-bottom: 24px;
    max-width: 680px;
    line-height: 1.7;
}
</style>
</head>
<body>
<div class="layout">

    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">EduPortal <span>v2.1 // Eastbridge University</span></div>
        </div>
        <nav class="nav">
            <div class="nav-label">Student</div>
            <a href="dashboard.php?id=<?= base64_encode($_SESSION['user_id']) ?>">My Profile</a>
            <a href="profile_edit.php" class="active">Edit Profile</a>
            <a href="upload.php">File Upload</a>
            <div class="nav-label">Support</div>
            <a href="ai-internal-portal-v2/index.php">AI Assistant</a>
            <div class="nav-label">Session</div>
            <a href="logout.php">Logout</a>
        </nav>
        <div class="sidebar-footer">
            Session: <span><?= htmlspecialchars($_SESSION['user']) ?></span><br>
            Role: <span><?= htmlspecialchars($_SESSION['role']) ?></span><br>
            [<span>OK</span>] WAF_CORE: Active
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-title">Edit <span>Profile</span></div>
            <div class="topbar-meta">
                User: <?= htmlspecialchars($_SESSION['user']) ?><br>
                ID: <?= htmlspecialchars($_SESSION['user_id']) ?>
            </div>
        </div>

        <div class="warn-box">
            ⚠ Note: Biography field supports plain text only. HTML tags are not recommended.
        </div>

        <div class="form-card">
            <div class="form-section-title">Profile Information</div>
            <form method="POST" action="profile_edit.php">

                <div class="field">
                    <label>Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>

                <div class="field">
                    <label>Email</label>
                    <input type="text" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>

                <div class="field">
                    <label>Biography</label>
                    <textarea name="bio" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio']) ?></textarea>
                    <div class="field-hint">
                        Your biography is visible on your profile and reviewed by faculty staff.<br>
                        Supports plain text. Maximum 500 characters recommended.
                    </div>
                </div>

                <button type="submit" class="btn-submit">Save Changes →</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
