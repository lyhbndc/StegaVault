# StegaVault

**StegaVault** is a secure digital asset management platform built for Peanut Gallery Media Network. It combines AES-256 encryption, LSB steganography watermarking, cryptographic audit trails, multi-factor authentication, and role-based access control into a single system designed for organizations that need forensic accountability over shared media files.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Directory Structure](#directory-structure)
- [User Roles](#user-roles)
- [Authentication Flow](#authentication-flow)
- [Core Features](#core-features)
  - [File Encryption](#file-encryption)
  - [Steganography Watermarking](#steganography-watermarking)
  - [Cryptographic Watermarking](#cryptographic-watermarking)
  - [File Upload Pipeline](#file-upload-pipeline)
  - [Download & Watermark Embedding](#download--watermark-embedding)
  - [Watermark Verification](#watermark-verification)
  - [Project Collaboration](#project-collaboration)
  - [Backup System](#backup-system)
  - [Audit Logs](#audit-logs)
- [Database Schema](#database-schema)
- [Configuration](#configuration)
- [Setup & Installation](#setup--installation)

---

## Overview

When a user uploads a file, StegaVault encrypts it with AES-256 before writing it to disk. When a user downloads that file, the system decrypts it, embeds an invisible cryptographic watermark (via LSB steganography), and streams the watermarked copy to the user — leaving a forensic trail that can later prove who downloaded the file, when, and from where. Admins can upload any suspect file to the extraction tool and recover the embedded identity data.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+ |
| Database | PostgreSQL (Supabase, via PDO) |
| Frontend | Tailwind CSS, Material Symbols |
| Cryptography | OpenSSL (AES-256-CBC, HMAC-SHA256, PBKDF2) |
| PDF Security | TCPDF 6.7 + FPDI 2.6 |
| Email | PHPMailer 7.0 (SMTP / Gmail) |
| MFA | PHPGangsta GoogleAuthenticator (TOTP) |
| Dependencies | Composer |

---

## Directory Structure

```
StegaVault/
├── admin/              # Admin portal — user mgmt, projects, reports, forensics
├── employee/           # Employee portal — files, workspace, activity
├── collaborator/       # Collaborator portal — limited project/file access
├── api/                # REST API endpoints (auth, upload, download, backup…)
├── includes/           # Core classes — Encryption, Watermark, CryptoWatermark, etc.
├── migrations/         # SQL migration files
├── uploads/            # AES-256 encrypted file storage
├── backups/            # Automated database backups
├── logs/               # Application and cron logs
├── vendor/             # Composer dependencies
├── css/ / js/          # Frontend assets
└── index.php           # Entry point → redirects to employee login
```

---

## User Roles

| Role | Access |
|---|---|
| **super_admin** | System-level: backups, audit logs, admin management |
| **admin** | Full: user management, all projects, forensic extraction, reports |
| **employee** | Own files + assigned projects, activity log |
| **collaborator** | Assigned projects only, restricted feature set |

Each role has a dedicated login page and portal. Sessions are role-scoped and expire after 15 minutes of inactivity.

---

## Authentication Flow

1. **Login** — Email + bcrypt password check via `POST /api/auth.php?action=login`
2. **MFA challenge** — If MFA is enabled, a TOTP code is required before a full session is created. A `pending_mfa_user_id` session variable gates access until the code is verified.
3. **Session** — On success, `user_id`, `email`, `name`, and `role` are stored in the session. Inactivity timeout: 900 seconds.
4. **Password Reset** — Token-based flow: generates a 64-char hex token, emails a reset link, and validates expiry on submission.
5. **MFA Recovery** — 10 single-use backup codes generated at MFA setup. Each code is one-time use and marked `used` after redemption.

---

## Core Features

### File Encryption

**Class:** `includes/Encryption.php`  
**Algorithm:** AES-256-CBC (OpenSSL)

Every file written to disk is encrypted. The stored format is:

```
[5-byte header: "SVENC"] + [16-byte IV] + [AES-256-CBC encrypted content]
```

- `encryptFile($src, $dest)` — encrypts a file from source to destination path
- `decryptFileContent($path)` — returns the raw decrypted bytes in memory
- `decryptToTemp($path)` — writes decrypted content to a temp file (used by PDF libraries)

Files without the `SVENC` header are treated as unencrypted and served as-is (backward compatibility).

---

### Steganography Watermarking

**Class:** `includes/watermark.php`  
**Method:** Least Significant Bit (LSB) steganography

For PNG, JPEG, GIF, and WebP images, watermark data is embedded invisibly by modifying the least significant bit of color channel values. The change is imperceptible to the human eye.

**Payload format embedded in the image:**

```
[length]|[watermark_json]|[md5_checksum]
```

- `embedWatermark($imagePath, $outputPath, $data)` — writes watermark into image pixels
- `extractWatermark($imagePath)` — reads LSBs to recover hidden data
- The image must be larger than 32×32 pixels to have sufficient capacity

---

### Cryptographic Watermarking

**Class:** `includes/CryptoWatermark.php`  
**Purpose:** Non-repudiation — cryptographic proof of who downloaded a file

Each watermark contains an encrypted payload signed with HMAC-SHA256. The encryption key is derived per-user using PBKDF2 (10,000 iterations) from their user ID and email.

**Watermark structure:**

```json
{
  "version": "2.0",
  "key_id": "SHA-256(user_id + email)",
  "nonce": "random 16-byte hex",
  "timestamp": 1700000000,
  "signature": "HMAC-SHA256 signature",
  "encrypted_payload": "base64(AES-256-CBC(json))",
  "public": { "user_id": 5, "file_id": 12, "timestamp": 1700000000 }
}
```

**Encrypted payload contains:**

| Field | Value |
|---|---|
| User | id, name, role, email hash |
| File | id, SHA-256 hash of original, MIME type |
| Metadata | timestamp, IP address, session ID, download count, nonce |

**Verification steps:**

1. Extract encrypted payload from the watermark
2. Derive user key with PBKDF2
3. Decrypt payload with AES-256
4. Verify HMAC signature
5. Compare file hash against stored original
6. Validate nonce and timestamp
7. Log result to `watermark_crypto_log`

---

### File Upload Pipeline

**Endpoint:** `POST /api/upload.php`

```
[Auth check]
    → [Validate file: MIME type, size ≤ 50MB]
    → [Duplicate check: same filename in same project/user scope]
    → [Save raw file to api/storage/ (staging)]
    → [Optional: apply PDF open-password via TCPDF/FPDI]
    → [Encrypt with AES-256 → write to uploads/]
    → [Clean up staging file]
    → [INSERT into files table]
    → [Associate with project_id / folder_id if provided]
    → [Log activity: file_uploaded]
    → [Return file metadata]
```

**Supported MIME types:** PNG, JPEG, WebP, MP4, MOV, AVI, MPEG, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT

---

### Download & Watermark Embedding

**Endpoint:** `GET /api/download.php?file_id=X`

```
[Auth + ownership/project-membership check]
    → [Fetch file record from DB]
    → [Decrypt file content from uploads/]
    → [If image: generate crypto watermark → embed via LSB]
    → [Log watermark to watermark_crypto_log]
    → [Increment download_count]
    → [Stream file to browser]
    → [Log activity: file_downloaded]
```

Non-image files (PDFs, videos, documents) are decrypted and streamed without LSB embedding. The cryptographic watermark log entry is still created for all downloads.

---

### Watermark Verification

**Admin UI:** `/admin/extract.php`  
**API:** `POST /api/verify_watermark.php`

An admin uploads a suspicious image. The system:

1. Extracts LSB data from the image
2. Parses the watermark JSON
3. Decrypts the payload
4. Verifies the HMAC signature
5. Checks the file hash for tampering
6. Generates a forensic report

**Report includes:** who downloaded, when, IP address, download count, signature validity, hash integrity status.

---

### Project Collaboration

Projects are containers for files shared among multiple users.

- **Admin** creates a project and assigns members with roles (owner, editor, viewer)
- **Members** see the project in their workspace (`/employee/workspace.php`, `/collaborator/workspace.php`)
- Projects support a **hierarchical folder structure** (`project_folders` table with `parent_id`)
- File uploads can be scoped to a specific `project_id` and `folder_id`
- All actions within a project are logged with project context

---

### Backup System

**CLI Script:** `/api/backup_cron.php` (web access blocked)  
**Trigger:** Cron job, twice daily (midnight + noon, Manila time)

```
0 0,12 * * * php /path/to/api/backup_cron.php >> logs/backup_cron.log 2>&1
```

- Dumps the full PostgreSQL database to `.sql.gz`
- Stores metadata in `backups/backups_meta.json`
- Enforces a retention policy (oldest backups deleted automatically)
- Super admins can list, download, restore, and delete backups via `/api/super_admin_backup.php`

---

### Audit Logs

Activity is logged to role-separated tables to prevent cross-role visibility:

| Table | Used by |
|---|---|
| `activity_log_admin` | Admins |
| `activity_log_employee` | Employees |
| `activity_log_collaborator` | Collaborators |
| `super_admin_audit_log` | Super admins |

**Logged events include:** login_success, login_failed, file_uploaded, file_downloaded, file_deleted, user_created, user_edited, user_disabled, project_created, mfa_enabled, password_changed, password_reset.

Each entry captures: `user_id`, `action`, `description`, `ip_address`, `created_at`.

The super admin audit log additionally captures: action category, full JSON details, and the super admin's name and email at the time of the action.

---

## Database Schema

**Key tables:**

| Table | Purpose |
|---|---|
| `users` | Accounts, roles, MFA secrets, status, activation tokens |
| `files` | File records, encryption metadata, download count, watermark status |
| `projects` | Project containers with status (active, archived, completed) |
| `project_members` | User↔project membership with roles |
| `project_folders` | Hierarchical folder structure within projects |
| `watermark_mappings` | Links watermarks to files; tracks crypto_enabled and signature |
| `watermark_crypto_log` | Forensic log: watermark_id, user_id, file_id, signature, IP, timestamp |
| `mfa_recovery_codes` | Backup codes for MFA recovery |
| `activity_log_*` | Role-separated activity logs |
| `super_admin_audit_log` | Super admin action audit trail |

---

## Configuration

**`includes/config.php`**

```php
DB_HOST      = aws-1-ap-southeast-2.pooler.supabase.com
DB_PORT      = 6543
DB_NAME      = postgres
SITE_URL     = http://localhost/stegavault
MAX_FILE_SIZE = 10485760  // 10MB default (upload.php uses 50MB override)
SESSION_IDLE_TIMEOUT_SECONDS = 900  // 15 minutes
TIMEZONE     = Asia/Manila
```

**`includes/email_config.php`**

- SMTP: `smtp.gmail.com:465` (SMTPS)
- Used for: account activation, password reset, MFA backup code delivery

---

## Setup & Installation

1. **Clone the repo** and place it in your web server root (e.g. `htdocs/StegaVault`).

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure the database** in `includes/config.php` — set your Supabase (or local PostgreSQL) credentials.

4. **Run migrations** in order from `migrations/` to set up all tables.

5. **Set write permissions** on:
   ```
   uploads/
   backups/
   logs/
   api/storage/
   ```

6. **Configure email** in `includes/email_config.php` with your SMTP credentials.

7. **Set up the backup cron** on your server:
   ```bash
   0 0,12 * * * php /full/path/to/api/backup_cron.php >> /full/path/to/logs/backup_cron.log 2>&1
   ```

8. **Create your first super admin** directly in the database, then use the admin portal to create additional users.

---

## Security Notes

- The AES-256 encryption key in `Encryption.php` should be moved to an environment variable in production.
- The JWT secret in `config.php` should be set to a strong random value.
- The SMTP credentials in `email_config.php` should use an app-specific password, not a primary account password.
- `api/backup_cron.php` checks `php_sapi_name() === 'cli'` to block web access — do not remove this guard.

---

*StegaVault — Peanut Gallery Media Network*
