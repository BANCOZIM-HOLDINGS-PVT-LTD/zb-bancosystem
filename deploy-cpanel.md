# cPanel Deployment Guide

## 🚀 Deploying Laravel Application to cPanel

### 1. Upload Files
Upload all files to your cPanel public_html directory (or subdirectory).

### 2. Fix Storage Links (Required for cPanel)
Most cPanel hosts don't support symbolic links. Run this instead:

```bash
php sync-storage.php
```

This script:
- ✅ Copies `storage/app/public` to `public/storage` 
- ✅ Creates security `.htaccess` in uploads directory
- ✅ Sets proper permissions

### 3. Set Directory Permissions
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 644 public/storage/uploads/
```

### 4. Environment Configuration
Make sure your `.env` file has:
```env
FILESYSTEM_DISK=public
MAX_FILE_SIZE=10240
ALLOWED_FILE_TYPES=pdf,jpg,jpeg,png
```

### 5. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 6. Test File Uploads
1. Go to your application
2. Navigate to Document Upload section
3. Try uploading a PDF or image file
4. Verify files appear in `public/storage/uploads/`

### 🔄 Maintenance

**After each deployment**, run:
```bash
php sync-storage.php
```

This ensures uploaded files remain accessible.

### 🔒 Security Features
- ✅ Direct PHP execution blocked in uploads folder
- ✅ Directory browsing disabled  
- ✅ Only allowed file types accepted
- ✅ File signature validation
- ✅ Malicious content detection

### 🐛 Troubleshooting

**"mkdir(): No such file or directory"**
- Run: `php sync-storage.php`
- Check: `storage/app/public/uploads/` exists
- Check: Directory permissions are 755+

**Files upload but can't be accessed**
- Run: `php sync-storage.php`
- Verify: `public/storage/uploads/` contains files
- Check: `.htaccess` in uploads folder exists

**Permission denied errors**
- Set permissions: `chmod -R 755 storage/`
- For uploads: `chmod -R 777 storage/app/public/uploads/`

### 📁 Directory Structure
```
public_html/
├── public/
│   └── storage/           ← Copied files (accessible via web)
│       └── uploads/
├── storage/
│   └── app/
│       └── public/        ← Original files (not web accessible)
│           └── uploads/
└── sync-storage.php       ← Run this after deployment
```

### ⚡ Quick Commands
```bash
# Full deployment sync
php sync-storage.php && php artisan config:clear

# Check permissions
ls -la storage/app/public/uploads/
ls -la public/storage/uploads/

# Test upload endpoint
curl -X POST /api/documents/validate -F "file=@test.pdf"
```