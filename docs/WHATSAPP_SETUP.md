# WhatsApp Integration Setup Guide

## Overview

This application provides **complete feature parity** between WhatsApp and web interfaces for loan applications. Users can:
- **Complete entire application on WhatsApp** - Full catalog browsing, form filling, and submission
- **Complete entire application on web** - Full web experience with all features
- **Switch between channels seamlessly** - Save progress and resume on any device
- **Get resume codes during long forms** - Prevent loss of progress if phone/PC becomes unavailable

## Prerequisites

1. **Twilio Account**: You need a Twilio account with WhatsApp Business API access
2. **WhatsApp Business Profile**: Approved WhatsApp Business profile
3. **Environment Variables**: Configure Twilio credentials in your .env file

## Environment Configuration

Add these variables to your `.env` file:

```env
TWILIO_ACCOUNT_SID=ACcc8a7b869428e05081183eeba60933dd
TWILIO_AUTH_TOKEN=a5ac8fc29bb36f3663b53bf391c4266f
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

## Webhook Setup

1. **Configure Twilio Webhook**: In your Twilio Console, set the webhook URL to:
   ```
   https://your-domain.com/api/webhooks/whatsapp
   ```

2. **Status Callback URL** (Optional): For delivery receipts:
   ```
   https://your-domain.com/api/webhooks/whatsapp/status
   ```

## Testing the Integration

### 1. Start a WhatsApp Conversation

Send a message to your Twilio WhatsApp number:
- "start" or "begin" - Starts a new application
- "hello" or "hi" - Shows welcome message

### 2. Test Cross-Channel Functionality

1. **WhatsApp to Web**:
   - Start application on WhatsApp
   - When prompted for product selection, click the provided link
   - Continue on web with existing data

2. **Web to WhatsApp**:
   - Start application on web
   - Get resume code from the application
   - Send "resume XXXXXX" to WhatsApp bot

### 3. Complete Application Flow on WhatsApp

1. **Language Selection**: Choose English, Shona, or Ndebele
2. **Intent Selection**: Hire Purchase Credit or Micro Biz Loan
3. **Employer Selection**: Choose from 9 employer types
4. **Account Verification**: Confirm if you have a ZB Bank account
5. **Product Category Selection**: Browse 5 main categories (Agriculture, Retail, Manufacturing, Services, Technology)
6. **Subcategory Selection**: Navigate through specific subcategories
7. **Business Type Selection**: Choose exact business with pricing
8. **Scale Selection**: Select business scale with final pricing
9. **Form Filling**: Complete all required fields step-by-step
10. **Application Submission**: Submit complete application via WhatsApp

**Navigation Features**:
- Type 'back' to go to previous step
- Type 'save' during form filling to get resume code
- Type 'skip' for optional fields
- Full validation on all form fields

## Commands Available on WhatsApp

### Basic Commands
- `start` - Begin new application
- `resume XXXXXX` - Continue application from web (where XXXXXX is 6-digit code)
- `hello` / `hi` - Show welcome message

### Navigation Commands
- `back` - Go to previous step in any part of the application
- `save` - Save progress during form filling and get resume code
- `skip` - Skip optional form fields
- `continue` - Continue form filling after saving

### Form Commands
- Numbers `1-9` - Select options during category/subcategory browsing
- Text responses - Answer form questions
- Validation errors provide specific guidance for corrections

## Database Schema

The integration uses two main tables:

### application_states
- Stores application state across channels
- Includes session_id, channel, current_step, form_data
- Supports metadata for resume codes and cross-channel linking

### state_transitions
- Logs all state transitions for audit trail
- Tracks user journey across channels

## Feature Parity Details

### Complete Product Catalog
- **5 Main Categories**: Agriculture, Retail, Manufacturing, Services, Technology
- **Multiple Subcategories**: Each category has relevant subcategories
- **Business Types**: Specific businesses with base pricing
- **Scale Options**: Multiple scale options with pricing multipliers
- **Price Calculation**: Real-time price calculation during selection

### Form Filling Capabilities
- **Dynamic Forms**: Different forms based on employer type and account status
- **Field Validation**: Email, phone, number, date, and text validation
- **Progress Tracking**: "Question X of Y" progress indicators
- **Optional Fields**: Skip functionality for non-required fields
- **Save/Resume**: Save progress at any point during form filling

### Cross-Channel Resume Codes
- **Generation**: Codes generated when switching channels or saving progress
- **Context-Aware Expiration**: Intelligent expiration based on application stage
  - **Form Filling**: 2-4 hours (users need time to complete applications)
  - **Product Selection**: 1 hour (less sensitive data)
  - **Early Stages**: 30 minutes (basic security)
  - **Completed Applications**: 15 minutes (view-only access)
- **Usage**: Works for both WhatsApp → Web and Web → WhatsApp
- **Validation**: Secure validation prevents unauthorized access

## Security Features

1. **Webhook Signature Validation**: All incoming webhooks are validated using Twilio's signature
2. **Resume Code Expiration**: Resume codes expire after 30 minutes
3. **Session TTL**: WhatsApp sessions last 7 days, web sessions last 24 hours
4. **Input Validation**: All form inputs validated before storage
5. **SQL Injection Prevention**: Parameterized queries and ORM usage

## Troubleshooting

### Common Issues

1. **Webhook Not Receiving Messages**:
   - Verify webhook URL is accessible from internet
   - Check Twilio webhook configuration
   - Verify HTTPS is enabled

2. **Resume Codes Not Working**:
   - Codes expire after 30 minutes
   - Ensure codes are entered exactly as provided
   - Check database for metadata->resume_code field

3. **Cross-Channel Data Not Syncing**:
   - Verify StateManager service is properly injected
   - Check application_states table for linked sessions
   - Review logs for any merge errors

### Debug Mode

Enable debug logging in your .env:
```env
LOG_LEVEL=debug
```

Check logs at:
- `storage/logs/laravel.log`
- WhatsApp webhook calls are logged with prefix "WhatsApp"

## File Structure

```
app/
├── Http/Controllers/
│   └── WhatsAppWebhookController.php      # Handles incoming webhooks
├── Services/
│   ├── TwilioWhatsAppService.php          # Twilio API integration
│   ├── WhatsAppConversationService.php    # Conversation flow logic
│   └── StateManager.php                   # Cross-channel state management
└── Models/
    ├── ApplicationState.php               # Main state storage
    └── StateTransition.php                # Audit trail

routes/
├── api.php                                # Webhook routes
└── web.php                                # Resume functionality

resources/js/
└── components/ApplicationWizard/          # Web interface components
```

## Production Deployment

1. **HTTPS Required**: Twilio webhooks require HTTPS
2. **Database Migrations**: Run migrations for state tables
3. **Queue Workers**: Consider using queues for WhatsApp message processing
4. **Rate Limiting**: Implement rate limiting for webhook endpoints
5. **Monitoring**: Set up monitoring for webhook failures

## Support

For issues with the WhatsApp integration:
1. Check Twilio Console for webhook delivery status
2. Review application logs for detailed error messages
3. Verify environment variables are correctly set
4. Test webhook endpoint manually with curl