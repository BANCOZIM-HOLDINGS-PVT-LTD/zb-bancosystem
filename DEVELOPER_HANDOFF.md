# Developer Handoff Document
**Date:** 2026-01-26
**Topic:** Application Submission & PDF Generation Issues

## 1. System Status & Recent Changes
The following environment configurations have been modified to attempt to resolve the "500 Internal Server Error" during submission:

*   **php.ini**:
    *   `memory_limit` increased to **2048M** (was 512M).
    *   `upload_max_filesize` increased to **100M** (was 40M).
    *   `post_max_size` increased to **100M** (was 50M).
    *   `extension=gd` enabled.
*   **.env**:
    *   `APP_URL` updated to `http://192.168.1.104:8000` (LAN IP).
*   **bootstrap/app.php**:
    *   `RequestSizeLimitMiddleware` limit increased to **100MB**.

## 2. Critical Issue: Application Submission Failure (500 Error)

### Symptoms
*   When submitting the application (Wizard -> Submit), the endpoint `/api/states/save` returns a **500 Internal Server Error**.
*   **Crucial Finding**: Debug logs added to `SaveApplicationStateRequest::prepareForValidation` and `StateController::saveState` **DO NOT APPEAR** in `laravel.log`.
*   This indicates the request is crashing **before** it hits the Laravel validation logic or controller.

### Instrumentation
I have added temporary `\Log::info(...)` calls in:
1.  `app/Http/Controllers/Api/StateController.php` (at the start of `saveState`).
2.  `app/Http/Requests/SaveApplicationStateRequest.php` (in `prepareForValidation`).

### Potential Causes & Next Steps
1.  **Middleware Blocking**: The `TrimStrings` or `ConvertEmptyStringsToNull` middleware might be running out of memory processing massive Base64 strings in the JSON payload.
    *   **Action**: Temporarily disable these middleware in `bootstrap/app.php` or `app/Http/Kernel.php` to test.
2.  **Server-Level Limits**: If using Apache (XAMPP default), the `LimitRequestBody` might be too low, or PHP's `max_input_vars` might be exceeded by a deep JSON structure.
    *   **Action**: Check Apache `httpd.conf` and increase `max_input_vars` in `php.ini`.
3.  **JSON vs Multipart**: The frontend sends the entire application state (listing images as Base64 strings?) as a single JSON blob.
    *   **Action**: Refactor `StateManager.ts` to upload documents separately first, getting an ID/URL, and *only* submit the metadata in the final JSON. **Sending 50MB+ JSON payloads is an anti-pattern.**

## 3. Critical Issue: PDF Document Visibility

### Symptoms
*   Generated PDFs (SSB Loan, Account Holders) only show the "Selfie" image.
*   Other KYC documents (ID, Payslip) are missing or broken images.

### Root Cause Analysis
*   **Path Resolution**: The PDF generator (likely DomPDF or Snappy) often fails to resolve HTTP URLs (`http://192.168.../storage/...`) during generation because it requires a network loopback which might time out or be blocked.
*   **Selfie Exception**: The selfie works likely because it's either embedded directly as Base64 or handled differently (e.g., local temporary path).

### Recommended Fixes
1.  **Use Local Paths**: In your Blades/Views, do **not** use `asset()` or `url()` for images intended for PDF generation.
    *   **Incorrect**: `<img src="{{ asset('storage/uploads/id.jpg') }}">`
    *   **Correct (for PDF)**: `<img src="{{ public_path('storage/uploads/id.jpg') }}">`
    *   Using `public_path()` accesses the file directly on the disk, bypassing the network stack.
2.  **Verify Storage Link**: Ensure the symbolic link exists: `php artisan storage:link`.
3.  **Check Permissions**: Ensure the `storage/app/public` directory is readable by the PHP process.

## 4. Summary of Files Changed
*   `app/Http/Controllers/Api/StateController.php` (Added try-catch & logging)
*   `app/Http/Requests/SaveApplicationStateRequest.php` (Added logging)
*   `bootstrap/app.php` (Increased API limit)
*   `database/migrations/2026_01_26_150000_add_last_activity_to_application_states_table.php` (Added to fix SQL error)

---
**Recommended Immediate Action:**
Refactor the frontend to stop sending file data (Base64) inside the JSON state body. Upload files asynchronously, verify them, and only submit the file paths/IDs in the final `saveState` call. This will resolve the memory/timeout issues permanently.
