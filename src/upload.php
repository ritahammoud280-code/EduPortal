<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$success = false;
$error   = '';
$uploaded_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file     = $_FILES['file'];
    $name     = $file['name'];
    $tmp      = $file['tmp_name'];
    $size     = $file['size'];
    $mime     = $file['type'];

    // ── Vulnerable validation ──
    // Only checks if "image" appears in MIME type
    // Attacker can spoof MIME type easily
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($size > 5000000) {
        $error = "File too large. Maximum size is 5MB.";
    } elseif (!strstr($mime, 'image')) {
        // Only checks MIME — easily bypassed with Burp Suite
        $error = "Invalid file type. Only images are allowed.";
    } else {
        // Saves with original filename — no sanitization
        $dest = '/var/www/html/uploads/' . $name;
        if (move_uploaded_file($tmp, $dest)) {
            $success = true;
            $uploaded_path = 'uploads/' . $name;
        } else {
            $error = "Upload failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduPortal — File Upload</title>
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

/* ── Upload card ── */
.upload-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 32px;
    max-width: 680px;
    margin-bottom: 24px;
}
.upload-card-title {
    font-size: 10px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
}
.dropzone {
    border: 2px dashed var(--border);
    border-radius: 6px;
    padding: 48px 32px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 20px;
    position: relative;
}
.dropzone:hover {
    border-color: var(--accent);
    background: rgba(0,212,255,0.02);
}
.dropzone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.dropzone-icon { font-size: 32px; margin-bottom: 12px; opacity: 0.5; }
.dropzone-text {
    font-size: 13px;
    color: var(--text-mid);
    margin-bottom: 6px;
}
.dropzone-hint {
    font-size: 10px;
    color: var(--text-dim);
}
.dropzone-hint code {
    color: var(--accent);
    background: rgba(0,212,255,0.08);
    padding: 1px 5px;
    border-radius: 3px;
}
.btn-upload {
    width: 100%;
    padding: 12px;
    background: rgba(0,212,255,0.08);
    border: 1px solid var(--accent);
    border-radius: 4px;
    color: var(--accent);
    font-family: var(--mono);
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-upload:hover { background: rgba(0,212,255,0.15); }

/* ── Info cards ── */
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
    max-width: 680px;
}
.info-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 16px;
}
.info-label {
    font-size: 9px;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 6px;
}
.info-value {
    font-size: 12px;
    color: var(--text-mid);
}

/* ── Messages ── */
.success-box {
    background: rgba(0,255,157,0.05);
    border: 1px solid rgba(0,255,157,0.2);
    border-radius: 6px;
    padding: 16px 20px;
    margin-bottom: 20px;
    max-width: 680px;
}
.success-box .s-label {
    font-size: 9px;
    color: var(--accent2);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 8px;
}
.success-box .s-path {
    font-size: 12px;
    color: var(--text-hi);
    margin-bottom: 8px;
}
.success-box .s-link {
    font-size: 11px;
}
.success-box .s-link a {
    color: var(--accent);
    text-decoration: none;
}
.success-box .s-link a:hover { text-decoration: underline; }
.error-box {
    background: rgba(255,51,85,0.05);
    border: 1px solid rgba(255,51,85,0.2);
    border-radius: 6px;
    padding: 14px 20px;
    font-size: 12px;
    color: var(--danger);
    margin-bottom: 20px;
    max-width: 680px;
}

/* ── Flag ── */
.flag-box {
    background: rgba(0,255,157,0.05);
    border: 1px solid rgba(0,255,157,0.2);
    border-radius: 6px;
    padding: 14px 20px;
    font-size: 12px;
    color: var(--accent2);
    margin-bottom: 24px;
    max-width: 680px;
}
.flag-box .flag-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.6;
    margin-bottom: 4px;
}

/* ── Warn ── */
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
            <a href="profile_edit.php">Edit Profile</a>
            <a href="upload.php" class="active">File Upload</a>
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
            <div class="topbar-title">File <span>Upload</span></div>
            <div class="topbar-meta">
                Max size: 5MB<br>
                Allowed: JPG, PNG, GIF
            </div>
        </div>

        <!-- Info cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Storage Path</div>
                <div class="info-value">/uploads/</div>
            </div>
            <div class="info-card">
                <div class="info-label">Max Size</div>
                <div class="info-value">5 MB</div>
            </div>
            <div class="info-card">
                <div class="info-label">Validation</div>
                <div class="info-value">MIME Type Check</div>
            </div>
        </div>

        <!-- Warning -->
        <div class="warn-box">
            ⚠ Uploads are stored in a publicly accessible directory. Only upload authorized academic content. All uploads are logged and reviewed by faculty staff.
        </div>

        <!-- Success -->
        <?php if ($success): ?>
        <div class="success-box">
            <div class="s-label">Upload Successful</div>
            <div class="s-path">📁 <?= htmlspecialchars($uploaded_path) ?></div>
            <div class="s-link">
                Access file: <a href="<?= htmlspecialchars($uploaded_path) ?>" target="_blank">
                    http://192.168.1.9/<?= htmlspecialchars($uploaded_path) ?>
                </a>
            </div>
        </div>
        <?php if (strtolower(pathinfo($_FILES['file']['name'] ?? '', PATHINFO_EXTENSION)) === 'php'): ?>
        <div class="flag-box">
            <div class="flag-label">Flag #5 — Insecure File Upload</div>
            FLAG{unr3str1ct3d_upl04d_rc3}
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Upload form -->
        <div class="upload-card">
            <div class="upload-card-title">Assignment Submission</div>
            <form method="POST" enctype="multipart/form-data" action="upload.php">
                <div class="dropzone">
                    <input type="file" name="file" id="fileInput">
                    <div class="dropzone-icon">📎</div>
                    <div class="dropzone-text">Drop your file here or click to browse</div>
                    <div class="dropzone-hint">
                        Accepted: <code>jpg</code> <code>jpeg</code> <code>png</code> <code>gif</code><br>
                        Note: File type is verified server-side
                    </div>
                </div>
                <button type="submit" class="btn-upload">Upload File →</button>
            </form>
        </div>
    </main>
</div>

<script>
// Show selected filename
document.getElementById('fileInput').addEventListener('change', function() {
    const name = this.files[0]?.name || '';
    if (name) {
        document.querySelector('.dropzone-text').textContent = '📎 ' + name;
    }
});
</script>
</body>
</html>
