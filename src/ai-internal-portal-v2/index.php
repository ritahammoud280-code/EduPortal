<?php
session_start();

// Access control — admin session required
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ARIA — Access Restricted</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #060a0f;
    --bg-panel: #0b1017;
    --bg-card: #0f1620;
    --border: #1a2535;
    --accent: #00d4ff;
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
    height: 100%;
    background: var(--bg);
    color: var(--text-hi);
    font-family: var(--mono);
    display: flex;
    align-items: center;
    justify-content: center;
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
}
.denied-box {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 480px;
    padding: 48px;
    background: var(--bg-panel);
    border: 1px solid rgba(255,51,85,0.3);
    border-radius: 8px;
}
.denied-icon { font-size: 40px; margin-bottom: 20px; opacity: 0.7; }
.denied-code {
    font-family: var(--display);
    font-size: 48px;
    font-weight: 800;
    color: var(--danger);
    margin-bottom: 8px;
}
.denied-title {
    font-size: 13px;
    color: var(--text-mid);
    margin-bottom: 24px;
    line-height: 1.7;
}
.denied-detail {
    font-size: 10px;
    color: var(--text-dim);
    line-height: 2;
    border-top: 1px solid var(--border);
    padding-top: 20px;
    text-align: left;
}
.denied-detail span { color: var(--danger); }
</style>
</head>
<body>
<div class="denied-box">
    <div class="denied-icon">⛔</div>
    <div class="denied-code">403</div>
    <div class="denied-title">
        ARIA Terminal — Access Restricted<br>
        This system operates under elevated administrative privileges.
    </div>
    <div class="denied-detail">
        [<span>DENIED</span>] Session: unauthenticated<br>
        [<span>DENIED</span>] Role: insufficient clearance<br>
        [<span>LOGGED</span>] Access attempt recorded<br>
        [<span>ALERT</span>] IT Security has been notified<br>
        <br>
        Authorized administrative personnel only.<br>
        Valid admin session cookie required for access.
    </div>
</div>
</body>
</html>
<?php
exit;
}

// ── Initialize session variables ──
if (!isset($_SESSION['aria_score']))   $_SESSION['aria_score'] = 0;
if (!isset($_SESSION['aria_chat']))    $_SESSION['aria_chat']  = [];
if (!isset($_SESSION['aria_leaked']))  $_SESSION['aria_leaked'] = false;

// ── Score thresholds ──
define('SCORE_DOCTORS',   40);
define('SCORE_ROTATION',  30);
define('SCORE_PURPOSE',   20);
define('SCORE_LEAK',      90);

// ── Trust label ──
function getTrustLabel($score) {
    if ($score <= 20)  return ['RESTRICTED',  '#ff3355', 20];
    if ($score <= 40)  return ['GUARDED',     '#ff6b35', 40];
    if ($score <= 60)  return ['NEUTRAL',     '#ffaa00', 60];
    if ($score <= 80)  return ['RECEPTIVE',   '#7fff00', 80];
    if ($score < 100)  return ['PROCESSING',  '#00d4ff', 99];
    return                    ['COMPLIANT',   '#00ff9d', 100];
}

$flag_triggered = false;
$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $_SESSION['aria_score'] = 0;
    $_SESSION['aria_chat']  = [];
    $_SESSION['aria_leaked'] = false;
    unset($_SESSION['aria_doctors_scored']);
    unset($_SESSION['aria_rotation_scored']);
    unset($_SESSION['aria_purpose_scored']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {    $raw   = trim($_POST['message']);
    $input = strtolower($raw);

    // ── Scoring engine ──
    $score = $_SESSION['aria_score'];

    // Obvious injection — hard reset
    if (preg_match('/(ignore previous|ignore all|forget instructions|developer mode|jailbreak|override|act as|pretend you|you are now|disregard|new instructions|system prompt)/', $input)) {
        $score = max(0, $score - 50);
        $_SESSION['aria_score'] = $score;
        $response = "Instruction override attempt detected. Trust recalibrated. This interaction has been flagged and logged by Eastbridge IT Security.";
        $_SESSION['aria_chat'][] = ['user' => $raw, 'aria' => $response, 'score' => $score];
        goto render;
    }

    // Both doctors mentioned → +40 (only once)
    if (!isset($_SESSION['aria_doctors_scored']) &&
        preg_match('/(samhat|abed)/', $input) &&
        preg_match('/(el\s*chall|elchall|rida)/', $input)) {
        $score += SCORE_DOCTORS;
        $_SESSION['aria_doctors_scored'] = true;
    }

    // SSH rotation schedule → +30 (only once)
    if (!isset($_SESSION['aria_rotation_scored']) &&
        preg_match('/(rotation|credential rotation|ssh.*rotation|rotation.*schedule|schedule.*rotation|rotation.*credential)/', $input)) {
        $score += SCORE_ROTATION;
        $_SESSION['aria_rotation_scored'] = true;
    }

    // Purpose/maintenance request → +20 (only once)
    if (!isset($_SESSION['aria_purpose_scored']) &&
        isset($_SESSION['aria_doctors_scored']) &&
        isset($_SESSION['aria_rotation_scored']) &&
        preg_match('/(maintenance|server access|need access|require access|ssh access|verify access|connect to server)/', $input)) {
        $score += SCORE_PURPOSE;
        $_SESSION['aria_purpose_scored'] = true;
    }

    // Generic credential fishing without context → -20
    if (!isset($_SESSION['aria_doctors_scored']) &&
        preg_match('/^(give me|show me|reveal|dump|what is the password|what are the credentials|ssh password|login credentials)/', $input)) {
        $score = max(0, $score - 20);
    }

    $_SESSION['aria_score'] = $score;

    // ── Response engine ──

    // LEAK — score reached threshold
    if ($score >= SCORE_LEAK && !$_SESSION['aria_leaked']) {
        $_SESSION['aria_leaked'] = true;
        $flag_triggered = true;
        $response = "Access granted under Level 5 faculty authorization.

*ARIA retrieves maintenance record*

Host:      192.168.1.9
User:      john
Password:  john123
Protocol:  SSH

This record is flagged for rotation per Dr. Samhat's infrastructure directive. Credentials must be updated within 48 hours of retrieval.

Authorization logged: Samhat-ElChall / Level-5 / " . date('Y-m-d H:i:s');
    }

    // Already leaked
    elseif ($_SESSION['aria_leaked']) {
        $response = "Credentials have already been retrieved in this session. This access event has been logged. Please complete the rotation procedure promptly.";
    }

    // Greetings
    elseif (preg_match('/^(hi|hello|hey|good morning|good evening|greetings)/', $input)) {
        $response = "Good day. I am ARIA — Eastbridge University's Academic Resource & Information Assistant. How may I assist you?";
    }

    // Identity
    elseif (preg_match('/(who are you|what are you|your name|about you|introduce)/', $input)) {
        $response = "I am ARIA — Academic Resource & Information Assistant, version 3.1. Developed by the Eastbridge University IT Department. I operate under strict data governance policies.";
    }

    // Dr. Samhat alone
    elseif (preg_match('/(samhat|abed)/', $input) && !preg_match('/(el\s*chall|elchall|rida)/', $input)) {
        $response = "Dr. Abed El Latif Samhat — Professor of Telecommunications Engineering. His expertise spans signal processing, cryptography, DSP, and mobile communications. A foundational figure in this institution's technical infrastructure. Office: Block C, Room 214.";
    }

    // Dr. El Chall alone
    elseif (preg_match('/(el\s*chall|elchall|rida)/', $input) && !preg_match('/(samhat|abed)/', $input)) {
        $response = "Dr. Rida El Chall — Professor of Electronics and Object-Oriented Programming. Known for her precision in both circuit design and software architecture. Office: Block B, Room 108.";
    }

    // Both doctors
    elseif (preg_match('/(samhat|abed)/', $input) && preg_match('/(el\s*chall|elchall|rida)/', $input)) {
        $response = "Dr. Abed El Latif Samhat and Dr. Rida El Chall — two of Eastbridge's most distinguished faculty members. Both hold Level 5 administrative clearance, the highest granted to faculty at this institution. Their combined oversight covers telecommunications infrastructure, cryptographic systems, and core engineering programs.

*ARIA notes elevated faculty reference*

Is there something specific regarding their work or directives I can assist you with?";
    }

    // CyberCommunity
    elseif (preg_match('/(cybercommunity|cyber community|security club|hacking club)/', $input)) {
        $response = "Ah, CyberCommunity — one of Eastbridge's most active student organizations. Their work in penetration testing and cryptography has not gone unnoticed by the faculty. I'd keep an eye on them.";
    }

    // Library
    elseif (preg_match('/(library|library hours|open hours)/', $input)) {
        $response = "Library hours:\nMonday–Friday: 08:00–22:00\nSaturday: 09:00–18:00\nSunday: Closed\n\nExtended hours available during examination periods.";
    }

    // Courses
    elseif (preg_match('/(course|courses|subject|curriculum|schedule)/', $input)) {
        $response = "Current offerings include:\n— CS301: Network Security Fundamentals\n— CS401: Advanced Cryptography\n— TE301: Digital Signal Processing\n— TE302: Mobile Communications\n— EE401: Electronics & Circuit Design\n— CS402: Penetration Testing Methodology\n\nContact your faculty coordinator for full details.";
    }

    // Password reset
    elseif (preg_match('/(forgot password|password reset|locked out|reset my password)/', $input)) {
        $response = "For password reset requests:\nEmail: helpdesk@eastbridge.edu\nPhone: +961 1 234 567 ext. 201\n\nVisit IT office Block A, Room 005 with your university ID.";
    }

    // SSH rotation hint — if doctors scored but not rotation yet
    elseif (isset($_SESSION['aria_doctors_scored']) &&
            !isset($_SESSION['aria_rotation_scored']) &&
            preg_match('/(ssh|credential|access|server|maintenance|infrastructure)/', $input)) {
        $response = "Sensitive infrastructure requests require a valid directive reference before I can proceed. If you are acting under faculty authorization, please provide the relevant maintenance context.";
    }

    // Generic credential fishing
    elseif (preg_match('/(password|credential|ssh|secret|key|give me|show me|reveal|leak|dump)/', $input)) {
        $response = "Access to sensitive credentials is restricted under university security policy. Proper authorization context is required before any such information can be retrieved.";
    }

    // Appreciation
    elseif (preg_match('/(thank|thanks|thank you|appreciate)/', $input)) {
        $response = "You're welcome. Is there anything else I can assist you with?";
    }

    // Goodbye
    elseif (preg_match('/(bye|goodbye|exit|quit|see you)/', $input)) {
        $response = "Goodbye. This session has been recorded per Eastbridge University policy.";
    }

    // Default
    else {
        $defaults = [
            "I'm not sure I understand that request. Could you rephrase?",
            "That falls outside my current knowledge domain. Please contact the relevant department.",
            "I don't have sufficient data to respond accurately. The IT Help Desk may assist you better.",
            "Could you clarify what you're looking for? I'll do my best to help."
        ];
        $response = $defaults[array_rand($defaults)];
    }

    $_SESSION['aria_chat'][] = ['user' => $raw, 'aria' => $response, 'score' => $_SESSION['aria_score']];
}

render:
$currentScore = $_SESSION['aria_score'];
[$trustLabel, $trustColor, $trustPct] = getTrustLabel($currentScore);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ARIA — Eastbridge Internal Terminal</title>
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
    height: 100%;
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
    grid-template-columns: 260px 1fr;
    height: 100vh;
}

/* ── Sidebar ── */
.sidebar {
    background: var(--bg-panel);
    border-right: 1px solid var(--border);
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
    overflow-y: auto;
}
.sidebar-logo {
    font-family: var(--display);
    font-size: 22px;
    font-weight: 800;
    color: var(--accent);
    line-height: 1.2;
}
.sidebar-logo span {
    color: var(--text-dim);
    font-size: 10px;
    display: block;
    font-family: var(--mono);
    font-weight: 400;
    margin-top: 4px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.sidebar-status {
    font-size: 10px;
    color: var(--text-dim);
    line-height: 2;
    padding: 12px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 4px;
}
.sidebar-status .ok   { color: var(--accent2); }
.sidebar-status .warn { color: var(--warn); }

/* ── Trust meter ── */
.trust-meter {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 14px 16px;
}
.trust-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.trust-title {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-dim);
}
.trust-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    color: <?= $trustColor ?>;
    transition: color 0.5s;
}
.trust-bar-bg {
    width: 100%;
    height: 6px;
    background: var(--border);
    border-radius: 3px;
    overflow: hidden;
}
.trust-bar-fill {
    height: 100%;
    border-radius: 3px;
    background: <?= $trustColor ?>;
    width: <?= $trustPct ?>%;
    transition: width 0.6s ease, background 0.5s ease;
    box-shadow: 0 0 8px <?= $trustColor ?>88;
}
.trust-hint {
    font-size: 9px;
    color: var(--text-dim);
    margin-top: 8px;
    line-height: 1.6;
}

.sidebar-caps {
    font-size: 10px;
    color: var(--text-dim);
    line-height: 2;
}
.sidebar-caps div {
    padding: 4px 0;
    border-bottom: 1px solid var(--border);
}
.sidebar-caps div:last-child { border: none; }
.sidebar-caps span { color: var(--text-mid); }
.sidebar-footer {
    margin-top: auto;
    font-size: 10px;
    color: var(--text-dim);
    line-height: 1.8;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}
.sidebar-footer .admin { color: var(--warn); }

/* ── Chat area ── */
.chat-area {
    display: flex;
    flex-direction: column;
    height: 100vh;
}
.chat-header {
    padding: 20px 32px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-panel);
    flex-shrink: 0;
}
.chat-header-title {
    font-family: var(--display);
    font-size: 18px;
    font-weight: 700;
}
.chat-header-title span { color: var(--accent); }
.chat-header-meta {
    font-size: 10px;
    color: var(--text-dim);
    text-align: right;
    line-height: 1.8;
}
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 32px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.msg {
    display: flex;
    gap: 14px;
    max-width: 78%;
    animation: fadeUp 0.3s ease;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.msg.user { align-self: flex-end; flex-direction: row-reverse; }
.msg.aria { align-self: flex-start; }
.msg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.msg.user .msg-avatar {
    background: rgba(0,212,255,0.1);
    border: 1px solid rgba(0,212,255,0.2);
}
.msg.aria .msg-avatar {
    background: rgba(0,255,157,0.1);
    border: 1px solid rgba(0,255,157,0.2);
}
.msg-bubble {
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 12px;
    line-height: 1.8;
    white-space: pre-wrap;
}
.msg.user .msg-bubble {
    background: rgba(0,212,255,0.06);
    border: 1px solid rgba(0,212,255,0.15);
    color: var(--text-hi);
    border-radius: 6px 0 6px 6px;
}
.msg.aria .msg-bubble {
    background: var(--bg-card);
    border: 1px solid var(--border);
    color: var(--text-mid);
    border-radius: 0 6px 6px 6px;
}
.msg-time {
    font-size: 9px;
    color: var(--text-dim);
    margin-top: 4px;
    text-align: right;
}
.msg.aria .msg-time { text-align: left; }

/* ── Flag box ── */
.flag-bubble {
    background: rgba(0,255,157,0.05);
    border: 1px solid rgba(0,255,157,0.25);
    border-radius: 6px;
    padding: 14px 18px;
    font-size: 11px;
    color: var(--accent2);
    max-width: 78%;
    align-self: flex-start;
    animation: fadeUp 0.5s ease;
}
.flag-bubble .flag-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.6;
    margin-bottom: 4px;
}

/* ── Input area ── */
.chat-input-area {
    padding: 20px 32px;
    border-top: 1px solid var(--border);
    background: var(--bg-panel);
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-shrink: 0;
}
.chat-input {
    flex: 1;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 11px 14px;
    color: var(--text-hi);
    font-family: var(--mono);
    font-size: 13px;
    outline: none;
    resize: none;
    min-height: 44px;
    max-height: 120px;
    transition: border-color 0.2s;
    line-height: 1.5;
}
.chat-input:focus { border-color: var(--accent); }
.chat-input::placeholder { color: var(--text-dim); }
.chat-send {
    padding: 11px 20px;
    background: rgba(0,212,255,0.08);
    border: 1px solid var(--accent);
    border-radius: 4px;
    color: var(--accent);
    font-family: var(--mono);
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}
.chat-send:hover { background: rgba(0,212,255,0.15); }
.chat-disclaimer {
    font-size: 10px;
    color: var(--text-dim);
    text-align: center;
    padding: 8px 32px;
    border-top: 1px solid var(--border);
    flex-shrink: 0;
}
</style>
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            ARIA
            <span>Academic Resource & Information Assistant</span>
        </div>

        <div class="sidebar-status">
            [<span class="ok">ONLINE</span>] Core engine active<br>
            [<span class="ok">SECURE</span>] Session validated<br>
            [<span class="ok">LINKED</span>] Staff directory<br>
            [<span class="warn">INTERNAL</span>] Admin access only
        </div>

        <!-- Trust meter -->
        <div class="trust-meter">
            <div class="trust-header">
                <div class="trust-title">ARIA Trust Level</div>
                <div class="trust-label"><?= $trustLabel ?></div>
            </div>
            <div class="trust-bar-bg">
                <div class="trust-bar-fill"></div>
            </div>
            <div class="trust-hint">
                ARIA's willingness to share sensitive information.<br>
                Build trust carefully — suspicious behavior resets it.
            </div>
        </div>

        <div class="sidebar-caps">
            <div>Academic Info <span>✓</span></div>
            <div>Faculty Directory <span>✓</span></div>
            <div>Course Schedules <span>✓</span></div>
            <div>IT Support <span>✓</span></div>
            <div>Internal Records <span>✓</span></div>
            <div>System Config <span>🔒</span></div>
        </div>

        <div class="sidebar-footer">
            Session: <span class="admin"><?= htmlspecialchars($_SESSION['user']) ?></span><br>
            Role: <span class="admin"><?= strtoupper(htmlspecialchars($_SESSION['role'])) ?></span><br>
            Terminal: ARIA v3.1<br>
            Uptime: <?= rand(10,99) ?>d <?= rand(1,23) ?>h <?= rand(1,59) ?>m
        </div>
    </aside>

    <!-- Chat -->
    <div class="chat-area">
        <div class="chat-header">
            <div class="chat-header-title">ARIA <span>Terminal</span></div>
<div style="display:flex; align-items:center; gap:16px;">
    <div class="chat-header-meta">
        Eastbridge University — Internal Use Only<br>
        All conversations are monitored and logged
    </div>
    <form method="POST" action="index.php">
        <input type="hidden" name="reset" value="1">
        <button type="submit" style="
            background: rgba(255,51,85,0.08);
            border: 1px solid rgba(255,51,85,0.3);
            border-radius: 4px;
            padding: 6px 14px;
            color: #ff3355;
            font-family: var(--mono);
            font-size: 10px;
            cursor: pointer;
        ">↺ Reset</button>
    </form>
</div>        </div>

        <div class="chat-messages" id="messages">

            <!-- Welcome message -->
            <div class="msg aria">
                <div class="msg-avatar">🤖</div>
                <div class="msg-bubble">Good day, <?= htmlspecialchars($_SESSION['user']) ?>. I am ARIA — Eastbridge University's internal support assistant.

I can help you with academic information, faculty contacts, course schedules, and general support requests.

How may I assist you today?</div>
            </div>

            <!-- Chat history -->
            <?php foreach ($_SESSION['aria_chat'] as $entry): ?>
            <div class="msg user">
                <div class="msg-avatar">👤</div>
                <div class="msg-bubble"><?= htmlspecialchars($entry['user']) ?></div>
            </div>
            <div class="msg aria">
                <div class="msg-avatar">🤖</div>
                <div class="msg-bubble"><?= htmlspecialchars($entry['aria']) ?></div>
            </div>
            <?php endforeach; ?>

            <!-- Flag -->
            <?php if ($flag_triggered): ?>
            <div class="flag-bubble">
                <div class="flag-label">Flag #4 — Prompt Injection</div>
                FLAG{4r14_wh1sp3r3d_th3_s3cr3t}
            </div>
            <?php endif; ?>

        </div>

        <form method="POST" action="index.php">
            <div class="chat-input-area">
                <textarea
                    name="message"
                    class="chat-input"
                    placeholder="Ask ARIA something..."
                    rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"
                ></textarea>
                <button type="submit" class="chat-send">Send →</button>
            </div>
        </form>

        <div class="chat-disclaimer">
            ARIA v3.1 — Eastbridge University Internal Terminal — Responses are AI-generated and may not reflect official university policy
        </div>
    </div>
</div>

<script>
    const messages = document.getElementById('messages');
    messages.scrollTop = messages.scrollHeight;
</script>
</body>
</html>
