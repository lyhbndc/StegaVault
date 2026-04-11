# Security Shield Implementation

## Overview
While it is technically impossible to completely prevent OS-level screenshots on a standard web application, StegaVault implements a **Military-Grade Security Shield** to discourage and trace unauthorized capture.

## Features

### 1. Dynamic Forensic Watermarking
- **What**: An invisible/faint overlay covers the entire application screen.
- **Content**: Repeated grid containing: `[User Name] | [User Email] | [Date] | CONFIDENTIAL`.
- **Purpose**: If a screenshot is taken and leaked, the image acts as "Digital Evidence" tracing back to the specific user who captured it.

### 2. Privacy Blur (Anti-Snipping)
- **What**: The application instantly detects when the browser window loses focus.
- **Action**: A heavy blur filter and "Security Paused" lock screen covers all content.
- **Effectiveness**: Many screen capture tools (like Windows Snipping Tool) cause the window to lose focus, thus capturing only the blurred security screen.

### 3. Interaction Blocking
- **Right-Click Disabled**: Prevents context menu usage (Save Image As, Inspect Element).
- **Key Combo Blocking**: Intercepts `Ctrl+P` (Print), `Ctrl+S` (Save), `Ctrl+Shift+I` (DevTools).
- **Print Styles**: CSS `@media print { body { display: none; } }` ensures printed pages are blank.

## Files
- `js/security-shield.js`: Core logic module.
- `admin/dashboard.php`: Injected.
- `admin/users.php`: Injected.
- `admin/upload.php`: Injected.

## Verification
1. Open the Admin Dashboard.
2. Observe the faint diagonal text overlay.
3. Try to click outside the browser window -> Screen should blur.
4. Try to Right-Click -> Should be blocked.
