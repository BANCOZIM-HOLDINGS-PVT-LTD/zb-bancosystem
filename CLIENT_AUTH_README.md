# Client Authentication System

This document explains the new client authentication system using National ID and OTP verification via Twilio SMS.

## Overview

The client authentication system allows users to:
1. **Register** with their Zimbabwe National ID and phone number
2. **Verify** their phone number via OTP (One-Time Password) sent via SMS
3. **Login** using only their National ID (OTP sent for verification)

## Features

- ✅ National ID-based authentication (Zimbabwe format: `63-123456A12`)
- ✅ Phone number verification via Twilio SMS OTP
- ✅ 6-digit OTP codes with 10-minute expiration
- ✅ Resend OTP functionality with cooldown (60 seconds)
- ✅ Automatic OTP input distribution for paste support
- ✅ Masked phone numbers for privacy
- ✅ Session management for pending registrations

## Routes

### Client Registration
- `GET /client/register` - Show registration form
- `POST /client/register` - Process registration (sends OTP)
- `GET /client/verify-otp` - Show OTP verification form
- `POST /client/verify-otp` - Verify OTP and complete registration
- `POST /client/resend-otp` - Resend OTP code

### Client Login
- `GET /client/login` - Show login form
- `POST /client/login` - Process login (sends OTP)
- `GET /client/login/verify-otp` - Show OTP verification form
- `POST /client/login/verify-otp` - Verify OTP and log in
- `POST /client/login/resend-otp` - Resend OTP code

### Admin Authentication (Existing)
- `GET /register` - Admin registration (email + password)
- `GET /login` - Admin login (email + password)

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
TWILIO_ACCOUNT_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM=+1234567890
```

### Getting Twilio Credentials

1. Sign up at [https://www.twilio.com](https://www.twilio.com)
2. Get your Account SID and Auth Token from the Console Dashboard
3. Purchase a phone number or use a trial number
4. Add the credentials to your `.env` file

## Database Schema

### Users Table - New Fields

The migration adds the following fields to the `users` table:

```php
- national_id (string, unique, nullable) - Zimbabwe National ID
- phone (string, unique, nullable) - Zimbabwe phone number (+263...)
- phone_verified (boolean, default: false) - Phone verification status
- phone_verified_at (timestamp, nullable) - When phone was verified
- otp_code (string, nullable) - Current OTP code (hidden)
- otp_expires_at (timestamp, nullable) - OTP expiration time
```

## User Model Methods

### `generateOtp(): string`
Generates a 6-digit OTP code and stores it with a 10-minute expiration.

```php
$otp = $user->generateOtp();
```

### `verifyOtp(string $otp): bool`
Verifies the OTP code and marks the phone as verified.

```php
$verified = $user->verifyOtp('123456');
```

## OTP Service

The `OtpService` handles all Twilio SMS operations:

### `sendOtp(User $user): bool`
Sends an OTP code to the user's phone number.

```php
$otpService = new OtpService();
$sent = $otpService->sendOtp($user);
```

### `verifyOtp(User $user, string $otp): bool`
Verifies the OTP code.

```php
$verified = $otpService->verifyOtp($user, '123456');
```

### `resendOtp(User $user): bool`
Resends the OTP with rate limiting (1 minute between sends).

```php
$sent = $otpService->resendOtp($user);
```

## Flow Diagrams

### Registration Flow

```
1. User enters National ID and Phone Number
   ↓
2. System validates format and uniqueness
   ↓
3. System creates user record
   ↓
4. System generates and sends OTP via SMS
   ↓
5. User enters OTP code
   ↓
6. System verifies OTP
   ↓
7. User is logged in and redirected to application wizard
```

### Login Flow

```
1. User enters National ID
   ↓
2. System finds user record
   ↓
3. System generates and sends OTP via SMS
   ↓
4. User enters OTP code
   ↓
5. System verifies OTP
   ↓
6. User is logged in and redirected to dashboard
```

## Validation Rules

### National ID Format
- Pattern: `XX-XXXXXXXX` where X is alphanumeric
- Example: `63-123456A12`
- Regex: `/^[0-9]{2}-[0-9]{6,7}[A-Z][0-9]{2}$/`

### Phone Number Format
- Pattern: `+263XXXXXXXXX` (Zimbabwe country code)
- Example: `+263771234567`
- Regex: `/^\+263[0-9]{9}$/`

## Security Features

1. **OTP Expiration**: All OTP codes expire after 10 minutes
2. **Rate Limiting**: Resend OTP is limited to once per minute
3. **Session Management**: Pending registrations are stored in session
4. **Hidden OTP Storage**: OTP codes are hidden from serialization
5. **Phone Masking**: Phone numbers are masked in UI (`+263****4567`)

## Frontend Components

### Client Register Page
Path: `resources/js/pages/auth/client-register.tsx`
- National ID input with auto-formatting
- Phone number input with validation
- Zimbabwean format enforcement

### Client Login Page
Path: `resources/js/pages/auth/client-login.tsx`
- National ID input with auto-formatting
- Simple, streamlined login experience

### OTP Verification Pages
- `resources/js/pages/auth/verify-otp.tsx` (Registration)
- `resources/js/pages/auth/verify-login-otp.tsx` (Login)
- 6-digit OTP input with auto-focus
- Paste support for OTP codes
- Resend OTP with countdown timer
- Masked phone number display

## Testing

### Manual Testing Checklist

#### Registration
- [ ] Can register with valid National ID and phone number
- [ ] Cannot register with invalid National ID format
- [ ] Cannot register with invalid phone format
- [ ] Cannot register with duplicate National ID
- [ ] Cannot register with duplicate phone number
- [ ] OTP is sent successfully
- [ ] OTP can be verified
- [ ] OTP expires after 10 minutes
- [ ] Can resend OTP
- [ ] Cannot resend OTP within 1 minute

#### Login
- [ ] Can login with valid National ID
- [ ] Cannot login with unregistered National ID
- [ ] OTP is sent successfully
- [ ] OTP can be verified
- [ ] Cannot use expired OTP
- [ ] Can resend OTP
- [ ] Successfully redirected after login

## Troubleshooting

### OTP Not Received

1. Check Twilio credentials in `.env`
2. Verify phone number format is correct
3. Check Twilio account balance
4. Review Laravel logs: `storage/logs/laravel.log`
5. Check Twilio console for SMS delivery status

### Invalid National ID Format

- Ensure format is `XX-XXXXXXXX`
- Example: `63-123456A12`
- Auto-formatting is applied on input

### Session Expired Errors

- This occurs if the user waits too long between steps
- Simply restart the registration/login process

## Future Enhancements

- [ ] Add rate limiting for login attempts
- [ ] Implement account lockout after failed OTP attempts
- [ ] Add SMS cost tracking and alerts
- [ ] Support for multiple phone numbers per user
- [ ] Two-factor authentication option
- [ ] Backup authentication methods (email)

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Review Twilio console for SMS delivery issues
- Verify environment variables are set correctly
- Ensure migrations have been run: `php artisan migrate`