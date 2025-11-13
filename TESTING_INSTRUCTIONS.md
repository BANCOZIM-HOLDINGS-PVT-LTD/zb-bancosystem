# Testing Instructions - ID Verification with didit.me

## 🚀 Quick Start Guide

Follow these steps to test your ID verification system:

---

## Step 1: Build the Frontend

```bash
# Navigate to your project directory
cd C:\xampp\htdocs\bancosystem

# Install dependencies (if not already done)
npm install

# Build the frontend
npm run build
```

**Expected Output**:
```
✓ built in XXXms
```

---

## Step 2: Start Your Server

### Option A: Using XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Your application should be available at: `http://localhost/bancosystem/public`

### Option B: Using PHP Built-in Server
```bash
php artisan serve
```
Application will be at: `http://localhost:8000`

---

## Step 3: Clear Cache (Important!)

```bash
# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
```

---

## Step 4: Test the ID Verification Flow

### **Navigate to Document Upload**

1. Open your application in a browser
2. Go through the application wizard steps
3. Reach the **"Document Upload & Verification"** step
4. You should see the **"National ID"** section at the top

### **Test ID Card Verification**

**Step-by-Step**:

1. **Click** "Start ID Verification" button
   - Camera permission popup should appear
   - Grant camera access

2. **Camera Opens**
   - You should see live video feed
   - Visual frame overlay appears (rectangular guide)
   - Position your Zimbabwean National ID within the frame

3. **Capture ID Card**
   - Click "Capture ID Card" button
   - Image freezes and shows processing indicator
   - Progress bar appears: "Verifying ID card... Performing OCR, AI validation, and biometric checks..."

4. **View Results** (after 2-5 seconds)
   - ✅ **Success**: Green box appears with:
     - "ID Card Verified Successfully!"
     - ID Number: XX-XXXXXXX-Y-ZZ
     - Name: First Last
     - Card Type: Metal Card / Plastic Card
     - Confidence: XX%
     - ✓ OCR Verified, AI Validated, Biometric Match

   - ❌ **Failure**: Red box appears with:
     - "Verification Failed"
     - Error message explaining the issue
     - "Try Again" button to retry

5. **After Successful Verification**
   - Upload/camera options become enabled
   - You can now upload or capture your ID document
   - Verification data is stored for the application

---

## Step 5: Check Logs

Monitor the application logs to see API calls:

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log

# Or view last 50 lines
tail -50 storage/logs/laravel.log
```

**What to look for**:
```
[2025-11-10 XX:XX:XX] local.INFO: Calling didit.me ID verification API
[2025-11-10 XX:XX:XX] local.INFO: Didit API response received
```

---

## Test Cases

### ✅ **Test Case 1: Metal Zimbabwean ID**
**Steps**:
1. Use a metal/biometric Zimbabwean national ID
2. Follow verification flow
3. **Expected**: Card type shows "Metal Card"

### ✅ **Test Case 2: Plastic Zimbabwean ID**
**Steps**:
1. Use an older plastic laminated Zimbabwean ID
2. Follow verification flow
3. **Expected**: Card type shows "Plastic Card"

### ✅ **Test Case 3: Good Lighting**
**Steps**:
1. Ensure good lighting (no shadows, no glare)
2. Card flat and in focus
3. **Expected**: High confidence score (>90%)

### ❌ **Test Case 4: Poor Lighting**
**Steps**:
1. Try in dark room or with heavy shadows
2. **Expected**: Verification fails or low confidence score

### ❌ **Test Case 5: Blurry Image**
**Steps**:
1. Move camera while capturing
2. **Expected**: Verification fails with error message

### ❌ **Test Case 6: Wrong Document**
**Steps**:
1. Try with different document (passport, driver's license)
2. **Expected**: Verification fails or incorrect data extracted

---

## Troubleshooting

### Issue: "Camera not available"
**Solutions**:
- Grant camera permissions in browser
- Ensure HTTPS or localhost (browsers require secure context)
- Check if another app is using the camera

### Issue: "Failed to load face detection models"
**Check**:
```bash
ls -la public/models/
# Should show 4 files:
# - tiny_face_detector_model-weights_manifest.json
# - tiny_face_detector_model-shard1
# - face_landmark_68_model-weights_manifest.json
# - face_landmark_68_model-shard1
```

### Issue: "Didit API key not configured"
**Check `.env` file**:
```bash
grep DIDIT .env
# Should show:
# DIDIT_API_KEY=FVuQt4Lx7IoCmZqvejHzAcjdO2mYkSogZIo_FN_go_k
# DIDIT_APP_ID=031e56ce-7810-4499-bfa8-85a4ad7236e9
# DIDIT_API_URL=https://verification.didit.me
```

If missing, run:
```bash
php artisan config:clear
php artisan config:cache
```

### Issue: "Network error" or timeouts
**Check**:
- Internet connection working
- Didit.me API is accessible
- No firewall blocking the request
- Check logs: `tail -50 storage/logs/laravel.log`

### Issue: Frontend not updated
**Rebuild**:
```bash
npm run build
php artisan view:clear
# Hard refresh browser: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
```

---

## API Testing (Optional)

Test the API endpoint directly using curl:

```bash
curl -X POST http://localhost/bancosystem/public/api/verify-id-card \
  -H "Accept: application/json" \
  -F "id_card_image=@/path/to/your/id-card.jpg" \
  -F "country=ZW" \
  -F "document_type=NATIONAL_ID"
```

**Expected Response**:
```json
{
  "success": true,
  "message": "ID card verified successfully",
  "data": {
    "verified": true,
    "id_number": "XX-XXXXXXX-Y-ZZ",
    "first_name": "John",
    "last_name": "Doe",
    ...
  }
}
```

---

## Verification Checklist

Before considering the feature complete, verify:

- [ ] Camera opens successfully
- [ ] Visual frame guide appears
- [ ] Can capture image
- [ ] Processing indicator shows
- [ ] API call to didit.me succeeds
- [ ] Verification results display correctly
- [ ] ID number extracted correctly
- [ ] Name extracted correctly
- [ ] Card type detected (metal/plastic)
- [ ] Confidence score shows (>85%)
- [ ] Upload/camera enabled after verification
- [ ] Can retry on failure
- [ ] Error messages are clear
- [ ] Mobile responsive (test on phone)
- [ ] Works on Chrome, Firefox, Safari
- [ ] Logs show detailed information

---

## Performance Expectations

**Timing**:
- Camera startup: < 2 seconds
- Image capture: Instant
- API call to didit.me: 2-5 seconds
- Results display: Instant
- Total time: 5-10 seconds per verification

**Success Rate**:
- Good conditions: >95% success rate
- Poor lighting: May require 2-3 attempts
- Blurry/unclear: Should fail gracefully with clear error

---

## Next Steps After Testing

Once testing is successful:

1. **Monitor Usage**:
   - Check didit.me dashboard: https://business.didit.me/dashboard
   - Track verification counts
   - Monitor costs

2. **Gather Feedback**:
   - Test with real users
   - Note any common issues
   - Track success rates

3. **Optimize**:
   - Adjust confidence thresholds if needed
   - Improve error messages based on feedback
   - Add analytics tracking

4. **Production Deployment**:
   - Deploy to production server
   - Test on production domain
   - Monitor logs and performance

---

## Support

**Documentation**:
- Main Implementation: `ID_VERIFICATION_SDK_IMPLEMENTATION.md`
- Didit Integration: `DIDIT_ME_INTEGRATION_COMPLETE.md`
- Face Detection: `FACE_DETECTION_AND_ID_VALIDATION_IMPLEMENTATION.md`

**Logs**:
```bash
storage/logs/laravel.log
```

**Configuration**:
- `.env` - API credentials
- `config/services.php` - Service configuration
- `routes/api.php` - API routes

**Key Files**:
- Frontend: `resources/js/components/ui/id-card-verifier.tsx`
- Backend: `app/Http/Controllers/Api/IDVerificationController.php`
- Upload Flow: `resources/js/components/DocumentUpload/DocumentUploadStep.tsx`

---

**Happy Testing! 🎉**