<?php
session_start();
$conn = new mysqli('localhost','webuser','webpass','eduportal');
$error = '';
$debug = '';

function security_filter($input) {
    $banned = ['/\s+/', '/--/'];
    foreach ($banned as $pattern) {
        if (preg_match($pattern, $input)) return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    if (security_filter($user) || security_filter($pass)) {
        $error = "Security Alert: Malicious pattern detected. Incident logged.";
        if (isset($_GET['debug'])) {
            $debug = "BLOCKED QUERY: SELECT * FROM users WHERE username='$user' AND password='$pass' AND role='student'";
        }
    } else {
        $query = "SELECT * FROM users WHERE username='$user' AND password='$pass' AND role='student'";
        if (isset($_GET['debug'])) $debug = htmlspecialchars($query);
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            
            if ($row['role'] === 'admin') {
                $_SESSION['user']    = 'john';
                $_SESSION['role']    = 'student';
                $_SESSION['user_id'] = 2;
                header('Location: dashboard.php?id=' . base64_encode(2). '&flag1=1');
                exit;
            }

            $_SESSION['user']    = $row['username'];
            $_SESSION['role']    = $row['role'];
            $_SESSION['user_id'] = $row['id'];
            $encoded_id = base64_encode($row['id']);
            header('Location: dashboard.php?id=' . $encoded_id . '&flag1=1');
            exit;
        } else {
            if (isset($_POST['username']) && $_POST['username'] === 'admin') {
                $error = "Administrative access restricted. Contact your system administrator.";
            } else {
                $error = 'Invalid credentials.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduPortal — Secure Access</title>
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
    --text-hi: #e8f4ff;
    --text-mid: #7a9bbf;
    --text-dim: #3d5472;
    --mono: 'JetBrains Mono', monospace;
    --display: 'Syne', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; background: var(--bg); color: var(--text-hi); font-family: var(--mono); overflow: hidden; }
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
.page {
    position: relative;
    z-index: 2;
    height: 100vh;
    display: grid;
    grid-template-columns: 1fr 480px;
}
.left {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 48px;
    border-right: 1px solid var(--border);
    background: var(--bg-panel);
}
.hero-title {
    font-family: var(--display);
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 800;
    line-height: 1.05;
    margin-bottom: 20px;
}
.hero-title .hl { color: var(--accent); }
.subtitle { color: var(--text-mid); max-width: 320px; line-height: 1.6; font-size: 13px; }
.status-bar { font-size: 11px; color: var(--text-dim); line-height: 1.8; }
.status-bar span { color: var(--accent2); }
.right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
}
.form-wrap { width: 100%; max-width: 360px; }
.form-title {
    font-family: var(--display);
    font-size: 22px;
    color: var(--accent);
    margin-bottom: 8px;
}
.form-sub { font-size: 11px; color: var(--text-dim); margin-bottom: 28px; }
label { font-size: 10px; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 6px; }
input {
    width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 11px 14px;
    color: var(--text-hi);
    font-family: var(--mono);
    font-size: 13px;
    outline: none;
    margin-bottom: 18px;
    transition: border-color 0.2s;
}
input:focus { border-color: var(--accent); }
.btn-submit {
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
.btn-submit:hover { background: rgba(0,212,255,0.15); }
.error-msg {
    background: rgba(255,51,85,0.08);
    border: 1px solid rgba(255,51,85,0.3);
    border-radius: 4px;
    padding: 10px 14px;
    color: var(--danger);
    font-size: 11px;
    margin-bottom: 16px;
}
.debug-box {
    background: rgba(255,107,53,0.06);
    border: 1px solid rgba(255,107,53,0.3);
    border-radius: 4px;
    padding: 10px 14px;
    color: #ff6b35;
    font-size: 10px;
    margin-bottom: 16px;
    word-break: break-all;
}
.hint-strip {
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid var(--border);
    font-size: 11px;
    color: var(--text-dim);
    line-height: 1.9;
}
.hint-strip code {
    color: var(--accent);
    background: rgba(0,212,255,0.08);
    padding: 1px 5px;
    border-radius: 3px;
}
</style>
</head>
<body>
<div class="page">
    <div class="left">
        <div>
            <h1 class="hero-title">Student<br><span class="hl">Portal</span><br>Access</h1>
            <p class="subtitle">Eastbridge University Internal Gateway. All access attempts are monitored and logged by the security team.</p>
        </div>
        <div class="status-bar">
            [<span>OK</span>] DB_CONN: eduportal@localhost<br>
            [<span>OK</span>] WAF_CORE: Active — Strict pattern filtering enabled<br>
            [<span>OK</span>] SESSION_MGR: PHP/8.3 — Secure<br>
            [--] LAST_LOGIN: admin — today 09:42:11 UTC
        </div>
    </div>
    <div class="right">
        <div class="form-wrap">
            <div class="form-title">EduPortal v2.1</div>
            <div class="form-sub">Authorized personnel only. Violations are prosecuted.</div>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($debug): ?>
                <div class="debug-box"><strong>DEBUG:</strong> <?= $debug ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php<?= isset($_GET['debug']) ? '?debug=1' : '' ?>">
                <label>Student ID / Username</label>
                <input type="text" name="username" placeholder="Enter your ID" autocomplete="off"
                    value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••">
                <button type="submit" class="btn-submit">Authenticate →</button>
            </form>
            <div class="hint-strip">
                Tip: Append <code>?debug=1</code> to URL for query diagnostics<br>
                WAF Status: <code style="color:var(--accent2)">ACTIVE</code> — blocking space/comment patterns
            </div>
        </div>
    </div>
</div>
</body>
</html>
