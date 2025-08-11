# SMS Integration Setup Guide - Android SMS Gateway

This document explains how to set up the SMS integration for the QR Attendance System using an Android phone as SMS gateway with the custom Android SMS Gateway service.

## Prerequisites

1. Android phone with data/wifi connection
2. SMS Gateway app installed on the phone (Android SMS Gateway)
3. Active SIM card for sending SMS
4. Network connectivity between server and Android device

## Android SMS Gateway App Setup

### Recommended App: Android SMS Gateway
1. Download and install Android SMS Gateway app on your phone
2. Open the app and grant necessary permissions:
   - SMS permissions
   - Phone permissions
   - Storage permissions (if needed)
3. Configure the gateway settings:
   - Set up username/login credentials
   - Set up password
   - Configure the port (default: 8080)
   - Note the device IP address
4. Start the SMS Gateway service
5. Keep the app running and phone connected to wifi

### Configuration Details
- **Default Port**: 8080
- **API Endpoint**: `/v1/send` for sending SMS
- **Status Endpoint**: `/v1/message/{id}` for checking delivery status
- **Authentication**: Basic HTTP authentication (username/password)

## Laravel Configuration

### 1. Environment Variables
Add these variables to your `.env` file:

```env
# SMS Gateway Configuration
SMS_GATEWAY_URL=http://192.168.1.100:8080
SMS_GATEWAY_LOGIN=your_gateway_username
SMS_GATEWAY_PASSWORD=your_gateway_password
```

Replace:
- `192.168.1.100:8080` with your phone's IP address and port
- `your_gateway_username` with the username set in the app
- `your_gateway_password` with the password set in the app

### 2. Configuration File
The system uses `config/sms.php` for SMS settings:

```php
return [
    'gateway_url' => env('SMS_GATEWAY_URL', 'http://192.168.1.100:8080'),
    'login' => env('SMS_GATEWAY_LOGIN'),
    'password' => env('SMS_GATEWAY_PASSWORD'),
    'timeout' => 30,
    'validate_numbers' => true,
    'allowed_country_codes' => ['+63'],
    'default_country_code' => '+63',
];
```

### 3. Database Schema
The `outbound_messages` table structure:

```sql
- id (Primary Key)
- student_id (Foreign Key to students table)
- contact_number (Phone number)
- message (SMS content)
- message_id (Gateway message ID for tracking)
- status (pending, sent, failed)
- created_at, updated_at (Timestamps)
```

## Features Implemented

### 1. Android SMS Gateway Service (`AndroidSmsGatewayService`)

**Core Methods:**
- `sendSms($text, $recipients)` - Send SMS to one or multiple recipients
- `getStatus($messageId)` - Check delivery status of sent message
- `isGatewayReachable()` - Test gateway connectivity
- `getGatewayInfo()` - Get gateway status information

**Features:**
- Phone number validation and normalization
- Multiple recipient support
- Automatic retry logic
- Comprehensive error handling and logging
- HTTP Basic Authentication

### 2. Message API Controller (`MessageApiController`)

**Endpoints:**
- `POST /teacher/send-sms` - Send custom SMS message
- `GET /teacher/outbound-messages` - Get SMS history with filtering
- `GET /teacher/message-status/{id}` - Check delivery status
- `GET /teacher/test-sms-gateway` - Test gateway connectivity

**Features:**
- Input validation and sanitization
- Automatic attendance notification sending
- Message status tracking
- Error handling with detailed logging

### 3. Automatic SMS Notifications

**Trigger Points:**
- When student scans QR code for attendance (via `AttendanceSessionController`)
- Message includes: student name, attendance status (PRESENT/ABSENT), time

**Message Format:**
```
"Your child [Student Name] was marked [PRESENT/ABSENT] today at [Time]."
```

**Validation:**
- Only sends if contact number exists and is valid
- Philippine number format validation (+63 or 09)
- Automatic normalization to international format

### 4. Manual SMS Interface

**Teacher Dashboard Features:**
- SMS button next to each student in attendance table
- Custom message modal with templates:
  - Absent Today Template
  - General Reminder Template  
  - Custom Message Template
- Character counter (1000 character limit)
- Real-time validation

### 5. SMS History & Tracking

**History Table Features:**
- Complete message log with timestamps
- Student information linking
- Message status tracking
- Delivery status checking
- Filter by student, status, date range

**Status Updates:**
- Real-time status checking via gateway API
- Automatic status updates in database
- Visual status indicators (badges)

## API Endpoints Details

### Send SMS
```http
POST /teacher/send-sms
Content-Type: application/json
Authorization: Bearer {token}

{
    "number": "+639123456789",
    "message": "Your child John Doe was marked PRESENT today at 8:30 AM.",
    "student_id": 123
}
```

**Response:**
```json
{
    "status": "success",
    "message": "SMS sent successfully",
    "message_id": "unique_gateway_id",
    "outbound_message_id": 456
}
```

### Check Message Status
```http
GET /teacher/message-status/{outbound_message_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
    "status": "success",
    "message_id": "unique_gateway_id",
    "delivery_status": "delivered",
    "data": {...}
}
```

### Get Message History
```http
GET /teacher/outbound-messages?student_id=123&status=sent&start_date=2025-08-01&end_date=2025-08-31
Authorization: Bearer {token}
```

## Phone Number Validation

**Supported Formats:**
- `+639123456789` (International format - preferred)
- `09123456789` (Local Philippine format)

**Automatic Processing:**
- Normalization to +63 format
- Removal of spaces and dashes
- Length validation (11 digits for 09, 13 for +63)

## Gateway Communication Protocol

### Send SMS Request
```http
POST http://phone-ip:8080/v1/send
Authorization: Basic base64(username:password)
Content-Type: application/json

{
    "recipients": ["+639123456789"],
    "message": "Your message content here"
}
```

### Status Check Request
```http
GET http://phone-ip:8080/v1/message/{message_id}
Authorization: Basic base64(username:password)
```

## Error Handling & Logging

### Laravel Logging
All SMS operations are logged with context:
- Successful sends with message IDs
- Failed attempts with error details
- Phone number validation failures
- Gateway connectivity issues

### Log Locations
- `storage/logs/laravel.log` - Main application log
- Database `outbound_messages` table - SMS transaction log

### Common Error Scenarios
1. **Invalid Phone Number** - Logs warning, skips sending
2. **Gateway Unreachable** - Logs error, marks as failed
3. **Authentication Failed** - Logs error with HTTP status
4. **Network Timeout** - Logs timeout error, retries if configured

## Troubleshooting

### SMS Not Sending
1. **Check Gateway Connection:**
   ```bash
   # Test from server
   curl -u username:password http://phone-ip:8080/v1/status
   ```

2. **Verify Configuration:**
   - Check `.env` file settings
   - Verify phone IP address hasn't changed
   - Confirm username/password are correct

3. **Check Phone Setup:**
   - SMS Gateway app is running
   - Phone has active SIM card with credit
   - Phone is connected to same network
   - Required permissions granted

### Gateway Not Reachable
1. **Network Issues:**
   - Ping the phone IP from server
   - Check firewall settings
   - Verify both devices on same network
   - Check if phone's IP is static/DHCP

2. **App Issues:**
   - Restart SMS Gateway app
   - Check app permissions
   - Verify port configuration
   - Update app if needed

### Database Issues
1. **Migration Problems:**
   ```bash
   php artisan migrate:status
   php artisan migrate --force
   ```

2. **Missing Records:**
   - Check foreign key constraints
   - Verify student IDs exist
   - Check database connections

## Performance Considerations

### Rate Limiting
- Gateway may have built-in rate limits
- Consider implementing queue for bulk SMS
- Monitor phone balance/credit

### Network Optimization
- Use static IP for Android device
- Configure QoS for SMS traffic priority
- Consider redundant network connections

### Database Optimization
- Index on `student_id` and `created_at` for history queries
- Regular cleanup of old SMS records
- Consider archiving old messages

## Security Best Practices

### Authentication
- Use strong username/password for gateway
- Change default credentials immediately
- Consider certificate-based authentication

### Network Security
- Use secure network (WPA3/WPA2)
- Consider VPN for remote access
- Firewall rules for SMS gateway port

### Data Protection
- Encrypt sensitive configuration values
- Regular backup of SMS logs
- Audit SMS content for privacy compliance

### Access Control
- Role-based access to SMS features
- Audit logging for SMS operations
- Rate limiting per user/session

## Monitoring & Maintenance

### Health Checks
- Gateway connectivity monitoring
- Automated status checks
- SMS delivery rate monitoring

### Maintenance Tasks
- Regular SMS log cleanup
- Gateway app updates
- Phone system updates
- Network security updates

### Alerting
- Gateway offline notifications
- High failure rate alerts
- Phone balance warnings
- Network connectivity alerts

## Cost Management

### SMS Costs
- Monitor usage patterns
- Set up balance alerts
- Consider unlimited SMS plans
- Track cost per message

### Infrastructure
- Dedicated phone for SMS gateway
- Backup phone for redundancy
- Network infrastructure costs
- Server resource usage

---

**Note**: This SMS integration provides enterprise-grade SMS capabilities using Android devices as cost-effective SMS gateways. Ensure compliance with local telecommunications regulations and privacy laws.
