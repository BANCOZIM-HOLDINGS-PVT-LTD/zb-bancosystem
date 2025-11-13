# User Experience Flow Documentation

This document describes how the application differentiates between new and returning users, providing tailored experiences for each.

## Overview

The system now provides **different landing experiences** based on whether a user is new to the platform or is returning with existing applications.

## User Types & Flow

### 🆕 New Users (No Applications)

**Definition:** Users who have just registered or logged in but have not yet submitted any applications.

**Landing Experience:**
- After login/registration, users see the welcome page with **2 options**:
  1. **Apply for Personal Products** - Credit for phones, furniture, appliances, etc.
  2. **Apply for Small Business Starter Pack (MicroBiz)** - Business startup assistance

**Flow:**
```
Register/Login → OTP Verification → Home Page → Apply Options Only
```

**What They See:**
- Application options only
- No tracking options visible
- Clear path to start their first application

---

### 🔄 Returning Users (Has Applications)

**Definition:** Users who have previously submitted at least one application (tracked by their National ID or phone number).

**Landing Experience:**
- After login, users see the welcome page with **2 options**:
  1. **Track your application** - Check application status and progress
  2. **Track your delivery** - Monitor product/equipment delivery

**Flow:**
```
Login → OTP Verification → Home Page → Tracking Options Only
```

**What They See:**
- Tracking options only
- No application options visible (prevents duplicate applications)
- Clear path to monitor existing applications

---

## Technical Implementation

### Backend Logic

**WelcomeController** (`app/Http/Controllers/WelcomeController.php`):
```php
// Checks if authenticated user has any applications
$hasApplications = ApplicationState::where(function($query) use ($user) {
    if ($user->phone) {
        $query->orWhere('user_identifier', $user->phone);
    }
    if ($user->national_id) {
        $query->orWhere('user_identifier', $user->national_id);
    }
})->exists();

// Pass this to the frontend
return Inertia::render('welcome', [
    'hasApplications' => $hasApplications,
]);
```

### Frontend Logic

**Welcome Page** (`resources/js/pages/welcome.tsx`):
```typescript
// Define all possible intents with user targeting
const allIntents = [
    {
        id: 'hirePurchase',
        name: 'Apply for Personal Products',
        forNewUsers: true,  // Only for new users
    },
    {
        id: 'microBiz',
        name: 'Apply for Small Business Starter Pack (MicroBiz)',
        forNewUsers: true,  // Only for new users
    },
    {
        id: 'checkStatus',
        name: 'Track your application',
        forNewUsers: false,  // Only for returning users
    },
    {
        id: 'trackDelivery',
        name: 'Track your delivery',
        forNewUsers: false,  // Only for returning users
    }
];

// Filter intents based on user status
const intents = useMemo(() => {
    if (hasApplications) {
        // Returning user - show only tracking options
        return allIntents.filter(intent => !intent.forNewUsers);
    } else {
        // New user - show only application options
        return allIntents.filter(intent => intent.forNewUsers);
    }
}, [hasApplications]);
```

---

## Authentication Flow

### New User Registration
1. User visits `/client/register`
2. Enters National ID and phone number
3. Receives OTP via SMS (Twilio)
4. Verifies OTP
5. Redirected to home page
6. Sees **application options only** (no applications exist yet)

### Returning User Login
1. User visits `/client/login`
2. Enters National ID
3. Receives OTP via SMS
4. Verifies OTP
5. Redirected to home page
6. Sees **tracking options only** (applications exist)

---

## Routes

### Authentication Routes
- `GET /client/register` - Registration page
- `POST /client/register` - Process registration
- `GET /client/verify-otp` - OTP verification (registration)
- `POST /client/verify-otp` - Verify OTP (registration)
- `GET /client/login` - Login page
- `POST /client/login` - Process login
- `GET /client/login/verify-otp` - OTP verification (login)
- `POST /client/login/verify-otp` - Verify OTP (login)

### Main Application Routes
- `GET /` - Home/Welcome page (shows different content based on user status)
- `GET /application` - Application wizard (for new users)
- `GET /application/status` - Track application (for returning users)
- `GET /delivery/tracking` - Track delivery (for returning users)

---

## Database Schema

### Users Table Fields
```sql
- national_id (string, unique) - Zimbabwe National ID
- phone (string, unique) - Zimbabwe phone number
- phone_verified (boolean) - Phone verification status
- phone_verified_at (timestamp) - When phone was verified
- otp_code (string, hidden) - Current OTP code
- otp_expires_at (timestamp) - OTP expiration time
```

### ApplicationState Table
```sql
- session_id (string) - Unique session identifier
- user_identifier (string) - User's phone or National ID
- current_step (string) - Current application step
- form_data (json) - Application data
- metadata (json) - Additional metadata
```

---

## User Experience Benefits

### For New Users:
✅ Simplified onboarding - only see relevant application options
✅ Clear call-to-action for starting their journey
✅ No confusion from tracking options they don't need yet
✅ Streamlined path to their first application

### For Returning Users:
✅ Immediate access to what matters - tracking their applications
✅ No temptation to create duplicate applications
✅ Focus on monitoring existing requests
✅ Clear visibility into application and delivery status

---

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Unauthenticated User                      │
│                   (Visits Home Page)                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ├──────────────┬──────────────┐
                      │              │              │
                ┌─────▼────┐   ┌────▼─────┐  ┌────▼─────┐
                │ Register │   │  Login   │  │ Continue │
                │ (New)    │   │(Existing)│  │ Browsing │
                └─────┬────┘   └────┬─────┘  └──────────┘
                      │              │
                ┌─────▼──────────────▼─────┐
                │   OTP Verification        │
                └─────┬────────────────────┘
                      │
                ┌─────▼────────────────────┐
                │ Authenticated User        │
                │ Check: hasApplications?   │
                └─────┬────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
  ┌─────▼─────┐              ┌─────▼─────┐
  │ New User  │              │ Returning │
  │ (No Apps) │              │   User    │
  └─────┬─────┘              └─────┬─────┘
        │                           │
  ┌─────▼─────────────┐    ┌───────▼──────────┐
  │ Show Application  │    │ Show Tracking    │
  │ Options:          │    │ Options:         │
  │ • Personal Prod.  │    │ • Track App      │
  │ • MicroBiz        │    │ • Track Delivery │
  └───────────────────┘    └──────────────────┘
```

---

## Testing Checklist

### New User Flow
- [ ] Register with valid National ID and phone
- [ ] Receive and verify OTP
- [ ] Redirected to home page
- [ ] See **only** application options (Personal Products, MicroBiz)
- [ ] Do **not** see tracking options
- [ ] Can successfully start an application

### Returning User Flow
- [ ] Login with existing National ID
- [ ] Receive and verify OTP
- [ ] Redirected to home page
- [ ] See **only** tracking options (Track Application, Track Delivery)
- [ ] Do **not** see application options
- [ ] Can successfully track applications

### Guest User Flow
- [ ] Visit home page without authentication
- [ ] See Login and Register buttons in header
- [ ] Can navigate to both authentication pages
- [ ] Adala welcome screen shows properly

---

## Configuration

No additional configuration needed. The system automatically:
- Checks user's application history on each home page visit
- Filters available options based on application count
- Provides appropriate user experience without any manual setup

---

## Maintenance Notes

### Adding New Application Types
If you need to add a new application type for new users:

1. Add to `allIntents` in `welcome.tsx`:
```typescript
{
    id: 'newType',
    name: 'New Application Type',
    icon: YourIcon,
    description: 'Description here',
    route: 'your.route',
    forNewUsers: true,  // Set to true for new users
}
```

### Adding New Tracking Options
If you need to add a new tracking option for returning users:

1. Add to `allIntents` in `welcome.tsx`:
```typescript
{
    id: 'newTracking',
    name: 'New Tracking Option',
    icon: YourIcon,
    description: 'Description here',
    route: 'your.route',
    forNewUsers: false,  // Set to false for returning users
}
```

---

## Support

For questions or issues with user experience flow:
1. Check `WelcomeController.php` for backend logic
2. Review `welcome.tsx` for frontend filtering
3. Verify `ApplicationState` records are being created properly
4. Ensure user authentication is working correctly