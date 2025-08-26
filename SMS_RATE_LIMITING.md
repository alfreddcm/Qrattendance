# SMS Rate Limiting Feature

## Overview
This feature prevents sending multiple SMS messages to the same phone number within a specified time period, reducing the risk of message failures and improving the reliability of attendance notifications.

## Configuration

### Environment Variables
Add these variables to your `.env` file:

```bash
# SMS Rate Limiting Configuration
SMS_MESSAGE_DELAY_SECONDS=60      # Delay in seconds between messages to same number
SMS_ENABLE_RATE_LIMITING=true     # Enable/disable rate limiting
```

### Config File
The settings are defined in `config/sms.php`:

```php
'message_delay_seconds' => env('SMS_MESSAGE_DELAY_SECONDS', 60),
'enable_rate_limiting' => env('SMS_ENABLE_RATE_LIMITING', true),
```

## How It Works

1. **Before sending an SMS**, the system checks if a message was recently sent to the same number
2. **If a message was sent within the delay period**, the new message is skipped with a log entry
3. **If no recent message exists**, the message is sent normally
4. **Each successful message** updates the `last_sent_at` timestamp for that number

## Database Changes

A new field `last_sent_at` was added to the `outbound_messages` table to track when messages were last sent to each number.

## Features

### Rate Limit Status Check
- **Route**: `POST /teacher/check-rate-limit` or `POST /admin/check-rate-limit`
- **Purpose**: Check if a message can be sent to a specific number
- **Response**: Returns status, time remaining, and other details

### Test Rate Limiting
- **Route**: `GET /teacher/test-rate-limit` or `GET /admin/test-rate-limit`
- **Purpose**: Test the rate limiting configuration
- **Response**: Returns current settings and test results

## Usage in Attendance Recording

When a student scans their QR code for attendance:
1. The system records the attendance
2. If successful, it attempts to send an SMS notification to the parent/guardian
3. **Rate limiting is applied** - if a message was recently sent to that number, it will be skipped
4. This prevents multiple messages when a student scans multiple times quickly

## Benefits

1. **Reduces SMS failures** caused by rapid successive messages
2. **Prevents spam** to parents/guardians
3. **Saves SMS costs** by avoiding duplicate notifications
4. **Improves system reliability** during high-traffic periods

## Monitoring

- Check the application logs for rate limiting events
- Look for messages like "Message rate limited" in the logs
- Monitor the `outbound_messages` table for `last_sent_at` timestamps

## Customization

You can adjust the delay period by changing `SMS_MESSAGE_DELAY_SECONDS` in your `.env` file:
- **30 seconds**: Fast notifications, higher risk of duplicates
- **60 seconds**: Balanced approach (recommended)
- **120 seconds**: Conservative approach, lower message frequency

## Troubleshooting

If messages aren't being sent:
1. Check if `SMS_ENABLE_RATE_LIMITING=false` to disable temporarily
2. Review logs for "Message rate limited" entries
3. Check the `outbound_messages` table for recent entries
4. Verify the `SMS_MESSAGE_DELAY_SECONDS` setting

## Migration

The feature was added with migration:
```
2025_08_25_223856_add_last_sent_field_to_outbound_messages_table.php
```

To roll back if needed:
```bash
php artisan migrate:rollback --step=1
```
