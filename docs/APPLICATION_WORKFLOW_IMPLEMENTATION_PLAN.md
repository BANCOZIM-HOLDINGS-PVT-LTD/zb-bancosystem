# BancoSystem — Application Workflow Overhaul: Implementation Plan

> **Purpose**: Shareable blueprint for any developer or AI agent. Covers the full lifecycle of loan/account applications: submission → manual verification stages → admin sync → delivery/PO/PDF generation.

---

## 1. System Overview

BancoSystem is a multi-channel (Web + WhatsApp) banking/loan platform. Clients apply through a wizard, applications flow through 3 manual verification stages, and multiple admin roles handle specific parts of the pipeline.

### 1.1 Application Types

| # | Type | Frontend Form | PDF Template | Proof of Employment Required? |
|---|------|---------------|--------------|-------------------------------|
| 1 | **SSB** (Government Loan) | `SSBLoanForm.tsx` | `ssb_form_pdf.blade.php` | **No** — goes straight to Stage 2 |
| 2 | **ZB Account Opening** | `ZBAccountOpeningForm.tsx` | `zb_account_opening_pdf.blade.php` | **Yes** |
| 3 | **ZB Account Holder** | `AccountHoldersLoanForm.tsx` | `account_holders_pdf.blade.php` | **Yes** |
| 4 | **Pensioner** | `PensionerLoanForm.tsx` | `pensioner_loan_pdf.blade.php` | **Yes** |
| 5 | **RDC** (Rural District Council) | `RDCLoanForm.tsx` | *(uses SSB or account holders template)* | **Yes** |

### 1.2 Admin Roles & Panels (Filament)

| Role | Panel ID | Responsibility |
|------|----------|----------------|
| **Super Admin (BancoZim)** | `admin` | Stage 1: Document verification & allocation. Full system control. |
| **Qupa Admin** | `partner` | Receives allocated applications. Manages loan officers & branch managers. |
| **Loan Officer** | *(under Qupa)* | Stage 2: Financial assessment & credit checks. |
| **Branch Manager** | *(under Qupa)* | Stage 3: Final approval/rejection. |
| **Stores** | `stores` | Inventory, Purchase Orders, Delivery dispatch. |
| **HR** | `hr` | Employee/payroll management. |
| **Accounting** | `accounting` | Invoicing, financial records. |
| **ZB Admin** | `zb-admin` | ZB-specific administration. |

### 1.3 Current Problems

1. **SSB/FCB API endpoints killed**: Routes in `api.php` return HTTP 410 (lines 265-290). Old `SSBLoanStatus` and `ZBLoanStatus` enums still model automated API flows (`AWAITING_SSB_APPROVAL`, `AWAITING_CREDIT_CHECK`) — no longer applicable.

2. **Status page out of sync**: `ApplicationStatusController` timeline references steps like `pending_review`, `officer_check`, `manager_approval`, but actual `current_step` values don't match because the old flow used SSB/ZB metadata.

3. **No unified manual workflow**: Each type previously had its own status flow via separate services. Now all should follow the same 3-stage manual pipeline with type-specific gates (SSB skips proof of employment; all others require it).

4. **Products/business not in PDFs**: Selected business/products and product codes are not fully listed on invoices and delivery notes.

5. **SSB Application Number wrong format**: Currently `SSB{YYYY}{000001}`, needs to be `SSBB/M/PP/NNNNN` format.

---

## 2. Workstream 1: Unified Manual Application Workflow

### 2.1 New Unified Status Enum

**Delete** `app/Enums/SSBLoanStatus.php` and `app/Enums/ZBLoanStatus.php` entirely.

**Create** `app/Enums/ApplicationStatus.php`:

```php
enum ApplicationStatus: string
{
    // Submission
    case SUBMITTED = 'submitted';

    // Stage 1: BancoZim Super Admin - Document Verification
    case STAGE1_DOCUMENT_REVIEW = 'stage1_document_review';
    case STAGE1_DOCUMENTS_UNCLEAR = 'stage1_documents_unclear';       // → client re-upload
    case STAGE1_RESUBMITTED = 'stage1_resubmitted';                   // client re-uploaded
    case STAGE1_VERIFIED = 'stage1_verified';                         // docs OK

    // Proof of Employment Gate (ZB Account Opening, ZB Account Holder, Pensioner, RDC)
    // SSB applications SKIP this gate entirely
    case AWAITING_EMPLOYMENT_PROOF = 'awaiting_employment_proof';     // client must submit proof
    case EMPLOYMENT_PROOF_SUBMITTED = 'employment_proof_submitted';   // proof uploaded → Stage 2

    // Stage 2: Qupa Loan Officer - Financial Assessment
    case STAGE2_OFFICER_REVIEW = 'stage2_officer_review';
    case STAGE2_ADDITIONAL_DOCS_REQUESTED = 'stage2_additional_docs'; // officer needs more info
    case STAGE2_OFFICER_APPROVED = 'stage2_officer_approved';         // passes to Stage 3

    // Stage 3: Branch Manager - Final Approval
    case STAGE3_MANAGER_REVIEW = 'stage3_manager_review';
    case STAGE3_APPROVED = 'stage3_approved';                         // triggers PO + delivery

    // Terminal states
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
```

**Methods to include:**
- `getMessage(): string` — user-friendly label for client-facing pages
- `requiresClientAction(): bool` — true for `STAGE1_DOCUMENTS_UNCLEAR`, `AWAITING_EMPLOYMENT_PROOF`
- `isFinalState(): bool` — true for `APPROVED`, `REJECTED`, `CANCELLED`
- `getAllowedTransitions(): array` — enforces valid state transitions
- `getStageNumber(): ?int` — returns 1, 2, or 3

---

### 2.2 Application Flow Diagrams

#### All Application Types — Stage 1

```
Client submits → SUBMITTED → STAGE1_DOCUMENT_REVIEW

BancoZim Admin checks documents:
    ├── Docs unclear → STAGE1_DOCUMENTS_UNCLEAR
    │       → client re-uploads → STAGE1_RESUBMITTED
    │       → back to STAGE1_DOCUMENT_REVIEW
    └── Docs OK → STAGE1_VERIFIED
```

#### SSB Applications — After Stage 1

```
STAGE1_VERIFIED
    ↓
→ STAGE2_OFFICER_REVIEW  (immediately, NO proof of employment required)
```

#### ZB Account Opening / ZB Account Holder / Pensioner / RDC — After Stage 1

```
STAGE1_VERIFIED
    ↓
→ AWAITING_EMPLOYMENT_PROOF  (client must submit proof)
    ↓
Client uploads proof of employment
    ↓
→ EMPLOYMENT_PROOF_SUBMITTED
    ↓
→ STAGE2_OFFICER_REVIEW
```

#### All Application Types — Stage 2 & 3

```
STAGE2_OFFICER_REVIEW
    ├── Needs more docs → STAGE2_ADDITIONAL_DOCS_REQUESTED
    ├── Reject → REJECTED
    └── Approve → STAGE2_OFFICER_APPROVED → STAGE3_MANAGER_REVIEW

STAGE3_MANAGER_REVIEW
    ├── Reject → REJECTED
    └── Approve → STAGE3_APPROVED → APPROVED

On APPROVED:
    → Generate PDF (with full product list)
    → Create Purchase Order (Stores)
    → Create Delivery Tracking
    → Trigger Invoice (Accounting)
    → Calculate Agent Commission
    → Notify Client via SMS
```

---

### 2.3 Files to Change

#### New Files

| File | Description |
|------|-------------|
| `app/Enums/ApplicationStatus.php` | Unified status enum (replaces SSBLoanStatus + ZBLoanStatus) |
| `app/Services/ManualWorkflowService.php` | Central workflow service for all status transitions |
| `database/migrations/xxxx_add_application_status_to_application_states.php` | Add `application_status` column |
| `database/migrations/xxxx_migrate_existing_statuses.php` | Data migration for existing records |

#### Files to Delete

| File | Reason |
|------|--------|
| `app/Enums/SSBLoanStatus.php` | Replaced by `ApplicationStatus` |
| `app/Enums/ZBLoanStatus.php` | Replaced by `ApplicationStatus` |

#### Files to Modify

| File | Changes |
|------|---------|
| `app/Models/ApplicationState.php` | Add `application_status` to `$fillable`/`$casts`. Add `getApplicationTypeAttribute()`, `requiresEmploymentProof(): bool`, update `getApplicationNumberAttribute()` for SSB format. |
| `app/Services/ApplicationWorkflowService.php` | Update `approveApplication()` to use new `ApplicationStatus::APPROVED`. Ensure downstream effects (delivery, commission, PDF) use full product list. |
| `app/Services/SSBStatusService.php` | **Delete** or gut — all logic moves to `ManualWorkflowService` |
| `app/Services/ZBStatusService.php` | **Delete** or gut — same as above |
| `app/Services/PurchaseOrderService.php` | Ensure `extractItems()` carries product codes through. Ensure MicroBiz package expansion includes codes. |
| `app/Services/PDFGeneratorService.php` | Extract full product list (cart items, selected business, package contents) with product codes. Pass `$packageItems` array to all PDF templates. |
| `app/Http/Controllers/ApplicationStatusController.php` | Update `determineApplicationStatus()`, `buildApplicationTimeline()`, `calculateProgressPercentage()`, `getNextAction()` to use `ApplicationStatus` enum. |
| `app/Http/Controllers/ApplicationWizardController.php` | On submission, call `ManualWorkflowService::submitApplication()`. |
| `app/Filament/Resources/ApplicationResource.php` | Replace SSB/ZB workflow dropdowns with unified stage actions. Update table badges and filters. Make actions role/stage-aware. |
| `app/Observers/ApplicationStateObserver.php` | Listen for `application_status` changes. On `APPROVED`, trigger PersonalService creation + PurchaseOrder creation. |
| `routes/api.php` | Keep SSB/ZB returning 410. Add route for employment proof submission if needed. |

---

### 2.4 ManualWorkflowService — Key Methods

**File:** `app/Services/ManualWorkflowService.php`

| Method | Description | Triggered By |
|--------|-------------|--------------|
| `submitApplication(ApplicationState $app)` | Sets `SUBMITTED` → `STAGE1_DOCUMENT_REVIEW`. Determines app type. Sends SMS confirmation. | `ApplicationWizardController` on form submit |
| `stage1Verify(ApplicationState $app)` | Marks docs verified. If app requires employment proof → `AWAITING_EMPLOYMENT_PROOF`. If SSB → `STAGE2_OFFICER_REVIEW`. Allocates to Qupa. | BancoZim Admin (Filament) |
| `stage1RequestReupload(ApplicationState $app, array $docs)` | Sets `STAGE1_DOCUMENTS_UNCLEAR`. Stores unclear doc details in metadata. SMS to client. | BancoZim Admin (Filament) |
| `submitEmploymentProof(ApplicationState $app)` | Sets `EMPLOYMENT_PROOF_SUBMITTED` → `STAGE2_OFFICER_REVIEW`. | Client via status page / API |
| `stage2Approve(ApplicationState $app)` | Sets `STAGE2_OFFICER_APPROVED` → `STAGE3_MANAGER_REVIEW`. | Loan Officer (Qupa panel) |
| `stage2RequestDocs(ApplicationState $app, array $docs)` | Sets `STAGE2_ADDITIONAL_DOCS_REQUESTED`. | Loan Officer (Qupa panel) |
| `stage3Approve(ApplicationState $app)` | Sets `STAGE3_APPROVED` → `APPROVED`. Triggers: PDF, PO, delivery, commission, SMS. | Branch Manager (Qupa panel) |
| `reject(ApplicationState $app, string $reason, string $stage)` | Sets `REJECTED`. Records stage + reason. | Any admin at their stage |
| `getClientStatusDetails(ApplicationState $app)` | Returns unified status data for client-facing page. | `ApplicationStatusController` |

All transitions validated against `ApplicationStatus::getAllowedTransitions()`. All logged as `StateTransition` records.

---

## 3. Workstream 2: Application Status Page Sync

### 3.1 Client-Facing Timeline

Update `ApplicationStatusController::buildApplicationTimeline()` to render:

| # | Timeline Step | Complete When |
|---|--------------|---------------|
| 1 | Application Submitted | Always complete |
| 2 | Document Verification (BancoZim) | `status >= STAGE1_VERIFIED` |
| 2b | Proof of Employment *(non-SSB only)* | `status >= EMPLOYMENT_PROOF_SUBMITTED` |
| 3 | Financial Assessment (Loan Officer) | `status >= STAGE2_OFFICER_APPROVED` |
| 4 | Final Approval (Branch Manager) | `status >= STAGE3_APPROVED` |
| 5 | Product Delivery | Based on `DeliveryTracking` status |

**Key:** Step 2b only shows for ZB Account Opening, ZB Account Holder, Pensioner, and RDC. It is hidden for SSB.

### 3.2 Admin Sync

All admin panels read the same `application_status` column from `application_states` table. The Filament `ApplicationResource` table badge uses this field — all admins see identical state.

### 3.3 Controller Updates

| Method | Change |
|--------|--------|
| `determineApplicationStatus()` | Use `ApplicationStatus` enum instead of raw `current_step` matching |
| `buildApplicationTimeline()` | Build timeline from enum stages, conditionally show proof of employment step |
| `calculateProgressPercentage()` | Map each `ApplicationStatus` to a percentage |
| `getNextAction()` | Return correct user-facing message per status |

---

## 4. Workstream 3: Products/Business in PDFs

### 4.1 Problem
Selected business/products and their product codes are not listed on generated PDFs (application form, invoice, delivery note).

### 4.2 Solution

**Modify `PDFGeneratorService.php`:**
- Extract the full product list from `form_data`:
  - `form_data['cartItems']` — Building Materials flow
  - `form_data['selectedBusiness']` + its `MicrobizPackage` constituent products
  - `form_data['selectedProduct']` — legacy/SSB
- For MicroBiz packages: Expand into component products using the same logic as `PurchaseOrderService::expandPackages()`.
- Pass to all PDF templates as `$packageItems` array with fields: `name`, `product_code`, `quantity`, `unit_price`.

**Modify all PDF Blade templates** — add a "Package Contents" table section:

```html
<h3>Package Contents</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Product Code</th>
            <th>Item Description</th>
            <th>Qty</th>
            <th>Unit Price (USD)</th>
            <th>Total (USD)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($packageItems as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item['product_code'] ?? 'N/A' }}</td>
            <td>{{ $item['name'] }}</td>
            <td>{{ $item['quantity'] }}</td>
            <td>${{ number_format($item['unit_price'], 2) }}</td>
            <td>${{ number_format($item['unit_price'] * $item['quantity'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
```

**Templates to update:**
- `resources/views/forms/ssb_form_pdf.blade.php`
- `resources/views/forms/zb_account_opening_pdf.blade.php`
- `resources/views/forms/account_holders_pdf.blade.php`
- `resources/views/forms/pensioner_loan_pdf.blade.php`
- `resources/views/forms/sme_account_opening_pdf.blade.php`

---

## 5. Workstream 4: SSB Application Number Format

### 5.1 Current Format
`SSB{YYYY}{000001}` — e.g., `SSB2026000042`

### 5.2 New Format
`SSBB/M/PP/NNNNN` — **12 alphanumeric characters** (slashes are separators, not counted).

| Part | Length | Description | Example |
|------|--------|-------------|---------|
| `SSBB` | 4 | Fixed prefix | `SSBB` |
| `M` | 1 | First letter of the month applied | `M` (March) |
| `PP` | 2 | Province code | `HR` (Harare) |
| `NNNNN` | 5 | Sequential number, zero-padded | `00042` |
| **Total** | **12** | | `SSBB/M/HR/00042` |

### 5.3 Province Codes

| Province | Code |
|----------|------|
| Harare | HR |
| Bulawayo | BW |
| Manicaland | MC |
| Mashonaland Central | SC |
| Mashonaland East | SE |
| Mashonaland West | SW |
| Masvingo | MV |
| Matabeleland North | MN |
| Matabeleland South | MS |
| Midlands | ML |

### 5.4 Month Codes

Single first letter of the month name. Some months share a letter (Jan/Jun/Jul = `J`, Mar/May = `M`, Apr/Aug = `A`). This is accepted.

| Month | Code | Month | Code |
|-------|------|-------|------|
| January | J | July | J |
| February | F | August | A |
| March | M | September | S |
| April | A | October | O |
| May | M | November | N |
| June | J | December | D |

### 5.5 Code Changes

**Modify `app/Models/ApplicationState.php` → `getApplicationNumberAttribute()`:**

```php
public function getApplicationNumberAttribute(): string
{
    $formData = $this->form_data ?? [];

    if ($this->isSSBApplication($formData)) {
        return $this->generateSSBApplicationNumber($formData);
    }

    // Keep existing logic for non-SSB
    $year = $this->created_at ? $this->created_at->format('Y') : date('Y');
    $id = str_pad($this->id, 6, '0', STR_PAD_LEFT);
    $prefix = $this->isAccountHolderApplication($formData) ? 'ZBAH' : 'ZB';
    return "{$prefix}{$year}{$id}";
}

private function generateSSBApplicationNumber(array $formData): string
{
    $month = $this->created_at ? $this->created_at->format('F') : date('F');
    $monthLetter = strtoupper(substr($month, 0, 1));
    $province = $this->getProvinceCode($formData);
    $sequence = str_pad($this->id, 5, '0', STR_PAD_LEFT);

    return "SSBB/{$monthLetter}/{$province}/{$sequence}";
}

private function getProvinceCode(array $formData): string
{
    $formResponses = $formData['formResponses'] ?? [];
    $province = strtolower(
        $formResponses['province'] ?? $formResponses['city'] ?? ''
    );

    $provinceMap = [
        'harare' => 'HR', 'bulawayo' => 'BW',
        'manicaland' => 'MC', 'mashonaland central' => 'SC',
        'mashonaland east' => 'SE', 'mashonaland west' => 'SW',
        'masvingo' => 'MV', 'matabeleland north' => 'MN',
        'matabeleland south' => 'MS', 'midlands' => 'ML',
    ];

    foreach ($provinceMap as $key => $code) {
        if (str_contains($province, $key)) return $code;
    }
    return 'XX'; // Unknown province
}
```

---

## 6. Complete File Change Manifest

### New Files
| File | Workstream |
|------|------------|
| `app/Enums/ApplicationStatus.php` | 1 |
| `app/Services/ManualWorkflowService.php` | 1 |
| `database/migrations/xxxx_add_application_status_to_application_states.php` | 1 |
| `database/migrations/xxxx_migrate_existing_statuses.php` | 1 |

### Deleted Files
| File | Workstream |
|------|------------|
| `app/Enums/SSBLoanStatus.php` | 1 |
| `app/Enums/ZBLoanStatus.php` | 1 |
| `app/Services/SSBStatusService.php` | 1 |
| `app/Services/ZBStatusService.php` | 1 |

### Modified Files
| File | Workstream |
|------|------------|
| `app/Models/ApplicationState.php` | 1, 4 |
| `app/Services/ApplicationWorkflowService.php` | 1 |
| `app/Services/PurchaseOrderService.php` | 3 |
| `app/Services/PDFGeneratorService.php` | 3 |
| `app/Http/Controllers/ApplicationStatusController.php` | 2 |
| `app/Http/Controllers/ApplicationWizardController.php` | 1 |
| `app/Filament/Resources/ApplicationResource.php` | 1, 2 |
| `app/Observers/ApplicationStateObserver.php` | 1 |
| `routes/api.php` | 1 |
| `resources/views/forms/ssb_form_pdf.blade.php` | 3 |
| `resources/views/forms/zb_account_opening_pdf.blade.php` | 3 |
| `resources/views/forms/account_holders_pdf.blade.php` | 3 |
| `resources/views/forms/pensioner_loan_pdf.blade.php` | 3 |
| `resources/views/forms/sme_account_opening_pdf.blade.php` | 3 |

---

## 7. Verification Plan

### 7.1 Automated Tests (Pest)

| Test File | Tests |
|-----------|-------|
| `tests/Unit/Enums/ApplicationStatusTest.php` | Valid transitions, `isFinalState()`, `requiresClientAction()`, `getStageNumber()` |
| `tests/Unit/Services/ManualWorkflowServiceTest.php` | SSB flow (skip proof), ZB flow (require proof), rejection at each stage, invalid transition errors |
| `tests/Unit/Models/ApplicationStateTest.php` | SSB app number format `SSBB/M/PP/NNNNN`, province codes, month letters, non-SSB formats unchanged |
| `tests/Feature/ApplicationStatusTest.php` | `GET /api/application/status/{ref}` returns correct timeline per stage, ZB shows proof of employment step, SSB omits it |

Run all: `php artisan test`

### 7.2 Manual Testing (Staging)

1. **SSB Submission**: Submit SSB app → appears in Admin at Stage 1 → app number is `SSBB/M/PP/NNNNN` → status page shows "Document Verification"
2. **SSB Full Pipeline**: Stage 1 verify → jumps directly to Stage 2 (no proof gate) → Stage 2 approve → Stage 3 approve → PDF generated with product list → PO in Stores → delivery created
3. **ZB/Pensioner/RDC Flow**: Stage 1 verify → status shows "Awaiting Proof of Employment" → client uploads proof → Stage 2 → Stage 3 → approval
4. **PDF Check**: Open generated PDF → verify "Package Contents" table shows all items with product codes, quantities, unit prices

---

## 8. Implementation Order

| Phase | Work | Depends On |
|-------|------|------------|
| **1** | Create `ApplicationStatus` enum + DB migration | — |
| **2** | Create `ManualWorkflowService` + update `ApplicationState` model | Phase 1 |
| **3** | Update `ApplicationStatusController` (client status sync) | Phase 2 |
| **4** | Update `ApplicationResource` Filament (admin actions) | Phase 2 |
| **5** | Update `ApplicationWorkflowService` + Observer (approval effects) | Phase 2 |
| **6** | Update PDF templates + `PDFGeneratorService` (product list) | Independent |
| **7** | SSB application number format change | Independent |
| **8** | Delete old enums + services (`SSBLoanStatus`, `ZBLoanStatus`, etc.) | After Phase 2 tested |
| **9** | Data migration for existing records | Phase 1 |
| **10** | Write tests + end-to-end verification | All above |
