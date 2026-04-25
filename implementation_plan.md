# 🏦 BancoSystem — Updated Implementation Plan

> **Date:** 23 April 2026
> **Status:** Sprint 1 Complete | Sprint 2 In Progress

---

## ✅ Completed Milestones

### 🏢 1. SME Application Process Flow (Sprint 1)
- [x] **Frontend:** Dedicated `smeBiz` intent detection and routing.
- [x] **Wizard:** Replaced Employer Selection with Company Type selection for SMEs.
- [x] **Admin:** Created `SMEApplicationResource` and `SmeLoanResource` for specialized management.
- [x] **Logic:** Implemented SME-specific validation and PDF generation logic.

### 💰 2. Cash Payment Flow & Unified Tracking (Sprint 2 Priority)
- [x] **UX:** Added "Express Checkout" for cash payments on the Welcome page.
- [x] **Wizard:** Streamlined 4-step flow: Product -> Delivery -> Summary -> Payment.
- [x] **Payment:** Full Paynow integration (EcoCash/Card) for total order amounts.
- [x] **Receipts:** Automated PDF receipt generation and secure download for customers.
- [x] **Admin:** Created a dedicated **Cash Orders** resource in ZbAdmin.

### 🔄 3. SSB Integration Automation (Sprint 1 Priority)
- [x] **API:** Full integration with the Salary Deductions Gateway API.
- [x] **Auth:** Automated JWT Bearer Token management with caching.
- [x] **Submission:** Added "Submit to SSB" manual action in Filament.
- [x] **Sync:** Created `ssb:sync-status` console command for background status updates.

---

## 📋 Remaining Tasks & Next Steps

### 📧 4. Dandemutande Email Integration (Sprint 2 - NEXT)
**🎯 Goal:** Switch from SMS-only to unified Email + SMS notifications.
- [ ] Configure Dandemutande SMTP in `.env`.
- [ ] Create `DandemutandeMailService.php`.
- [ ] Build HTML templates for: Application Received, Status Updated, and Payment Receipt.
- [ ] Implement event listeners to trigger emails automatically.

### 🔔 5. Payment Reminders & Incomplete Apps (Sprint 3)
**🎯 Goal:** Re-engage users who drop off or forget to pay deposits.
- [ ] Automated SMS/Email reminders for pending payments (3, 7, 14 days).
- [ ] "Resume Application" deep links with pre-filled sessions.
- [ ] Funnel analytics widget to track drop-off steps.

### 📦 6. SME Booster Packages (Sprint 3)
- [x] Models and API are ready.
- [ ] **Next:** Update frontend to fully utilize the tiered selection UI for Booster products.

### 📊 7. Accounting & Stores Sync (Sprint 5)
- [ ] Real-time financial transaction logging in Accounting panel.
- [ ] Automated Purchase Order (PO) creation for ALL approved applications (Cash + Credit).
- [ ] Real-time inventory deduction upon order fulfillment.

---

## 🚀 Execution Strategy for Next Session
The foundation is now very strong. The next logical step is to **finalize communications**.
1. **Email Setup:** Start by wiring up the Dandemutande SMTP.
2. **Automated Jobs:** Schedule the SSB sync and Payment reminders in the system crontab.
