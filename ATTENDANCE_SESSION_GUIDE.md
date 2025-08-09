# QR Attendance Session System

## Overview
The QR Attendance Session System allows teachers to create secure, one-time links for taking attendance. These links can be safely opened on any device or browser without compromising security.

## How It Works

### For Teachers:

1. **Start Attendance Session**
   - Click "Start Attendance Session" on the dashboard
   - Enter optional session name and duration (5 minutes to 8 hours)
   - System creates a unique, secure link

2. **Share the Link**
   - Copy the generated public link
   - Share with students via chat, email, or projection
   - Link can be opened on any device safely

3. **Monitor Sessions**
   - View active sessions in the "Active Sessions" modal
   - See access count and session details
   - Close sessions manually when done

### For Students:

1. **Access the Link**
   - Open the shared attendance link
   - No login required
   - Works on any device with camera

2. **Scan QR Code**
   - Allow camera access when prompted
   - Point camera at your QR code
   - System automatically processes attendance

## Security Features

- **One-time tokens**: Each session has a unique, hard-to-guess token
- **Time expiration**: Sessions automatically expire after set duration
- **Access logging**: All access attempts are logged for debugging
- **Teacher isolation**: Sessions only work for the teacher's students
- **Automatic cleanup**: Only one active session per teacher at a time

## Error Debugging

All attendance events are logged in separate files:

- **Location**: `storage/logs/attendance-YYYY-MM-DD.log`
- **Contains**: Session creation, QR scans, errors, access attempts
- **Format**: JSON structured logs with timestamps and context

### Common Log Events:

```json
{
  "timestamp": "2025-08-01T10:30:00.000Z",
  "action": "Attendance session created",
  "session_id": 123,
  "user_id": 1,
  "ip_address": "192.168.1.100",
  "session_name": "Morning Attendance"
}
```

## Troubleshooting

### Session Not Working
- Check if session has expired
- Verify the token in the URL is correct
- Ensure active semester exists

### QR Code Not Scanning
- Check camera permissions
- Ensure good lighting
- Verify QR code format is correct

### Student Not Found
- Confirm student belongs to the session's semester
- Check if student is assigned to the correct teacher

## Database Tables

### attendance_sessions
- `session_token`: Unique session identifier
- `teacher_id`: Owner of the session
- `semester_id`: Associated semester
- `status`: active/expired/closed
- `expires_at`: Automatic expiration time
- `access_log`: JSON log of access attempts

## API Endpoints

### Teacher Routes (Auth Required)
- `POST /teacher/attendance-session/create` - Create new session
- `GET /teacher/attendance-session/active` - Get active sessions
- `POST /teacher/attendance-session/{id}/close` - Close session

### Public Routes (No Auth)
- `GET /attendance/{token}` - Public attendance page
- `POST /attendance/{token}/qr-verify` - Process QR codes

## Implementation Notes

- Sessions automatically close existing active sessions
- Maximum 50 access log entries per session (auto-pruned)
- Public pages use temporary authentication for attendance processing
- Bootstrap 5 and Font Awesome 6 for UI components
- jsQR library for client-side QR code detection
