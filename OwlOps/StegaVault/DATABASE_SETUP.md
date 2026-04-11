# 🗄️ Database Setup Guide - Cryptographic Watermarks

## What You Need to Add to Your Database

You need to add **1 new table** and **2 new columns** to your existing database.

---

## 📋 Step-by-Step Instructions

### **Option 1: Using phpMyAdmin (EASIEST)**

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`
   - Login (usually no password for XAMPP)

2. **Select Your Database**
   - Click on `stegavault` database in the left sidebar

3. **Run the SQL**
   - Click on the "SQL" tab at the top
   - Copy and paste the SQL below
   - Click "Go" button

```sql
-- Create watermark_crypto_log table
CREATE TABLE IF NOT EXISTS watermark_crypto_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    watermark_id VARCHAR(100) NOT NULL,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    signature VARCHAR(255) NOT NULL,
    key_id VARCHAR(64) NOT NULL,
    nonce VARCHAR(64) NOT NULL,
    timestamp BIGINT NOT NULL,
    ip_address VARCHAR(45),
    verified BOOLEAN DEFAULT FALSE,
    verification_count INT DEFAULT 0,
    last_verified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_signature (signature),
    INDEX idx_watermark_id (watermark_id),
    INDEX idx_file_user (file_id, user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add columns to watermark_mappings table
ALTER TABLE watermark_mappings 
ADD COLUMN crypto_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN signature VARCHAR(255) DEFAULT NULL;

-- Add index for signature
ALTER TABLE watermark_mappings 
ADD INDEX idx_signature (signature);
```

4. **Verify Success**
   - You should see: "Query OK" or "Table created successfully"
   - If you see errors about columns already existing, that's OK!

---

### **Option 2: Using MySQL Command Line**

```bash
cd c:\xampp\htdocs\StegaVault
mysql -u root stegavault < migrations/crypto_watermark.sql
```

---

## 📊 What This Adds

### **New Table: `watermark_crypto_log`**

This table stores the cryptographic audit trail for every watermark.

| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT | Auto-increment ID |
| `watermark_id` | VARCHAR(100) | Links to watermark_mappings |
| `file_id` | INT | Which file was downloaded |
| `user_id` | INT | Who downloaded it |
| `signature` | VARCHAR(255) | **HMAC signature (proves authenticity)** |
| `key_id` | VARCHAR(64) | User's encryption key ID |
| `nonce` | VARCHAR(64) | Unique random number |
| `timestamp` | BIGINT | When watermark was created |
| `ip_address` | VARCHAR(45) | Where it was downloaded from |
| `verified` | BOOLEAN | Has it been verified? |
| `verification_count` | INT | How many times verified |
| `last_verified` | TIMESTAMP | Last verification time |
| `created_at` | TIMESTAMP | When log entry was created |

**Purpose:** Complete forensic audit trail - tracks every watermark and verification

---

### **Updated Table: `watermark_mappings`**

Adds 2 new columns to your existing table:

| Column | Type | Purpose |
|--------|------|---------|
| `crypto_enabled` | BOOLEAN | Is this a crypto watermark? |
| `signature` | VARCHAR(255) | Quick signature lookup |

**Purpose:** Flag which watermarks have crypto protection

---

## ✅ How to Verify It Worked

After running the SQL, check:

1. **In phpMyAdmin:**
   - Click on `stegavault` database
   - You should see `watermark_crypto_log` in the table list
   - Click on `watermark_mappings`
   - Click "Structure" tab
   - You should see `crypto_enabled` and `signature` columns

2. **Table Count:**
   - Before: 5 tables (users, files, projects, project_members, watermark_mappings, activity_log)
   - After: 6 tables (+ watermark_crypto_log)

---

## 🎯 What Happens After Setup

### **Automatic (No Action Needed):**

1. **When User Downloads File:**
   ```
   User clicks download
   ↓
   System generates crypto watermark
   ↓
   Embeds in image (invisible)
   ↓
   Logs to watermark_crypto_log table ← NEW!
   ↓
   Updates watermark_mappings with signature ← NEW!
   ↓
   User receives watermarked file
   ```

2. **Database Entries Created:**
   - `watermark_mappings`: 1 row (with crypto_enabled=TRUE, signature=abc123...)
   - `watermark_crypto_log`: 1 row (full crypto details)

### **When Admin Verifies:**

```
Admin uploads file to analysis.php
↓
System extracts watermark
↓
Checks if crypto watermark exists
↓
If YES: Shows crypto verification section ← NEW!
↓
Updates verification_count in watermark_crypto_log ← NEW!
```

---

## 📸 What You'll See

### **Before Crypto (Legacy Watermark):**
```
analysis.php shows:
┌─────────────────────────────┐
│ ✅ Digital Signature Verified│
│                              │
│ User: John Doe               │
│ IP: 192.168.1.50             │
│ Timestamp: 2026-02-10        │
└─────────────────────────────┘
```

### **After Crypto (New Watermark):**
```
analysis.php shows:
┌─────────────────────────────┐
│ ✅ Digital Signature Verified│
│                              │
│ User: John Doe               │
│ IP: 192.168.1.50             │
│ Timestamp: 2026-02-10        │
└─────────────────────────────┘

┌─────────────────────────────┐  ← NEW SECTION!
│ 🔐 Cryptographic Verification│
│                              │
│ ✅ Cryptographically         │
│    Authenticated             │
│                              │
│ Signature: ✓ VALID           │
│ Encryption: ✓ VALID          │
│ Integrity: ✓ INTACT          │
│ Tamper Detection: ✓ PASSED   │
│                              │
│ Security Features:           │
│ • AES-256 Encryption         │
│ • HMAC-SHA256 Signature      │
│ • User-Specific Keys         │
│ • Tamper Detection           │
└─────────────────────────────┘
```

---

## 🔍 Database Examples

### **Example: watermark_crypto_log Entry**
```
id: 1
watermark_id: wm_3_456_1234567890
file_id: 456
user_id: 3
signature: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...
key_id: user_key_abc123...
nonce: 1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p
timestamp: 1707728400
ip_address: 192.168.1.50
verified: TRUE
verification_count: 3
last_verified: 2026-02-12 12:44:00
created_at: 2026-02-10 14:30:15
```

### **Example: watermark_mappings Entry (Updated)**
```
id: 5
file_id: 456
user_id: 3
watermark_id: wm_3_456_1234567890
watermarked_path: uploads/watermarked/wm_3_456_1234567890.png
generated_at: 2026-02-10 14:30:15
download_count: 1
last_download: 2026-02-10 14:30:15
crypto_enabled: TRUE  ← NEW!
signature: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...  ← NEW!
```

---

## ⚠️ Important Notes

1. **Existing Data:** Your existing watermarks will still work! They just won't have crypto verification.

2. **New Downloads:** All NEW downloads (after setup) will automatically get crypto watermarks.

3. **No User Impact:** Users won't notice any difference. Downloads work exactly the same.

4. **Admin Benefit:** You get crypto verification in analysis.php for new watermarks.

---

## 🎉 That's It!

After running the SQL:
- ✅ Database is ready
- ✅ Crypto watermarks will be generated automatically
- ✅ analysis.php will show crypto verification
- ✅ Complete audit trail in database

**Next step:** Download a file and verify it in analysis.php to see it in action!
