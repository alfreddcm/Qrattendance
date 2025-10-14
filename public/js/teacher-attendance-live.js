// Teacher Attendance Live JavaScript
class TeacherAttendanceLive {
    constructor() {
        this.sessionToken = window.sessionToken;
        this.html5QrCode = null;
        this.isScanning = false;
        this.lastScannedCode = null;
        this.lastScannedTime = 0;
        this.scanCooldown = 3000; // 3 seconds between scans of same code
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startLiveClock();
        this.setupQRScanner();
        this.pollForUpdates();
    }

    setupEventListeners() {
        // Scanner toggle buttons
        document.getElementById('usb-toggle')?.addEventListener('click', () => {
            this.switchToUSBScanner();
        });

        document.getElementById('webcam-toggle')?.addEventListener('click', () => {
            this.switchToWebcamScanner();
        });

        // USB scanner input
        const usbInput = document.getElementById('usb-scanner-input');
        if (usbInput) {
            usbInput.addEventListener('input', (e) => {
                this.handleUSBScan(e.target.value);
            });

            usbInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.handleUSBScan(e.target.value);
                }
            });

            // Keep focus on USB input
            usbInput.focus();
            setInterval(() => {
                if (document.getElementById('usb-toggle')?.classList.contains('active')) {
                    usbInput.focus();
                }
            }, 1000);
        }

        // Session action buttons
        document.querySelector('[onclick="shareSession()"]')?.addEventListener('click', this.shareSession.bind(this));
        document.querySelector('[onclick="viewReports()"]')?.addEventListener('click', this.viewReports.bind(this));
    }

    startLiveClock() {
        const updateClock = () => {
            const now = new Date();
            const timeOptions = {
                timeZone: 'Asia/Manila',
                hour12: true,
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit'
            };
            const dateOptions = {
                timeZone: 'Asia/Manila',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };

            const timeStr = now.toLocaleTimeString('en-US', timeOptions);
            const dateStr = now.toLocaleDateString('en-US', dateOptions);

            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                clockElement.querySelector('.time').textContent = timeStr;
                clockElement.querySelector('.date').textContent = dateStr;
            }
        };

        updateClock();
        setInterval(updateClock, 1000);
    }

    switchToUSBScanner() {
        // Update toggle buttons
        document.getElementById('usb-toggle')?.classList.add('active');
        document.getElementById('webcam-toggle')?.classList.remove('active');

        // Show/hide sections
        document.getElementById('usb-scanner-section').style.display = 'block';
        document.getElementById('webcam-scanner-section').style.display = 'none';

        // Stop webcam if running
        this.stopWebcamScanner();

        // Focus on USB input
        document.getElementById('usb-scanner-input')?.focus();

        this.updateScannerStatus('USB Scanner Ready', 'success');
    }

    switchToWebcamScanner() {
        // Update toggle buttons
        document.getElementById('usb-toggle')?.classList.remove('active');
        document.getElementById('webcam-toggle')?.classList.add('active');

        // Show/hide sections
        document.getElementById('usb-scanner-section').style.display = 'none';
        document.getElementById('webcam-scanner-section').style.display = 'block';

        // Start webcam scanner
        this.startWebcamScanner();

        this.updateScannerStatus('Camera Scanner Active', 'success');
    }

    async startWebcamScanner() {
        try {
            if (this.html5QrCode) {
                await this.html5QrCode.stop();
            }

            this.html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            await this.html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    this.handleQRScan(decodedText);
                },
                (errorMessage) => {
                    // Ignore decode errors
                }
            );

            this.isScanning = true;
        } catch (err) {
            console.error('Failed to start camera scanner:', err);
            this.updateScannerStatus('Camera access failed', 'error');
        }
    }

    async stopWebcamScanner() {
        if (this.html5QrCode && this.isScanning) {
            try {
                await this.html5QrCode.stop();
                this.isScanning = false;
            } catch (err) {
                console.error('Error stopping camera:', err);
            }
        }
    }

    handleUSBScan(scannedData) {
        if (!scannedData || scannedData.length < 3) return;

        this.handleQRScan(scannedData);
        
        // Clear the input for next scan
        document.getElementById('usb-scanner-input').value = '';
    }

    handleQRScan(qrData) {
        const now = Date.now();
        
        // Prevent duplicate scans of the same code within cooldown period
        if (this.lastScannedCode === qrData && (now - this.lastScannedTime) < this.scanCooldown) {
            return;
        }

        this.lastScannedCode = qrData;
        this.lastScannedTime = now;

        this.updateScannerStatus('Processing...', 'processing');
        this.showLoading(true);

        // Process the QR code
        this.processAttendance(qrData);
    }

    async processAttendance(qrData) {
        try {
            const response = await fetch('/teacher/attendance/live/qr-verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ qr_data: qrData })
            });

            const result = await response.json();
            
            this.showLoading(false);

            if (result.success) {
                this.displayStudentInfo(result.student, result.attendance_type, result.time);
                this.updateAttendanceList(result.student, result.attendance_type, result.time);
                this.showSuccessModal(result.student, result.attendance_type, result.time);
                this.updateScannerStatus('Scan successful!', 'success');
                
                // Reset to ready state after 3 seconds
                setTimeout(() => {
                    this.resetToReadyState();
                }, 3000);
            } else {
                this.showErrorModal(result.message || 'Failed to process attendance');
                this.updateScannerStatus('Scan failed', 'error');
                
                // Reset to ready state after 2 seconds
                setTimeout(() => {
                    this.resetToReadyState();
                }, 2000);
            }
        } catch (error) {
            console.error('Error processing attendance:', error);
            this.showLoading(false);
            this.showErrorModal('Network error occurred');
            this.updateScannerStatus('Network error', 'error');
            
            setTimeout(() => {
                this.resetToReadyState();
            }, 2000);
        }
    }

    displayStudentInfo(student, attendanceType, time) {
        const displayContainer = document.getElementById('student-card-display');
        if (!displayContainer) return;

        const attendanceTime = new Date(time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        displayContainer.innerHTML = `
            <div class="student-success-state">
                <div class="student-photo-display">
                    ${student.picture ? 
                        `<img src="/storage/student_pictures/${student.picture}" alt="${student.name}">` :
                        `<div class="avatar-placeholder">${student.name.charAt(0)}</div>`
                    }
                </div>
                <div class="student-info">
                    <h3>${student.name}</h3>
                    <p class="attendance-action ${attendanceType}">${attendanceType === 'time_in' ? 'TIME IN' : 'TIME OUT'}</p>
                </div>
                <div class="student-details">
                    <div class="detail-row">
                        <span class="label">Name:</span>
                        <span class="value">${student.name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Section:</span>
                        <span class="value">${student.section || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Time:</span>
                        <span class="value">${attendanceTime}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value ${attendanceType}">${attendanceType === 'time_in' ? 'PRESENT' : 'DEPARTED'}</span>
                    </div>
                </div>
            </div>
        `;
    }

    resetToReadyState() {
        const displayContainer = document.getElementById('student-card-display');
        if (!displayContainer) return;

        displayContainer.innerHTML = `
            <div class="waiting-state">
                <div class="student-photo-placeholder">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="student-info">
                    <h3>Ready to Scan</h3>
                    <p>Point your scanner at a student's QR code</p>
                </div>
                <div class="student-details">
                    <div class="detail-row">
                        <span class="label">Name:</span>
                        <span class="value">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Section:</span>
                        <span class="value">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Time:</span>
                        <span class="value">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value">-</span>
                    </div>
                </div>
            </div>
        `;

        this.updateScannerStatus('Ready to Scan', 'success');
    }

    updateScannerStatus(message, type) {
        const statusElement = document.getElementById('scanner-status');
        if (!statusElement) return;

        const iconClass = type === 'success' ? 'fa-check-circle text-success' :
                         type === 'error' ? 'fa-times-circle text-danger' :
                         type === 'processing' ? 'fa-spinner fa-spin text-warning' :
                         'fa-circle text-success';

        statusElement.innerHTML = `
            <i class="fas ${iconClass}"></i>
            ${message}
        `;
    }

    updateAttendanceList(student, attendanceType, time) {
        const attendanceList = document.getElementById('attendance-list');
        const attendanceCount = document.getElementById('attendance-count');
        
        if (!attendanceList) return;

        const attendanceTime = new Date(time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        // Create new attendance card
        const newCard = document.createElement('div');
        newCard.className = 'attendance-card new-record';
        newCard.innerHTML = `
            <div class="student-avatar">
                ${student.picture ? 
                    `<img src="/storage/student_pictures/${student.picture}" alt="${student.name}">` :
                    `<div class="avatar-placeholder">${student.name.charAt(0)}</div>`
                }
            </div>
            <div class="student-info">
                <div class="student-name">${student.name}</div>
                <div class="student-section">${student.section || 'No Section'}</div>
            </div>
            <div class="attendance-time">
                <div class="time-badge ${attendanceType === 'time_in' ? 'time-in' : 'time-out'}">
                    <i class="fas ${attendanceType === 'time_in' ? 'fa-sign-in-alt' : 'fa-sign-out-alt'}"></i>
                    ${attendanceTime}
                </div>
            </div>
        `;

        // Insert at the beginning
        attendanceList.insertBefore(newCard, attendanceList.firstChild);

        // Remove excess cards (keep only 8)
        const cards = attendanceList.querySelectorAll('.attendance-card');
        if (cards.length > 8) {
            for (let i = 8; i < cards.length; i++) {
                cards[i].remove();
            }
        }

        // Update count
        if (attendanceCount) {
            const currentCount = parseInt(attendanceCount.textContent) || 0;
            attendanceCount.textContent = `${currentCount + 1} records`;
        }

        // Highlight the new record
        setTimeout(() => {
            newCard.classList.remove('new-record');
        }, 2000);
    }

    showSuccessModal(student, attendanceType, time) {
        const modal = document.getElementById('attendanceModal');
        const modalBody = document.getElementById('modal-body');
        
        if (!modal || !modalBody) return;

        const attendanceTime = new Date(time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        modalBody.innerHTML = `
            <div class="success-modal">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h4>${attendanceType === 'time_in' ? 'TIME IN' : 'TIME OUT'} SUCCESSFUL</h4>
                <h5>${student.name}</h5>
                <p>Section: ${student.section || 'N/A'}</p>
                <p>Time: ${attendanceTime}</p>
            </div>
        `;

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();

        // Auto-hide after 3 seconds
        setTimeout(() => {
            bootstrapModal.hide();
        }, 3000);
    }

    showErrorModal(message) {
        const modal = document.getElementById('attendanceModal');
        const modalBody = document.getElementById('modal-body');
        
        if (!modal || !modalBody) return;

        modalBody.innerHTML = `
            <div class="error-modal">
                <i class="fas fa-times-circle text-danger" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h4>SCAN FAILED</h4>
                <p>${message}</p>
            </div>
        `;

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();

        // Auto-hide after 3 seconds
        setTimeout(() => {
            bootstrapModal.hide();
        }, 3000);
    }

    showLoading(show) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    shareSession() {
        const sessionUrl = `${window.location.origin}/attendance/${this.sessionToken}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'Attendance Session',
                text: 'Join our attendance session',
                url: sessionUrl
            });
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(sessionUrl).then(() => {
                alert('Session link copied to clipboard!');
            }).catch(() => {
                prompt('Copy this link:', sessionUrl);
            });
        }
    }

    viewReports() {
        window.open('/teacher/attendance', '_blank');
    }

    async pollForUpdates() {
        // Poll for new attendance records every 30 seconds
        setInterval(async () => {
            try {
                const response = await fetch(`/attendance/${this.sessionToken}/status`);
                const data = await response.json();
                
                if (data.recent_attendance) {
                    // Update recent attendance list if needed
                    // This could be implemented to show real-time updates from other devices
                }
            } catch (error) {
                console.log('Polling error:', error);
            }
        }, 30000);
    }
}

// Global functions for button clicks
function shareSession() {
    if (window.teacherAttendance) {
        window.teacherAttendance.shareSession();
    }
}

function viewReports() {
    if (window.teacherAttendance) {
        window.teacherAttendance.viewReports();
    }
}

function stopScanning() {
    if (window.teacherAttendance) {
        window.teacherAttendance.stopWebcamScanner();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.teacherAttendance = new TeacherAttendanceLive();
});

// Additional CSS for dynamic states
const additionalStyles = `
<style>
.student-photo-display {
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.student-photo-display img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.attendance-action {
    font-weight: 700;
    font-size: 1.2rem;
    padding: 8px 16px;
    border-radius: 20px;
    display: inline-block;
    margin: 10px 0;
}

.attendance-action.time_in {
    background: #d4edda;
    color: #155724;
}

.attendance-action.time_out {
    background: #fff3cd;
    color: #856404;
}

.detail-row .value.time_in {
    color: #28a745;
    font-weight: 600;
}

.detail-row .value.time_out {
    color: #ffc107;
    font-weight: 600;
}

.attendance-card.new-record {
    animation: highlightNew 2s ease-in-out;
}

@keyframes highlightNew {
    0% { background: #e3f2fd; transform: scale(1.02); }
    100% { background: #f8f9fa; transform: scale(1); }
}

.success-modal, .error-modal {
    text-align: center;
    padding: 20px;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalStyles);
