# BancoSystem Enhanced Program Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Current Application Flow](#current-application-flow)
3. [Enhanced Features Specification](#enhanced-features-specification)
4. [Technical Architecture](#technical-architecture)
5. [Implementation Roadmap](#implementation-roadmap)
6. [Resource Files](#resource-files)
7. [API Specifications](#api-specifications)
8. [Database Schema](#database-schema)
9. [Security Considerations](#security-considerations)

## Project Overview

BancoSystem is a multi-step loan and account application system that guides users through a conversational flow to determine their eligibility and collect necessary information for various financial products.

### Current Stack
- **Frontend**: React 18, TypeScript, Tailwind CSS
- **Backend**: Laravel (PHP)
- **Database**: MySQL
- **UI Components**: Custom components with Lucide icons

## Current Application Flow

### Step-by-Step User Journey

1. **Language Selection**
   - English, Shona, Ndebele
   - Sets UI language preference

2. **Intent Selection**
   - Apply for Hire Purchase Credit
   - Apply for Micro Biz
   - Check Application Status
   - Track Delivery

3. **Employer Selection**
   - Government categories (SSB, ZAPPA, Pension)
   - Private sector (Parastatals, Corporates)
   - Entrepreneurs
   - Other

4. **Product Selection**
   - Dynamic category loading
   - Credit term selection
   - Monthly installment calculation

5. **Account Verification**
   - Existing account check
   - New account creation option

6. **Application Summary**
   - Review selections
   - Confirm and proceed

7. **Form Deployment**
   - Dynamic JSON form loading
   - Pre-populated fields
   - Document upload

## Enhanced Features Specification

### 1. WhatsApp Bot Integration

#### Architecture
```
WhatsApp User → WhatsApp Business API → Webhook Handler → Application State Manager → Response Generator
```

#### Implementation Components

##### A. WhatsApp Webhook Handler
```php
// app/Http/Controllers/WhatsAppController.php
class WhatsAppController extends Controller
{
    public function webhook(Request $request)
    {
        // Verify webhook token
        // Process incoming message
        // Retrieve user state
        // Generate appropriate response
        // Send response via WhatsApp API
    }
}
```

##### B. Message Parser
```php
// app/Services/WhatsApp/MessageParser.php
class MessageParser
{
    public function parseIntent($message, $currentState)
    {
        // NLP for intent detection
        // Map to application flow steps
        // Return next action
    }
}
```

##### C. Response Templates
```json
// resources/whatsapp/templates/language_selection.json
{
  "type": "interactive",
  "body": {
    "text": "Hi! I am Adala, your digital assistant. Please select your preferred language:"
  },
  "action": {
    "buttons": [
      {"id": "lang_en", "title": "English"},
      {"id": "lang_sn", "title": "Shona"},
      {"id": "lang_nd", "title": "Ndebele"}
    ]
  }
}
```

### 2. Cross-Channel State Persistence

#### State Management System

##### A. Application State Schema
```sql
CREATE TABLE application_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    channel ENUM('web', 'whatsapp', 'ussd', 'mobile_app') NOT NULL,
    user_identifier VARCHAR(255) NOT NULL, -- phone number, email, or device ID
    current_step VARCHAR(50) NOT NULL,
    form_data JSON NOT NULL,
    metadata JSON,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_channel (user_identifier, channel),
    INDEX idx_session (session_id),
    INDEX idx_expires (expires_at)
);

CREATE TABLE state_transitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    state_id BIGINT UNSIGNED,
    from_step VARCHAR(50),
    to_step VARCHAR(50),
    channel VARCHAR(20),
    transition_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES application_states(id)
);
```

##### B. State Manager Service
```php
// app/Services/StateManager.php
namespace App\Services;

class StateManager
{
    public function saveState($sessionId, $channel, $userIdentifier, $step, $data)
    {
        // Save or update application state
        // Log state transition
        // Set expiration based on channel
    }
    
    public function retrieveState($userIdentifier, $channel = null)
    {
        // Get most recent state for user
        // Handle cross-channel resume
    }
    
    public function mergeStates($primaryState, $secondaryState)
    {
        // Intelligent merge of states from different channels
    }
}
```

##### C. Session Linking
```php
// app/Services/SessionLinkingService.php
class SessionLinkingService
{
    public function generateLinkCode($sessionId)
    {
        // Generate 6-digit OTP for session linking
    }
    
    public function linkSessions($code, $newChannel, $userIdentifier)
    {
        // Verify code and link sessions across channels
    }
}
```

### 3. Enhanced Resource Files

#### A. Parastatals Data Structure
```json
// resources/js/Components/CreditFlow/data/parastatals.json
{
  "zimbabwean_parastatals": [
    {
      "id": "zesa",
      "name": "Zimbabwe Electricity Supply Authority",
      "acronym": "ZESA",
      "category": "utility",
      "employee_code_format": "ZESA-XXXXX"
    },
    {
      "id": "nrz",
      "name": "National Railways of Zimbabwe",
      "acronym": "NRZ",
      "category": "transport",
      "employee_code_format": "NRZ-XXXXXX"
    }
    // ... more parastatals
  ]
}
```

#### B. Corporations Data Structure
```json
// resources/js/Components/CreditFlow/data/corporations.json
{
  "zimbabwean_large_corporations": [
    {
      "id": "econet",
      "name": "Econet Wireless Zimbabwe",
      "listed": true,
      "exchange": "ZSE",
      "sector": "telecommunications",
      "employee_code_format": "ECO-XXXXXX"
    },
    {
      "id": "delta",
      "name": "Delta Corporation",
      "listed": true,
      "exchange": "ZSE",
      "sector": "beverages",
      "employee_code_format": "DELTA-XXXXX"
    }
    // ... more corporations
  ]
}
```

#### C. Product Categories Enhanced
```json
// resources/js/Components/CreditFlow/data/products.json
{
  "categories": {
    "hire_purchase": {
      "electronics": {
        "id": "electronics",
        "name": "Electronics",
        "icon": "Smartphone",
        "subcategories": [
          {
            "id": "phones",
            "name": "Mobile Phones",
            "products": [
              {
                "id": "iphone_15",
                "name": "iPhone 15",
                "price": 999,
                "whatsapp_media": "https://cdn.example.com/iphone15.jpg",
                "description": "Latest iPhone with advanced features"
              }
            ]
          }
        ]
      }
    }
  }
}
```

### 4. API Specifications

#### A. State Management APIs

##### Save State
```
POST /api/v1/state/save
{
  "session_id": "web_123456",
  "channel": "web",
  "user_identifier": "+263771234567",
  "current_step": "employer_selection",
  "form_data": {
    "language": "English",
    "intent": "hirePurchase",
    "employer": "Parastatal - ZESA"
  }
}
```

##### Retrieve State
```
GET /api/v1/state/retrieve?user=+263771234567&channel=whatsapp
Response:
{
  "session_id": "wa_789012",
  "current_step": "product_selection",
  "form_data": {...},
  "can_resume": true,
  "expires_in": 3600
}
```

##### Link Sessions
```
POST /api/v1/state/link
{
  "link_code": "123456",
  "new_channel": "whatsapp",
  "user_identifier": "+263771234567"
}
```

#### B. WhatsApp Integration APIs

##### Send Message
```
POST /api/v1/whatsapp/send
{
  "to": "+263771234567",
  "type": "interactive",
  "template": "product_selection",
  "data": {
    "products": [...]
  }
}
```

##### Process Webhook
```
POST /api/v1/whatsapp/webhook
{
  "from": "+263771234567",
  "message": {
    "type": "button_reply",
    "button_reply": {
      "id": "select_product_123",
      "title": "iPhone 15"
    }
  }
}
```

### 5. Implementation Roadmap

#### Phase 1: State Persistence (Week 1-2)
1. Create database schema for state management
2. Implement StateManager service
3. Add state saving to existing React flow
4. Create state retrieval endpoints
5. Add "Resume Application" feature to web

#### Phase 2: WhatsApp Foundation (Week 3-4)
1. Set up WhatsApp Business API account
2. Create webhook handler
3. Implement message parser
4. Create response templates
5. Test basic conversation flow

#### Phase 3: Cross-Channel Integration (Week 5-6)
1. Implement session linking service
2. Create OTP generation/verification
3. Add channel switching UI
4. Test cross-channel resume
5. Handle edge cases

#### Phase 4: Enhanced Features (Week 7-8)
1. Add rich media support for WhatsApp
2. Implement progress indicators
3. Create admin dashboard for monitoring
4. Add analytics tracking
5. Performance optimization

### 6. Frontend Enhancements

#### A. Resume Application Component
```tsx
// resources/js/Components/ResumeApplication.tsx
import React, { useState } from 'react';

const ResumeApplication: React.FC = () => {
  const [identifier, setIdentifier] = useState('');
  const [loading, setLoading] = useState(false);
  
  const handleResume = async () => {
    setLoading(true);
    const response = await fetch(`/api/v1/state/retrieve?user=${identifier}`);
    const data = await response.json();
    
    if (data.can_resume) {
      // Resume application flow
      window.location.href = `/resume/${data.session_id}`;
    }
  };
  
  return (
    <div className="p-6 bg-white rounded-lg shadow">
      <h3 className="text-lg font-semibold mb-4">Resume Your Application</h3>
      <input
        type="text"
        placeholder="Enter your phone number or reference"
        value={identifier}
        onChange={(e) => setIdentifier(e.target.value)}
        className="w-full p-2 border rounded"
      />
      <button
        onClick={handleResume}
        disabled={loading}
        className="mt-4 w-full bg-emerald-600 text-white p-2 rounded"
      >
        {loading ? 'Checking...' : 'Resume Application'}
      </button>
    </div>
  );
};
```

#### B. Channel Switch Component
```tsx
// resources/js/Components/ChannelSwitch.tsx
const ChannelSwitch: React.FC = () => {
  const [showQR, setShowQR] = useState(false);
  const [linkCode, setLinkCode] = useState('');
  
  const generateLinkCode = async () => {
    const response = await fetch('/api/v1/state/link/generate', {
      method: 'POST',
      body: JSON.stringify({ session_id: currentSessionId })
    });
    const data = await response.json();
    setLinkCode(data.code);
    setShowQR(true);
  };
  
  return (
    <div className="fixed bottom-4 right-4">
      <button
        onClick={generateLinkCode}
        className="bg-green-500 text-white p-3 rounded-full shadow-lg"
      >
        <WhatsAppIcon />
      </button>
      {showQR && (
        <div className="absolute bottom-16 right-0 bg-white p-4 rounded-lg shadow-xl">
          <p className="text-sm mb-2">Continue on WhatsApp</p>
          <p className="text-2xl font-bold">{linkCode}</p>
          <p className="text-xs mt-2">Send this code to our WhatsApp bot</p>
        </div>
      )}
    </div>
  );
};
```

### 7. Backend Services

#### A. WhatsApp Service Provider
```php
// app/Providers/WhatsAppServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsApp\Client;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                config('services.whatsapp.token'),
                config('services.whatsapp.phone_id')
            );
        });
    }
}
```

#### B. State Cleanup Job
```php
// app/Jobs/CleanupExpiredStates.php
namespace App\Jobs;

class CleanupExpiredStates implements ShouldQueue
{
    public function handle()
    {
        ApplicationState::where('expires_at', '<', now())
            ->whereNotIn('channel', ['whatsapp']) // Keep WhatsApp states longer
            ->delete();
    }
}
```

### 8. Security Considerations

#### A. Data Encryption
- Encrypt sensitive form data in database
- Use field-level encryption for PII
- Implement key rotation strategy

#### B. Authentication
- Phone number verification via OTP
- Session tokens with expiration
- Rate limiting on all endpoints

#### C. Privacy
- Data retention policies
- User consent tracking
- Right to deletion implementation

### 9. Monitoring and Analytics

#### A. Application Metrics
```sql
CREATE TABLE application_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_type VARCHAR(50),
    channel VARCHAR(20),
    step VARCHAR(50),
    value DECIMAL(10,2),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_type (metric_type, created_at)
);
```

#### B. Dashboard Components
- Real-time application status
- Channel distribution
- Completion rates by step
- Error tracking
- Performance metrics

### 10. Testing Strategy

#### A. Unit Tests
- State management logic
- Message parsing
- Form validation
- API endpoints

#### B. Integration Tests
- Cross-channel flow
- WhatsApp webhook handling
- Database transactions
- External API calls

#### C. E2E Tests
- Complete application flow
- Resume functionality
- Channel switching
- Error scenarios

## Deployment Considerations

### Infrastructure Requirements
- Redis for session caching
- Queue workers for WhatsApp messages
- SSL certificates for webhooks
- CDN for media files

### Environment Variables
```env
# WhatsApp Configuration
WHATSAPP_API_TOKEN=
WHATSAPP_PHONE_ID=
WHATSAPP_WEBHOOK_TOKEN=

# State Management
STATE_ENCRYPTION_KEY=
STATE_DEFAULT_TTL=86400
STATE_WHATSAPP_TTL=604800

# Feature Flags
ENABLE_WHATSAPP=true
ENABLE_STATE_PERSISTENCE=true
ENABLE_CROSS_CHANNEL=true
```

## Conclusion

This enhanced program specification provides a comprehensive roadmap for implementing cross-channel persistence and WhatsApp integration while maintaining the existing application flow. The modular approach allows for phased implementation and testing of each component independently.