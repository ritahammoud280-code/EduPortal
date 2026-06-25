<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli('localhost', 'webuser', 'webpass', 'eduportal');

// IDOR vulnerability: trusts Base64-encoded ID from URL with no ownership check
if (isset($_GET['id'])) {
    $decoded_id = base64_decode($_GET['id']);
    // Weak numeric check — still injectable feel but keeps it simple
    $id = intval($decoded_id);
} else {
    // Fallback to session
    $id = intval($_SESSION['user_id']);
}

$query = "SELECT * FROM users WHERE id=$id";
$result = $conn->query($query);
$profile = $result ? $result->fetch_assoc() : null;

$is_viewing_admin = ($profile && $profile['role'] === 'admin');
$is_own_profile   = ($profile && $profile['username'] === $_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduPortal — Dashboard</title>
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

/* ── Sidebar ── */
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
.sidebar-logo span { color: var(--text-dim); font-size: 11px; display: block; font-family: var(--mono); font-weight: 400; margin-top: 2px; }
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
.nav-label { font-size: 9px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1.5px; margin: 8px 12px 4px; }
.sidebar-footer { margin-top: auto; font-size: 10px; color: var(--text-dim); line-height: 1.8; }
.sidebar-footer span { color: var(--accent2); }

/* ── Main ── */
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

/* ── Profile Card ── */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 20px 24px;
}
.card-label {
    font-size: 9px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 8px;
}
.card-value {
    font-size: 15px;
    color: var(--text-hi);
}
.card-value.accent { color: var(--accent); }
.card-value.role-admin { color: var(--warn); }
.card-value.role-student { color: var(--accent2); }

/* ── Bio Card ── */
.bio-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 20px 24px;
    margin-bottom: 24px;
}
.bio-text {
    font-size: 13px;
    color: var(--text-mid);
    line-height: 1.7;
    margin-top: 8px;
}

/* ── Admin leak box ── */
.admin-leak {
    background: rgba(255,170,0,0.05);
    border: 1px solid rgba(255,170,0,0.25);
    border-radius: 6px;
    padding: 20px 24px;
    margin-bottom: 24px;
}
.admin-leak .leak-title {
    font-size: 10px;
    color: var(--warn);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 12px;
}
.admin-leak .leak-row {
    font-size: 12px;
    color: var(--text-mid);
    line-height: 2;
}
.admin-leak .leak-row strong { color: var(--text-hi); }
.admin-leak .leak-row code {
    color: var(--warn);
    background: rgba(255,170,0,0.08);
    padding: 1px 6px;
    border-radius: 3px;
}

/* ── IDOR warning banner ── */
.idor-banner {
    background: rgba(255,51,85,0.05);
    border: 1px solid rgba(255,51,85,0.2);
    border-radius: 6px;
    padding: 12px 18px;
    font-size: 11px;
    color: var(--danger);
    margin-bottom: 24px;
}

/* ── Flag box ── */
.flag-box {
    background: rgba(0,255,157,0.05);
    border: 1px solid rgba(0,255,157,0.2);
    border-radius: 6px;
    padding: 14px 20px;
    font-size: 12px;
    color: var(--accent2);
    margin-bottom: 24px;
    font-family: var(--mono);
}
.flag-box .flag-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.6;
    margin-bottom: 4px;
}
</style>
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">EduPortal <span>v2.1 // Eastbridge University</span></div>
        </div>
        <nav class="nav">
            <div class="nav-label">Student</div>
            <a href="dashboard.php?id=<?= base64_encode($_SESSION['user_id']) ?>" class="active">My Profile</a>
            <a href="profile_edit.php">Edit Profile</a>
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

    <!-- Main Content -->
    <main class="main">
        <div class="topbar">
            <div class="topbar-title">User <span>Profile</span></div>
            <div class="topbar-meta">
                Viewing ID: <?= htmlspecialchars($_GET['id'] ?? base64_encode($_SESSION['user_id'])) ?><br>
                Decoded: <?= $id ?>
            </div>
        </div>
<?php if(isset($_GET['saved'])): ?>
<div class="flag-box" style="margin-bottom:24px; max-width:100%;">
    <div class="flag-label">System</div>
    Profile updated successfully. Changes are now live.
</div>
<?php endif; ?>

<?php if(isset($_GET['flag1'])): ?>
<div class="flag-box" style="margin-bottom:24px; max-width:100%;">
    <div class="flag-label">Flag #1 — SQL Injection WAF Bypass</div>
    FLAG{sqli_w4f_byp4ss3d}
</div>
<?php endif; ?>
        <?php if (!$is_own_profile): ?>
        <div class="idor-banner">
            ⚠ WARNING: Viewing profile that does not belong to your session. Access not validated.
        </div>
        <?php endif; ?>

        <?php if (!$profile): ?>
            <div class="card"><div class="card-value">User not found.</div></div>
        <?php else: ?>

        <!-- Profile Fields -->
        <div class="profile-grid">
            <div class="card">
                <div class="card-label">User ID</div>
                <div class="card-value accent"><?= htmlspecialchars($profile['id']) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Username</div>
                <div class="card-value"><?= htmlspecialchars($profile['username']) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Email</div>
                <div class="card-value"><?= htmlspecialchars($profile['email']) ?></div>
            </div>
            <div class="card">
                <div class="card-label">Role</div>
                <div class="card-value role-<?= htmlspecialchars($profile['role']) ?>">
                    <?= strtoupper(htmlspecialchars($profile['role'])) ?>
                </div>
            </div>
        </div>

        <!-- Bio — rendered raw for XSS stage -->
        <div class="bio-card">
            <div class="card-label">Biography</div>
            <div class="bio-text"><?= $profile['bio'] ?></div>
        </div>

        <!-- Admin leak — only visible when viewing admin profile via IDOR -->
        <?php if ($is_viewing_admin): ?>
        <div class="flag-box">
            <div class="flag-label">Flag #2 — IDOR</div>
            FLAG{1d0r_b4s3d_4cc3ss_bypassed}
        </div>
        <div class="admin-leak">
            <div class="leak-title">⚙ Admin System Notes — Internal Use Only</div>
            <div class="leak-row">
                <strong>AI Portal:</strong> Running at <code>/ai-internal-portal-v2/</code><br>
                <strong>Note:</strong> AI assistant has access to internal staff directory<br>
                <strong>Reminder:</strong> Bot context includes SSH credential rotation schedule
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>
</body>
</html>
