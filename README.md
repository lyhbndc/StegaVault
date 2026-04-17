# StegaVault — Secure File Management System

**Built for PGMN Inc. (Peanut Gallery Media Network)**

StegaVault is a secure, web-based file management platform that protects sensitive media and documents using AES-256 encryption and invisible digital watermarking. Every file stored in the system is encrypted, and every file downloaded is invisibly signed with the identity of the downloader — making leaks fully traceable.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Directory Structure](#directory-structure)
- [User Roles](#user-roles)
- [Authentication Flow](#authentication-flow)
- [Core Features](#core-features)
- [Database Schema](#database-schema)
- [Configuration](#configuration)
- [Setup & Installation](#setup--installation)
- [Security Notes](#security-notes)

---

## Overview

When a user uploads a file, StegaVault encrypts it with AES-256 before writing it to disk. When a user downloads that file, the system decrypts it, embeds an invisible cryptographic watermark via LSB steganography, and streams the signed copy to the user — leaving a forensic trail. Admins can upload any suspect file to the Forensic Analysis page to recover the embedded identity data.

For a plain-English walkthrough of every page, see **`guide.php`** in the project root.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+ |
| Database | MySQL 8.0+ / MariaDB (MySQLi) |
| Frontend | Tailwind CSS (CDN), Material Symbols, Inter font |
| Cryptography | OpenSSL (AES-256-CBC, HMAC-SHA256, PBKDF2) |
| PDF Security | TCPDF 6.7 + FPDI 2.6 |
| Email | PHPMailer 7.0 (SMTP) |
| MFA | PHPGangsta GoogleAuthenticator (TOTP) |
| Dependencies | Composer |

---

## Directory Structure

```
StegaVault/
├── admin/                  # Admin portal pages
│   ├── login.php           # Admin login
│   ├── dashboard.php       # Admin overview
│   ├── users.php           # User management
│   ├── projects.php        # Project management
│   ├── analysis.php        # Forensic analysis (watermark extraction)
│   ├── activity.php        # Full activity logs
│   ├── reports.php         # Usage reports
│   └── mfa-settings.php    # Admin MFA setup
│
├── employee/               # Employee portal pages
│   ├── dashboard.php
│   ├── workspace.php       # File browser + upload
│   ├── activity.php        # Personal activity log
│   └── profile.php         # Password/settings
│
├── collaborator/           # Collaborator portal (same structure as employee)
│
├── api/                    # Backend endpoints
│   ├── auth.php            # Login / logout
│   ├── upload.php          # File upload + encryption pipeline
│   ├── download.php        # Decrypt + watermark + serve file
│   ├── view.php            # Decrypt + serve for preview (no watermark)
│   ├── projects.php        # Project/folder/file CRUD
│   ├── users.php           # User CRUD + activation
│   ├── mfa.php             # MFA verify / setup / recovery
│   ├── settings.php        # Password change, theme
│   ├── search.php          # Global search
│   └── backup_cron.php     # CLI-only backup script
│
├── includes/               # Shared PHP classes
│   ├── db.php              # MySQLi database connection
│   ├── Encryption.php      # AES-256-CBC file encryption
│   ├── watermark.php       # LSB steganography embed/extract
│   ├── CryptoWatermark.php # HMAC-SHA256 cryptographic watermark
│   ├── ActivityLogger.php  # Role-separated activity log writer
│   ├── EmailService.php    # PHPMailer SMTP wrapper
│   ├── PdfSecurity.php     # PDF password protection
│   └── settings_modal.php  # Shared settings UI component
│
├── uploads/                # All encrypted file storage
│   ├── encrypted/          # AES-256 encrypted files (permanent)
│   ├── raw/                # Temporary raw staging (deleted after encrypt)
│   ├── watermarked/        # Temporary watermarked downloads (auto-cleanup)
│   └── backups/            # Database backup archives
│
├── migrations/             # Database schema migration scripts
├── vendor/                 # Composer dependencies
├── js/                     # Frontend JavaScript
├── index.html              # Landing page (role selector)
├── activate.php            # Account activation via email token
├── mfa-verify.php          # MFA second-factor step
├── reset-password.php      # Password reset via email link
└── guide.php               # Plain-English system guide for non-developers
```

---

## User Roles

| Role | Access |
|---|---|
| `super_admin` | System-level: backups, audit logs, manage admin accounts |
| `admin` | Full control: users, projects, files, forensic analysis, all logs |
| `employee` | Own uploads + assigned project files, activity log |
| `collaborator` | Assigned project files only, limited feature set |

Each role has its own login page and portal. Sessions are role-scoped.

---

## Authentication Flow

1. **Login** — Email + bcrypt password via `POST /api/auth.php?action=login`
2. **MFA** — If enabled, a TOTP code (or recovery code) is required. A `pending_mfa_user_id` session gate blocks access until verified via `/mfa-verify.php`
3. **Account Activation** — New users receive an activation email with credentials and a token link. Status must be `active` before login is allowed.
4. **Password Reset** — 64-char hex token emailed, expires after 1 hour
5. **MFA Recovery Codes** — 10 single-use codes in `XXXXXXXX-XXXXXXXX` format, stored in `mfa_recovery_codes` table

---

## Core Features

### File Encryption

**Class:** `includes/Encryption.php` | **Algorithm:** AES-256-CBC

Stored file format:
```
[5-byte header: "SVENC"] + [16-byte IV] + [AES-256-CBC encrypted content]
```

- `encryptFile($src, $dest)` — encrypts a file from source to destination
- `decryptFileContent($path)` — returns raw decrypted bytes in memory
- Files without the `SVENC` header are treated as unencrypted (backward compatibility)

---

### File Upload Pipeline

**Endpoint:** `POST /api/upload.php`

```
Auth check
  → Validate MIME type and file size (max 50 MB)
  → Duplicate filename check within project scope
  → Save raw staging copy to uploads/raw/
  → Optional: apply PDF password protection (TCPDF/FPDI)
  → Encrypt with AES-256 → write to uploads/encrypted/
  → Delete raw staging copy
  → INSERT into files table (with project_id / folder_id)
  → Return file metadata
```

**Supported types:** PNG, JPEG, WebP, MP4, MOV, AVI, MPEG, PDF, DOC, DOCX, XLS, XLSX, TXT

---

### Download & Watermark Embedding

**Endpoint:** `GET /api/download.php?file_id=X`

```
Auth + ownership / project-membership check
  → Decrypt file from uploads/encrypted/
  → Build watermark payload (user id/name/role, file id, IP, timestamp)
  → Generate cryptographic watermark (HMAC-SHA256 + AES-256)
  → Embed watermark invisibly into image pixels (LSB steganography)
  → Log to activity log (file_downloaded)
  → Stream watermarked file to browser
```

Non-image files (PDF, video, documents) are streamed without LSB embedding.

---

### LSB Steganography Watermarking

**Class:** `includes/watermark.php`

The least significant bit of each RGB colour channel is overwritten with watermark data bits. Changes are invisible to the human eye but recoverable by the system.

**Embedded payload:**
```
[length]|{"u_id":5,"u_name":"John","u_role":"employee","f_id":12,"ip":"x.x.x.x","ts":1700000000,...}|[md5_checksum]
```

---

### Cryptographic Watermarking

**Class:** `includes/CryptoWatermark.php`

An additional cryptographic layer embedded alongside the LSB data:

- Per-user encryption key derived with **PBKDF2** (10,000 iterations) from user ID + email
- Payload encrypted with **AES-256-CBC**
- Signed with **HMAC-SHA256** for tamper detection
- Verification steps: extract → derive key → decrypt → verify HMAC → check hash

---

### Forensic Analysis

**Admin UI:** `/admin/analysis.php`

An admin uploads a suspected leaked file. The system:
1. Extracts LSB data from image pixels
2. Parses and decrypts the watermark JSON
3. Verifies the HMAC-SHA256 cryptographic signature
4. Compares the embedded content hash to detect post-download tampering
5. Displays the File Origin card (who downloaded, IP, timestamp, user ID)
6. Generates a downloadable PDF forensic report

---

### Activity Logging

**Class:** `includes/ActivityLogger.php`

Logs are written to role-separated tables:

| Table | Used for |
|---|---|
| `activity_log_admin` | All admin actions |
| `activity_log_employee` | All employee actions |
| `activity_log_collaborator` | All collaborator actions |

**Dual logging:** When an admin views or renames a file owned by another user, a notification entry is also written to that user's activity table so they can see who accessed their file.

**Logged events:** `login_success`, `login_failed`, `file_uploaded`, `file_downloaded`, `file_viewed`, `file_renamed`, `file_deleted`, `user_created`, `user_updated`, `user_deleted`, `user_activation_resent`, `account_activated`, `password_reset`, `mfa_enabled`

---

### Project Collaboration

- Admin creates projects and assigns members
- Projects support a hierarchical folder structure (`project_folders` table with `parent_id`)
- Members see assigned projects in their workspace
- All project file operations are logged

---

### Backup System

**CLI Script:** `api/backup_cron.php` (blocks web access via `php_sapi_name()` check)

Typical cron setup:
```bash
0 0,12 * * * php /full/path/to/api/backup_cron.php >> /full/path/to/logs/backup_cron.log 2>&1
```

Super admins can list, download, restore, and delete backups via the Super Admin portal.

---

## Database Schema

| Table | Purpose |
|---|---|
| `users` | Accounts, roles, MFA secrets, activation tokens, status |
| `files` | File records, encrypted path, download count, watermark flag |
| `projects` | Project containers (active / archived / completed) |
| `project_members` | User ↔ project membership with roles |
| `project_folders` | Hierarchical folder structure within projects |
| `watermark_mappings` | Watermark ↔ file linkage, signature reference |
| `watermark_crypto_log` | Forensic log: user, file, signature, IP, verified_at |
| `mfa_recovery_codes` | Backup codes for MFA recovery (one-time use) |
| `activity_log_admin` | Admin activity history |
| `activity_log_employee` | Employee activity history |
| `activity_log_collaborator` | Collaborator activity history |
| `super_admin_audit_log` | Super admin action audit trail |

---

## Configuration

**`includes/config.php`** — database credentials, site URL, session timeout, timezone

**`includes/email_config.php`** — SMTP host, port, username, password, from address

Key defaults:
- Max file size: 50 MB (enforced in `upload.php`)
- Session idle timeout: 900 seconds (15 minutes)
- Timezone: Asia/Manila

---

## Setup & Installation

1. Place the `StegaVault/` folder inside your web server root (`htdocs/` for XAMPP)
2. Run `composer install` inside the `StegaVault/` directory
3. Import the database and run all scripts in `migrations/` in order
4. Update `includes/config.php` with your database credentials
5. Update `includes/email_config.php` with your SMTP credentials
6. Ensure these directories are writable by the web server:
   ```
   uploads/
   uploads/encrypted/
   uploads/raw/
   uploads/watermarked/
   uploads/backups/
   ```
7. Create your first admin account directly in the `users` table (status: `active`, role: `admin`, password: bcrypt hash)
8. Visit `http://localhost/StegaVault/` to access the system

---

## Security Notes

- The AES-256 encryption key in `Encryption.php` should be stored in an environment variable in production — never commit it to version control
- SMTP credentials in `email_config.php` should use an app-specific password
- `api/backup_cron.php` is CLI-only — do not remove the `php_sapi_name()` guard
- The `uploads/encrypted/` directory should not be publicly accessible — configure Apache/Nginx to deny direct access to the `uploads/` path

---

*© 2026 PGMN Inc. — StegaVault Security Suite v2.1*
