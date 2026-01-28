# Developer Handoff - ZB Bank Application System

## 🌟 System Overview
This system is a multi-channel (Web, WhatsApp, USSD) application wizard for ZB Bank. It allows both existing customers (to apply for loans) and new customers (to open accounts). 

The architecture relies on a **Stateful Wizard**: every step is saved to the `application_states` table, allowing users to resume from any device.

## 🔄 Application Submission Lifecycle
When a user clicks "Submit" on the frontend, the following sequence occurs on the backend:

1.  **`StateController@saveState`**: The final form data is saved with a status of `completed`.
2.  **`ApplicationStateObserver`**: Fired by the database save.
    *   It checks for status changes.
    *   It calls `NotificationService` to log/send any initial customer alerts.
3.  **`StateController@createApplication`**: The "Submission Finalizer".
    *   Generates a unique `reference_code`.
    *   If it's an **Account Opening**, it creates a record in the `account_openings` table.
    *   It attempts to generate a **PDF** synchronously via `PDFGeneratorService`.
4.  **Admin Panel Visibility**: 
    *   Loan applications appear in the main "Applications" resource.
    *   Account openings appear in the specialized "ZB Account Opening" resource.

---

## Current Status (as of Jan 28, 2026)
We have successfully resolved the major "Maximum execution time exceeded" (500 Error) that was blocking all submissions. However, the system is currently hitting a secondary failure during the final PDF generation for **Account Opening** applications.

### ✅ Resolved Issues
1.  **Infinite Recursion Loop (Backend):** The `ApplicationStateObserver` was triggering a `save()` call via `NotificationService` whenever the status changed. This caused an infinite loop of saves.
    *   **Fix:** Added a static recursion guard (`$isProcessing`) in `ApplicationStateObserver.php`.
2.  **Submission Payload Size (Frontend):** Large raw File objects were being sent in the `saveState` payload.
    *   **Fix:** Explicitly set `uploadedDocuments: undefined` in `DocumentUploadStep.tsx` while keeping the file paths in `documentReferences`.
3.  **Application Categorization Logic:** Applications were being miscategorized as loans even when the user intent was "New Account".
    *   **Fix:** Updated `PDFGeneratorService::preparePDFData` to prioritize `wantsAccount` over `hasAccount`.

### ⚠️ Current Blockers
1.  **PDF Template Crash:** The `forms.zb_account_opening_pdf` template is currently failing. Logs show "Account opening PDF generation failed". This is likely due to missing data keys or invalid base64 encoding for the Selfie/Signature in the Blade template.
2.  **Frontend Dev Server Timeout:** The user's Vite server often fails to fetch `ApplicationSuccess.tsx`. This is a local network/environment issue, but it makes the submission *look* like it failed when the backend actually succeeded.

---

## Core Logic & Categorization
It is critical to distinguish between the three main flows to avoid mapping errors:

### 1. ZB Account Opening (New Customers)
*   **Trigger:** `wantsAccount == true` OR `intent == 'account'`.
*   **PDF Template:** `resources/views/forms/zb_account_opening_pdf.blade.php`
*   **Data Requirements:** Full KYC (National ID, Selfie, Signature, Proof of Residence).
*   **Logic:** This is the most complex. The PDF must embed base64 images of the customer's signature and selfie.

### 2. ZB Account Holders (Existing Customers - Loan)
*   **Trigger:** `hasAccount == true` AND `wantsAccount == false`.
*   **PDF Template:** `resources/views/forms/account_holders_pdf.blade.php`
*   **Logic:** Focuses on loan specific terms (repayment, amount). Documents are usually already on file at the bank, so requirements are lighter.

### 3. SSB/Government Loan
*   **Trigger:** `employer` is one of `['ssb', 'government', 'goz-ssb']`.
*   **PDF Template:** `resources/views/forms/ssb_form_pdf.blade.php`
*   **Logic:** This overrides everything else because SSB has a specific legacy PDF format required by the Salary Service Bureau.

---

## Internal Service Map
*   **`StateManager.php`**: The "Heart" of the system. Handles saving step-by-step data to `application_states`.
*   **`ApplicationStateObserver.php`**: Intercepts every save. Watch out for recursion!
*   **`PDFGeneratorService.php`**: Orchestrates data preparation and template selection. It uses `dompdf`.
*   **`DocumentUploadStep.tsx`**: Handles the file uploads to `/api/documents/upload` before final submission.

## Next Steps for Developers
1.  Debug `zb_account_opening_pdf.blade.php`. Check lines 100-150 where images are embedded. Ensure the `getImageData` helper doesn't crash on null paths.
2.  Verify `StateController::createApplication`. Ensure that even if PDF generation fails, the JSON response returns `success: true` (which it currently does via try-catch, but the catch might be incomplete).
