@extends('admin/sidebar')
@section('title', 'System Settings')
@section('content')

<link rel="stylesheet" href="{{ asset('css/compact-layout.css') }}">

<div class="compact-layout">
    <div class="sticky-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">
                    <span class="me-2">⚙️</span>
                    System Settings
                </h4>
                <p class="subtitle mb-0">Configure system preferences and options</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm" onclick="saveAllSettings()">
                    <i class="fas fa-save me-1"></i>Save All
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Settings Categories -->
        <div class="row g-3">
            <!-- General Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cog me-1"></i>General Settings
                    </div>
                    <div class="card-body">
                        <form id="generalSettingsForm">
                            <div class="mb-3">
                                <label for="system_name" class="form-label">System Name</label>
                                <input type="text" class="form-control" id="system_name" name="system_name" value="QR Attendance System">
                            </div>
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Asia/Manila" selected>Asia/Manila (UTC+8)</option>
                                    <option value="UTC">UTC (UTC+0)</option>
                                    <option value="America/New_York">America/New_York (UTC-5)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select" id="date_format" name="date_format">
                                    <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                    <option value="m/d/Y">MM/DD/YYYY</option>
                                    <option value="d/m/Y">DD/MM/YYYY</option>
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                <label class="form-check-label" for="maintenance_mode">
                                    Enable Maintenance Mode
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Attendance Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clock me-1"></i>Attendance Settings
                    </div>
                    <div class="card-body">
                        <form id="attendanceSettingsForm">
                            <div class="mb-3">
                                <label for="attendance_window" class="form-label">Attendance Window (minutes)</label>
                                <input type="number" class="form-control" id="attendance_window" name="attendance_window" value="30" min="1" max="120">
                                <small class="form-text text-muted">How long attendance sessions remain active</small>
                            </div>
                            <div class="mb-3">
                                <label for="late_threshold" class="form-label">Late Threshold (minutes)</label>
                                <input type="number" class="form-control" id="late_threshold" name="late_threshold" value="15" min="1" max="60">
                                <small class="form-text text-muted">Minutes after session start to mark as late</small>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_close_sessions" name="auto_close_sessions" checked>
                                <label class="form-check-label" for="auto_close_sessions">
                                    Auto-close expired sessions
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_manual_attendance" name="allow_manual_attendance" checked>
                                <label class="form-check-label" for="allow_manual_attendance">
                                    Allow manual attendance entry
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- SMS Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-sms me-1"></i>SMS Gateway Settings
                    </div>
                    <div class="card-body">
                        <form id="smsSettingsForm">
                            <div class="mb-3">
                                <label for="sms_gateway_url" class="form-label">Gateway URL</label>
                                <input type="url" class="form-control" id="sms_gateway_url" name="sms_gateway_url" placeholder="http://your-gateway-url.com">
                            </div>
                            <div class="mb-3">
                                <label for="sms_username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="sms_username" name="sms_username">
                            </div>
                            <div class="mb-3">
                                <label for="sms_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="sms_password" name="sms_password">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled">
                                <label class="form-check-label" for="sms_enabled">
                                    Enable SMS notifications
                                </label>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="testSmsGateway()">
                                <i class="fas fa-paper-plane me-1"></i>Test Connection
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-1"></i>Security Settings
                    </div>
                    <div class="card-body">
                        <form id="securitySettingsForm">
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="120" min="30" max="480">
                            </div>
                            <div class="mb-3">
                                <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="password_min_length" name="password_min_length" value="8" min="6" max="20">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="require_strong_passwords" name="require_strong_passwords">
                                <label class="form-check-label" for="require_strong_passwords">
                                    Require strong passwords
                                </label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_login_attempts" name="enable_login_attempts" checked>
                                <label class="form-check-label" for="enable_login_attempts">
                                    Limit login attempts
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="log_user_activity" name="log_user_activity" checked>
                                <label class="form-check-label" for="log_user_activity">
                                    Log user activity
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-database me-1"></i>Backup Settings
                    </div>
                    <div class="card-body">
                        <form id="backupSettingsForm">
                            <div class="mb-3">
                                <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                <select class="form-select" id="backup_frequency" name="backup_frequency">
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="backup_retention" class="form-label">Retention Period (days)</label>
                                <input type="number" class="form-control" id="backup_retention" name="backup_retention" value="30" min="7" max="365">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" checked>
                                <label class="form-check-label" for="auto_backup">
                                    Enable automatic backups
                                </label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="createBackup()">
                                    <i class="fas fa-download me-1"></i>Create Backup
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="viewBackups()">
                                    <i class="fas fa-list me-1"></i>View Backups
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-1"></i>System Information
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <strong>System Version:</strong>
                                <p class="mb-1">v1.0.0</p>
                            </div>
                            <div class="col-6">
                                <strong>Laravel Version:</strong>
                                <p class="mb-1">{{ app()->version() }}</p>
                            </div>
                            <div class="col-6">
                                <strong>PHP Version:</strong>
                                <p class="mb-1">{{ PHP_VERSION }}</p>
                            </div>
                            <div class="col-6">
                                <strong>Database:</strong>
                                <p class="mb-1">MySQL</p>
                            </div>
                            <div class="col-6">
                                <strong>Storage Used:</strong>
                                <p class="mb-1">-</p>
                            </div>
                            <div class="col-6">
                                <strong>Last Backup:</strong>
                                <p class="mb-1">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saveAllSettings() {
    // Collect all form data
    const generalData = new FormData(document.getElementById('generalSettingsForm'));
    const attendanceData = new FormData(document.getElementById('attendanceSettingsForm'));
    const smsData = new FormData(document.getElementById('smsSettingsForm'));
    const securityData = new FormData(document.getElementById('securitySettingsForm'));
    const backupData = new FormData(document.getElementById('backupSettingsForm'));
    
    // Here you would typically send the data to your backend
    alert('Settings saved successfully! (This functionality will be implemented based on your requirements)');
}

function testSmsGateway() {
    // Test SMS gateway connection
    alert('Testing SMS gateway connection... (This functionality will be implemented)');
}

function createBackup() {
    // Create a new backup
    alert('Creating backup... (This functionality will be implemented)');
}

function viewBackups() {
    // View available backups
    alert('Viewing backups... (This functionality will be implemented)');
}

// Auto-save functionality (optional)
document.addEventListener('DOMContentLoaded', function() {
    const forms = ['generalSettingsForm', 'attendanceSettingsForm', 'smsSettingsForm', 'securitySettingsForm', 'backupSettingsForm'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('change', function() {
                // Optionally implement auto-save on change
                console.log(`${formId} settings changed`);
            });
        }
    });
});
</script>

@endsection
