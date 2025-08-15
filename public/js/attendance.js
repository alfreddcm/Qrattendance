// QR Attendance System - Clean JavaScript with Material Design

// Global variables
let html5QrcodeScanner = null;
let usbScannerTimeout = null;
let currentStudentData = null;
let sessionCheckInterval = null;
let currentPeriodStatus = null;

let amTimeInStart, amTimeInEnd, pmTimeOutStart, pmTimeOutEnd;

 document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, session token:', window.sessionToken);
    updateDateTime();
    setInterval(updateDateTime, 1000);
    initializeScanner();
    startSessionMonitoring();
    setupEventListeners();
    
     initializeMaterialDesign();
});

 function initializeMaterialDesign() {
     resetToMaterialWaitingState();
    
     document.body.classList.add('material-design-enabled');
    
     if (!document.querySelector('.material-snackbar-container')) {
        const container = document.createElement('div');
        container.className = 'material-snackbar-container';
        document.body.appendChild(container);
    }
    
    console.log('Material Design components initialized');
}


function setupEventListeners() {
    
    document.getElementById('usb-toggle').addEventListener('click', () => {
        activateUsbScanner();
    });

    
    document.getElementById('webcam-toggle').addEventListener('click', () => {
        activateWebcamScanner();
    });
}


function startSessionMonitoring() {
    checkSessionValidity();
    
    sessionCheckInterval = setInterval(checkSessionValidity, 60000);
}

async function checkSessionValidity() {
    try {
        const response = await fetch(`/attendance/${window.sessionToken}/status`);
        const result = await response.json();

        console.log('Session status check:', result);

        if (!result.success) {
            handleSessionInvalid(result);
            return;
        }

        if (result.period_info) {
            updatePeriodStatus(result.period_info);
        }

    } catch (error) {
        console.error('Error checking session validity:', error);
        
    }
}

function updatePeriodStatus(periodInfo) {
    const newStatus = periodInfo.allowed ? periodInfo.period_name : 'outside';
    const isStatusChange = currentPeriodStatus !== newStatus;
    currentPeriodStatus = newStatus;
    
    updatePeriodDisplay(periodInfo);
    
    if (isStatusChange) {
        handlePeriodChange(periodInfo);
    }
}

function updatePeriodDisplay(periodInfo) {
    const timePeriods = document.querySelectorAll('.time-period');
    const statusDiv = document.querySelector('div[style*="margin-top: 8px"]');
    const alertDiv = document.querySelector('.outside-hours-alert');
    
    
    timePeriods.forEach(period => {
        period.classList.remove('active');
        period.classList.add('inactive');
    });
    
    if (periodInfo.allowed) {
        
        timePeriods.forEach(period => {
            const periodText = period.textContent;
            if ((periodInfo.period_name === 'AM Time In' && periodText.includes('AM Time In')) ||
                (periodInfo.period_name === 'PM Time Out' && periodText.includes('PM Time Out'))) {
                period.classList.remove('inactive');
                period.classList.add('active');
            }
        });
        
        if (statusDiv) {
            statusDiv.innerHTML = `
                <i class="fas fa-check-circle"></i> Currently accepting - ${periodInfo.period_name}
                <br><small>${periodInfo.time_remaining} minutes remaining</small>
            `;
            statusDiv.style.color = '#4CAF50';
        }
        
        if (alertDiv) alertDiv.style.display = 'none';
        
    } else {
        if (statusDiv) {
            let message = '<i class="fas fa-times-circle"></i> Outside attendance hours';
            if (periodInfo.next_period) {
                message += `<br><small>Next: ${periodInfo.next_period.period_name} at ${periodInfo.next_period.start_time}</small>`;
            }
            statusDiv.innerHTML = message;
            statusDiv.style.color = '#f44336';
        }
        
        if (alertDiv) {
            alertDiv.style.display = 'block';
            if (periodInfo.next_period) {
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    Outside attendance hours
                    <br><small>Next: ${periodInfo.next_period.period_name} at ${periodInfo.next_period.start_time}</small>
                `;
            }
        }
    }
}

function handlePeriodChange(periodInfo) {
    if (periodInfo.allowed && currentPeriodStatus !== null) {
        showInlineStatus(`🟢 ${periodInfo.period_name} period is now active!`, 'success');
        playNotificationSound(true);
        
        setTimeout(() => {
            resetToMaterialWaitingState();
        }, 5000);  
    } else if (!periodInfo.allowed && currentPeriodStatus !== null) {
        showInlineStatus(`🔴 Recording period has ended.`, 'warning');
        
        setTimeout(() => {
            resetToMaterialWaitingState();
        }, 5000);  
    }
}

function handleSessionInvalid(result) {
    clearInterval(sessionCheckInterval);
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }

    const message = result.message || 'Session is not valid';
    updateUIForInvalidSession(message);
    
    setTimeout(() => {
        window.location.reload();
    }, 5000);
}

function updateUIForInvalidSession(message) {
    const statusBadge = document.getElementById('status-badge');
    const photoElement = document.getElementById('student-photo');
    const infoElement = document.getElementById('student-info');

    statusBadge.textContent = 'SESSION INVALID';
    statusBadge.style.background = '#dc3545';

    photoElement.innerHTML = `<i class="fas fa-times-circle" style="color: #dc3545;"></i>`;
    
    infoElement.innerHTML = `
        <div class="student-name" style="color: #dc3545;">SESSION INVALID</div>
        <div class="student-details">${message}</div>
        <div class="student-details">Please contact your teacher for a valid link</div>
    `;

    showInlineStatus(`🚫 ${message}`, 'error');

    const usbInput = document.getElementById('usb-scanner-input');
    if (usbInput) {
        usbInput.disabled = true;
        usbInput.placeholder = 'Session invalid';
    }
}


function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    };
    const timeOptions = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    
    const dateStr = now.toLocaleDateString('en-US', options).toUpperCase();
    const timeStr = now.toLocaleTimeString('en-US', timeOptions);
    
    document.getElementById('datetime-bar').textContent = `TODAY IS: ${dateStr} | ${timeStr}`;
}


function initializeScanner() {
    const usbInput = document.getElementById('usb-scanner-input');
    
    usbInput.addEventListener('input', function(e) {
        const value = e.target.value;
        
        if (usbScannerTimeout) {
            clearTimeout(usbScannerTimeout);
        }
        
        usbScannerTimeout = setTimeout(() => {
            if (value.trim().length > 0) {
                processQRCode(value.trim(), 'USB Scanner');
                e.target.value = '';
                e.target.focus();
            }
        }, 100);
    });
    
    usbInput.addEventListener('blur', function() {
        setTimeout(() => {
            this.focus();
        }, 100);
    });
    
    usbInput.focus();
}

function activateUsbScanner() {
    document.getElementById('usb-toggle').classList.add('active');
    document.getElementById('webcam-toggle').classList.remove('active');
    
    document.getElementById('usb-scanner-section').style.display = 'block';
    document.getElementById('webcam-scanner-section').style.display = 'none';
    
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    
    setTimeout(() => {
        document.getElementById('usb-scanner-input').focus();
    }, 100);
}

function activateWebcamScanner() {
    document.getElementById('webcam-toggle').classList.add('active');
    document.getElementById('usb-toggle').classList.remove('active');
    
    document.getElementById('usb-scanner-section').style.display = 'none';
    document.getElementById('webcam-scanner-section').style.display = 'block';
    
    setTimeout(() => {
        initWebcamScanner();
    }, 300);
}

function initWebcamScanner() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
    }
    
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`QR Code detected: ${decodedText}`, decodedResult);
        processQRCode(decodedText, 'Webcam Scanner');
    }
    
    function onScanFailure(error) {
        
    }
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        { 
            fps: 10,
            qrbox: { width: 180, height: 180 },
            rememberLastUsedCamera: true,
            showTorchButtonIfSupported: true,
            showZoomSliderIfSupported: true,
            defaultZoomValueIfSupported: 1.0,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        },
        false
    );
    
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
}

function stopScanning() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
    }
    showInlineStatus('Webcam scanner stopped', 'info');
    setTimeout(() => {
        resetToMaterialWaitingState();
    }, 2000);
}


async function processQRCode(qrData, scannerType) {
    console.log('Processing QR Code:', qrData, 'from', scannerType);
    
    const now = Date.now();
    const timeSinceLastScan = now - (window.lastScanTime || 0);
    const isDuplicateWithinWindow = (window.lastScannedCode === qrData && timeSinceLastScan < 3000);
    
    if (isDuplicateWithinWindow) {
        console.log('Duplicate scan within 3 seconds, ignoring');
        return;
    }
    
    window.lastScannedCode = qrData;
    window.lastScanTime = now;
    
    showMaterialLoader(true);
    showMaterialSnackbar('🔍 Scanning QR Code...', 'info');
    
    try {
        if (!qrData || qrData.trim().length < 3) {
            throw new Error('QR code too short or empty');
        }

        const parsedData = parseQRCodeData(qrData.trim());
        if (!parsedData) {
            throw new Error('Invalid QR code format');
        }

        console.log('Parsed QR Data:', parsedData);
        
        // Display parsed data immediately (Material Design card)
        displayMaterialStudentCard(parsedData);
        
         const timeValidation = validateSessionTime();
         const attendanceType = determineAttendanceType(parsedData, timeValidation);
        
         updateMaterialCardWithTime(parsedData, timeValidation, attendanceType);

        console.log('Sending request to:', `/attendance/${window.sessionToken}/qr-verify`);

        const response = await fetch(`/attendance/${window.sessionToken}/qr-verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                qr_data: qrData.trim(),
                scanner_type: scannerType,
                scan_timestamp: new Date().toISOString(),
                parsed_data: parsedData,
                time_validation: timeValidation
            })
        });

        if (!response.ok) {
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        console.log('Server response:', result);
        
        // If server response includes student data with photo, update parsed data
        if (result.student && result.student.picture) {
            parsedData.picture = result.student.picture; // Map server picture to picture field
            // Update the photo in the card
            updateStudentPhotoInCard(result.student);
        }
        
        // Record attendance - allow both Time In and Time Out
        if (result.success) {
            recordMaterialAttendance(parsedData, attendanceType, result);
            showMaterialSnackbar(`✅ ${attendanceType} recorded successfully!`, 'success');
            
            // Show success popup with student details
            showSuccessPopup(result.student || parsedData, attendanceType, result.recorded_time);
        } else {
            // Even on failure, show student information if available (e.g., outside period)
            if (result.student) {
                console.log('Student data received on failure:', result.student);
                // Update only the status in the existing card (avoid redundant population)
                const statusElement = document.getElementById('material-status');
                if (statusElement) {
                    statusElement.textContent = result.status || 'Outside recording hours';
                }
                // Update info cards below with the student data (only if different from parsed data)
                if (result.student.name !== parsedData.name || result.student.section !== parsedData.section) {
                    updateInfoCards(result.student, result.current_time);
                }
            }
            showMaterialSnackbar(`❌ ${result.message || 'Recording failed'}`, 'error');
        }
        
        handleMaterialQRResult(result, parsedData, timeValidation, attendanceType);

    } catch (error) {
        console.error('Error processing QR code:', error);
        const errorMsg = error.message || 'Error processing QR code';
        showMaterialSnackbar(`❌ ${errorMsg}`, 'error');
        displayMaterialErrorCard(errorMsg);
        playNotificationSound(false);
    } finally {
        showMaterialLoader(false);
    }
}


function parseQRCodeData(qrData) {
    if (qrData.includes('_') && qrData.length >= 5) {
        return {
            stud_code: qrData,
            student_id: qrData.split('_')[0], 
            id_no: qrData.split('_')[0],
            name: 'Student ' + qrData.split('_')[0],
            teacher_id: null,
            section: 'Loading...'
        };
    }
    
    try {
        const jsonData = JSON.parse(qrData);
        if (jsonData.student_id) {
            return {
                student_id: jsonData.student_id,
                id_no: jsonData.id_no || jsonData.student_id,
                name: jsonData.name || 'Unknown Student',
                teacher_id: jsonData.teacher_id || null,
                section: jsonData.section || 'N/A'
            };
        }
    } catch (e) {
        const parts = qrData.split('|');
        if (parts.length >= 3) {
            return {
                student_id: parts[0],
                id_no: parts[0],
                name: parts[1],
                teacher_id: parts[2],
                section: parts[3] || 'N/A'
            };
        }
    }
    return null;
}

    fetch('/api/semester/time-sessions')
    .then(response => response.json())
    .then(data => {
        amTimeInStart = toMinutes(data.am_time_in_start);
        amTimeInEnd = toMinutes(data.am_time_in_end);
        pmTimeOutStart = toMinutes(data.pm_time_out_start);
        pmTimeOutEnd = toMinutes(data.pm_time_out_end);
    });

    function toMinutes(timeStr) {
    const [h, m] = timeStr.split(':');
    return parseInt(h) * 60 + parseInt(m);
    }


 function determineAttendanceType(parsedData, timeValidation) {
    const now = new Date();
    const currentHour = now.getHours();
    const currentMinutes = now.getMinutes();
    const currentTimeInMinutes = currentHour * 60 + currentMinutes;
    
    if (currentTimeInMinutes >= amTimeInStart && currentTimeInMinutes <= amTimeInEnd) {
         return getNextAttendanceTypeForStudent(parsedData, 'Time In');
    } else if (currentTimeInMinutes >= pmTimeOutStart && currentTimeInMinutes <= pmTimeOutEnd) {
         return getNextAttendanceTypeForStudent(parsedData, 'Time Out');
    }
    
     return getNextAttendanceTypeForStudent(parsedData, null);
}

 function getNextAttendanceTypeForStudent(parsedData, defaultType) {
     const attendanceList = document.getElementById('attendance-list');
    const recentRecords = attendanceList ? attendanceList.querySelectorAll('.material-attendance-record, .attendance-record') : [];
    
     let lastAttendanceType = null;
    for (const record of recentRecords) {
        const recordName = record.querySelector('.record-name')?.textContent;
        const recordId = record.querySelector('.record-id')?.textContent;
        
        if (recordName === parsedData.name || recordId?.includes(parsedData.id_no)) {
             const badge = record.querySelector('.time-in-badge, .time-out-badge, .time-badge');
            if (badge) {
                let badgeText = badge.textContent.trim();
                 if (record.querySelector('.time-out-badge')) {
                    lastAttendanceType = 'Time Out';
                } else if (badgeText.includes('Time Out')) {
                    lastAttendanceType = 'Time Out';
                } else if (badgeText.includes('Time In') || record.querySelector('.time-badge')) {
                    lastAttendanceType = 'Time In';
                }
                break;  
            }
        }
    }
    
     if (defaultType) {
         if (defaultType === 'Time In' && lastAttendanceType === 'Time In') {
            return 'Time Out';
        }
         if (defaultType === 'Time Out' && lastAttendanceType === 'Time Out') {
            return 'Time In';
        }
         return defaultType;
    }
    
     if (lastAttendanceType === 'Time In') {
        return 'Time Out';
    } else {
         return 'Time In';
    }
}

 function validateSessionTime() {
    const now = new Date();
    const currentTime = now.getHours() * 100 + now.getMinutes(); // Convert to HHMM format
    
     const periodInfo = currentPeriodStatus || {};
    
     const sessionHours = {
        morning: { start: 700, end: 1200 }, // 7:00 AM - 12:00 PM
        afternoon: { start: 1300, end: 1800 } // 1:00 PM - 6:00 PM
    };
    
    const isWithinMorning = currentTime >= sessionHours.morning.start && currentTime <= sessionHours.morning.end;
    const isWithinAfternoon = currentTime >= sessionHours.afternoon.start && currentTime <= sessionHours.afternoon.end;
    const isValid = isWithinMorning || isWithinAfternoon;
    
    return {
        isValid: isValid,
        period: isWithinMorning ? 'morning' : isWithinAfternoon ? 'afternoon' : 'outside',
        currentTime: now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }),
        sessionStart: isWithinMorning ? '7:00 AM' : '1:00 PM',
        sessionEnd: isWithinMorning ? '12:00 PM' : '6:00 PM',
        timeRemaining: calculateTimeRemaining(currentTime, isWithinMorning, isWithinAfternoon)
    };
}

function calculateTimeRemaining(currentTime, isWithinMorning, isWithinAfternoon) {
    if (isWithinMorning) {
        const endTime = 1200; // 12:00 PM
        const remaining = Math.max(0, endTime - currentTime);
        const hours = Math.floor(remaining / 100);
        const minutes = remaining % 100;
        return `${hours}h ${minutes}m`;
    } else if (isWithinAfternoon) {
        const endTime = 1800; // 6:00 PM
        const remaining = Math.max(0, endTime - currentTime);
        const hours = Math.floor(remaining / 100);
        const minutes = remaining % 100;
        return `${hours}h ${minutes}m`;
    }
    return '0m';
}

 function displayMaterialStudentCard(parsedData) {
    const cardContainer = document.getElementById('material-student-card') || createMaterialCardContainer();
    
    cardContainer.innerHTML = `
        <div class="material-card scanning">
            <div class="card-header">
                <div class="scan-indicator">
                    <div class="pulse-ring"></div>
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="card-title">QR Code Detected</div>
            </div>
            <div class="card-content">
                <div class="student-profile">
                    <div class="student-photo-container" id="material-student-photo">
                        <div class="photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="student-info">
                        <div class="info-row">
                            <span class="label">ID Number:</span>
                            <span class="value" id="material-id-no">${parsedData.id_no}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Full Name:</span>
                            <span class="value" id="material-name">${parsedData.name}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Section:</span>
                            <span class="value" id="material-section">${parsedData.section}</span>
                        </div>
                        <div class="info-row time-info">
                            <span class="label">Status:</span>
                            <span class="value" id="material-status">Validating...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
     loadStudentPhoto(parsedData);
}

 function updateStudentPhotoInCard(studentData) {
    const photoContainer = document.getElementById('material-student-photo');
    if (!photoContainer || !studentData.picture) return;
    
    const img = new Image();
    img.onload = function() {
        photoContainer.innerHTML = `
            <img src="/storage/student_pictures/${studentData.picture}" alt="${studentData.name}" class="student-photo">
        `;
    };
    
    img.onerror = function() {
         console.log('Failed to load student photo from server data:', studentData.picture);
    };
    
    img.src = `/storage/student_pictures/${studentData.picture}`;
}

 function updateMaterialCardWithTime(parsedData, timeValidation, attendanceType) {
    const statusElement = document.getElementById('material-status');
    const cardElement = document.querySelector('.material-card');
    
     statusElement.innerHTML = `<span class="${attendanceType === 'Time In' ? 'time-in-badge' : 'time-out-badge'}">${attendanceType}</span>`;
    statusElement.className = 'value valid-time';
    cardElement.className = 'material-card valid';
    
     const timeInfo = document.createElement('div');
    timeInfo.className = 'info-row';
    timeInfo.innerHTML = `
        <span class="label">Time:</span>
        <span class="value">${timeValidation.currentTime}</span>
    `;
    document.querySelector('.student-info').appendChild(timeInfo);
}

 function recordMaterialAttendance(parsedData, attendanceType, result) {
     const cardElement = document.querySelector('.material-card');
    cardElement.className = 'material-card recording';
    
    const statusElement = document.getElementById('material-status');
    statusElement.innerHTML = `<span class="recording-badge">Recording...</span>`;
    
     setTimeout(() => {
        cardElement.className = 'material-card recorded';
        statusElement.innerHTML = `<span class="recorded-badge">Recorded ✓</span>`;
        
        updateMaterialAttendanceList(parsedData, attendanceType, result.recorded_time || new Date().toLocaleTimeString());
    }, 1000);
}

function loadStudentPhoto(parsedData) {
    const photoContainer = document.getElementById('material-student-photo');
    if (!photoContainer) return;
    
     if (parsedData.picture) {
        const img = new Image();
        img.onload = function() {
            photoContainer.innerHTML = `
                <img src="/storage/student_pictures/${parsedData.picture}" alt="${parsedData.name}" class="student-photo">
            `;
        };
        
        img.onerror = function() {
            console.log('Failed to load student photo:', parsedData.picture);
            // Keep existing placeholder
        };
        
        img.src = `/storage/student_pictures/${parsedData.picture}`;
    }
 }

 function updateMaterialAttendanceList(parsedData, attendanceType, recordedTime) {
    const attendanceList = document.getElementById('attendance-list');
    const attendanceCount = document.getElementById('attendance-count');
    
    const newRecord = document.createElement('div');
    newRecord.className = 'material-attendance-record';
    newRecord.innerHTML = `
        <div class="record-card">
            <div class="record-avatar" id="record-avatar-${parsedData.student_id}">
                <i class="fas fa-user"></i>
            </div>
            <div class="record-info">
                <div class="record-name">${parsedData.name}</div>
                <div class="record-id">ID: ${parsedData.id_no}</div>
                <div class="record-section">${parsedData.section}</div>
            </div>
            <div class="record-badge">
                <span class="${attendanceType === 'Time In' ? 'time-in-badge' : 'time-out-badge'}">
                    ${attendanceType}
                </span>
                <div class="record-time">${recordedTime}</div>
            </div>
        </div>
    `;
    
    newRecord.style.animation = 'materialSlideIn 0.5s ease-out';
    attendanceList.insertBefore(newRecord, attendanceList.firstChild);
    
     loadRecordPhoto(parsedData, `record-avatar-${parsedData.student_id}`);
    
     const records = attendanceList.querySelectorAll('.material-attendance-record');
    if (records.length > 7) {
        for (let i = 7; i < records.length; i++) {
            records[i].remove();
        }
    }
    
     const currentCount = parseInt(attendanceCount.textContent) + 1;
    attendanceCount.textContent = currentCount;
}

function loadRecordPhoto(parsedData, containerId) {
    const photoContainer = document.getElementById(containerId);
    if (!photoContainer) return;
    
    // Try to load student photo from parsed data
    if (parsedData.picture) {
        const img = new Image();
        img.onload = function() {
            photoContainer.innerHTML = `
                <img src="/storage/student_pictures/${parsedData.picture}" alt="${parsedData.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
            `;
        };
        
        img.onerror = function() {
             photoContainer.innerHTML = parsedData.name.charAt(0).toUpperCase();
            photoContainer.style.fontSize = '18px';
            photoContainer.style.fontWeight = 'bold';
        };
        
        img.src = `/storage/student_pictures/${parsedData.picture}`;
    } else {
         photoContainer.innerHTML = parsedData.name.charAt(0).toUpperCase();
        photoContainer.style.fontSize = '18px';
        photoContainer.style.fontWeight = 'bold';
    }
}

 function displayMaterialErrorCard(errorMessage) {
    const cardContainer = document.getElementById('material-student-card') || createMaterialCardContainer();
    
    cardContainer.innerHTML = `
        <div class="material-card error">
            <div class="card-header">
                <div class="error-indicator">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="card-title">Scan Error</div>
            </div>
            <div class="card-content">
                <div class="error-message">${errorMessage}</div>
                <div class="error-action">Please try scanning again</div>
            </div>
        </div>
    `;
}

 function createMaterialCardContainer() {
    let container = document.getElementById('material-student-card');
    if (!container) {
        container = document.createElement('div');
        container.id = 'material-student-card';
        container.className = 'material-card-container';
        
         const mainPanel = document.querySelector('.main-panel .panel-content') || document.body;
        mainPanel.appendChild(container);
    }
    return container;
}

// Material Design Snackbar
function showMaterialSnackbar(message, type = 'info') {
    // Remove existing snackbar
    const existing = document.querySelector('.material-snackbar');
    if (existing) {
        existing.remove();
    }
    
    const snackbar = document.createElement('div');
    snackbar.className = `material-snackbar ${type}`;
    snackbar.innerHTML = `
        <div class="snackbar-content">
            <span class="snackbar-message">${message}</span>
        </div>
    `;
    
    document.body.appendChild(snackbar);
    
    // Animate in
    setTimeout(() => snackbar.classList.add('show'), 100);
    
    // Auto-hide after delay
    const hideDelay = type === 'error' ? 5000 : type === 'warning' ? 4000 : 3000;
    setTimeout(() => {
        snackbar.classList.remove('show');
        setTimeout(() => snackbar.remove(), 300);
    }, hideDelay);
}

// Material Design Loader
function showMaterialLoader(show) {
    let loader = document.getElementById('material-loader');
    
    if (show && !loader) {
        loader = document.createElement('div');
        loader.id = 'material-loader';
        loader.className = 'material-loader';
        loader.innerHTML = `
            <div class="loader-backdrop"></div>
            <div class="loader-content">
                <div class="material-spinner"></div>
                <div class="loader-text">Processing...</div>
            </div>
        `;
        document.body.appendChild(loader);
    } else if (!show && loader) {
        loader.remove();
    }
}

// Handle Material QR Result
function handleMaterialQRResult(result, parsedData, timeValidation, attendanceType) {
    setTimeout(() => {
        resetToMaterialWaitingState();
    }, 5000); // Extended to 5 seconds
}

// Reset to Material Waiting State
function resetToMaterialWaitingState() {
    const cardContainer = document.getElementById('material-student-card');
    if (cardContainer) {
        cardContainer.innerHTML = `
            <div class="material-card waiting">
                <div class="card-header">
                    <div class="waiting-indicator">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="card-title">Ready to Scan</div>
                </div>
                <div class="card-content">
                    <div class="waiting-message">
                        Point your scanner at a QR code to record attendance
                    </div>
                </div>
            </div>
        `;
    }
}

 function handleQRResult(result) {
    if (result.success) {
        displayStudentInfo(result.student, result);
        updateAttendanceList(result.student, result.recorded_time || new Date().toLocaleString(), result.status);
        
        clearInlineNotification();
        
        const statusMsg = result.already_present 
            ? `Already recorded today`
            : `✅ Attendance recorded successfully`;
        
        showInlineNotification(statusMsg, result.already_present ? 'warning' : 'success');
        playNotificationSound(!result.already_present);
    } else {
        if (result.student) {
            displayStudentInfoAlways(result.student, result.status, result.period_info);
        } else {
            showInlineStatus(`❌ ${result.message || 'Failed to verify QR code'}`, 'error');
            playNotificationSound(false);
            displayErrorInfo(result.message || 'Invalid QR Code');
        }
    }
}


function displayStudentInfo(student, attendanceData) {
    const isDuplicate = attendanceData.already_present || attendanceData.status?.includes('already');
    
     updateStatusBadge(isDuplicate ? 'ALREADY RECORDED' : 'ATTENDANCE RECORDED!', isDuplicate ? '#ff9800' : '#4CAF50');
    
     updateInfoCards(student);
    
    clearInlineNotification();
    currentStudentData = { student, attendanceData };
    
    setTimeout(() => {
        resetToMaterialWaitingState();
    }, isDuplicate ? 5000 : 5000); // Both cases now 5 seconds
}

function displayStudentInfoAlways(student, status, periodInfo) {
    let badgeText, badgeColor, notificationMessage, notificationType;
    
    if (status === 'outside_recording_period') {
        badgeText = 'SCAN SUCCESS - NOT RECORDED';
        badgeColor = '#ff9800';
        notificationMessage = periodInfo && periodInfo.next_period 
            ? `Recording blocked - Next period: ${periodInfo.next_period.period_name} at ${periodInfo.next_period.start_time}`
            : 'Recording blocked - Outside attendance period';
        notificationType = 'warning';
    } else {
        badgeText = 'SCAN ERROR';
        badgeColor = '#f44336';
        notificationMessage = 'QR code not recognized or invalid';
        notificationType = 'error';
    }

    updateStatusBadge(badgeText, badgeColor);
    
     updateInfoCards(student);
    
    showInlineNotification(notificationMessage, notificationType);
    
    setTimeout(() => {
        resetToMaterialWaitingState();
    }, 5000); 
}

function displayErrorInfo(errorMessage) {
    updateStatusBadge('SCAN ERROR', '#f44336');
    
     updateInfoCards({ name: 'ERROR', section: 'INVALID' }, 'FAILED');

    setTimeout(() => {
        resetToMaterialWaitingState();
    }, 5000); 
}

function updateStatusBadge(text, color) {
    const statusBadge = document.getElementById('status-badge');
    statusBadge.textContent = text;
    statusBadge.style.background = color;
}

function updateInfoCards(student, timeValue = null) {
    const currentTime = timeValue || new Date().toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });

    document.getElementById('name-value').textContent = student.name || 'ERROR';
    document.getElementById('section-value').textContent = student.section || 'INVALID';
    document.getElementById('time-value').textContent = timeValue || currentTime;
}


function showInlineStatus(message, type) {
    const statusBadge = document.getElementById('status-badge');
    const infoElement = document.getElementById('student-info');
    const photoElement = document.getElementById('student-photo');
    
    
    let badgeColor = '#6c757d';
    if (type === 'success') badgeColor = '#28a745';
    else if (type === 'error') badgeColor = '#dc3545';
    else if (type === 'warning') badgeColor = '#ffc107';
    else if (type === 'info') badgeColor = '#17a2b8';
    
    statusBadge.style.background = badgeColor;
    statusBadge.textContent = type.toUpperCase();
    
    
    let iconClass = 'fas fa-info-circle';
    let iconColor = badgeColor;
    if (type === 'success') iconClass = 'fas fa-check-circle';
    else if (type === 'error') iconClass = 'fas fa-exclamation-triangle';
    else if (type === 'warning') iconClass = 'fas fa-exclamation-triangle';
    else if (type === 'info') iconClass = 'fas fa-hourglass-half';
    
    photoElement.innerHTML = `<i class="${iconClass}" style="color: ${iconColor}; font-size: 50px;"></i>`;
    
    
    infoElement.innerHTML = `
        <div class="student-name" style="color: ${iconColor};">${type.toUpperCase()}</div>
        <div class="student-details">${message}</div>
        <div class="student-details">Please wait...</div>
    `;
}

function showInlineNotification(message, type) {
    const notificationArea = document.getElementById('notification-area');
    const notificationContent = document.getElementById('notification-content');
    
    if (!notificationArea || !notificationContent) return;

    notificationContent.className = 'notification-content';
    
    if (type) {
        notificationContent.classList.add(type);
    }

    notificationContent.innerHTML = message;
    notificationArea.style.display = 'block';

    setTimeout(() => {
        notificationArea.style.display = 'none';
    }, 4000);
}

function clearInlineNotification() {
    const notificationArea = document.getElementById('notification-area');
    if (notificationArea) {
        notificationArea.style.display = 'none';
    }
}

function updateAttendanceList(student, recordedTime, status) {
    const attendanceList = document.getElementById('attendance-list');
    const attendanceCount = document.getElementById('attendance-count');

    const newRecord = document.createElement('div');
    newRecord.className = 'attendance-record';
    newRecord.style.animation = 'slideInRight 0.5s ease-out';
    
    // Determine if this is Time In or Time Out based on status
    const isTimeOut = status.includes('OUT') || status.includes('Time Out');
    const badgeClass = isTimeOut ? 'time-out-badge' : 'time-badge';
    const displayStatus = isTimeOut ? 'Time Out' : 'Time In';
    
    newRecord.innerHTML = `
        <div class="student-avatar">
            ${student.photo ? 
                `<img src="/storage/student_pictures/${student.photo}" alt="${student.name}">` :
                student.name.charAt(0)
            }
        </div>
        <div class="record-info">
            <div class="record-name">${student.name}</div>
            <div class="record-section">${student.section || 'N/A'}</div>
        </div>
        <div class="record-time">
            <div class="${badgeClass}">
                ${displayStatus} - ${recordedTime}
            </div>
        </div>
    `;

    attendanceList.insertBefore(newRecord, attendanceList.firstChild);

    const records = attendanceList.querySelectorAll('.attendance-record');
    if (records.length > 7) {
        for (let i = 7; i < records.length; i++) {
            records[i].remove();
        }
    }

    const currentCount = parseInt(attendanceCount.textContent) + 1;
    attendanceCount.textContent = currentCount;
}

function showStatus(message, type) {
    const container = document.getElementById('status-container');
    
    const statusDiv = document.createElement('div');
    statusDiv.className = `status-message status-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'exclamation-triangle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    statusDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${icon} me-1"></i>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(statusDiv);
    
    const removeDelay = type === 'error' ? 10000 : type === 'warning' ? 8000 : 3000;
    setTimeout(() => {
        if (statusDiv.parentNode) {
            statusDiv.parentNode.removeChild(statusDiv);
        }
    }, removeDelay);
}

function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    overlay.style.display = show ? 'flex' : 'none';
}

function playNotificationSound(success) {
    try {
        const audioContext = new(window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (success) {
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
        } else {
            oscillator.frequency.setValueAtTime(300, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(200, audioContext.currentTime + 0.2);
        }

        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + (success ? 0.2 : 0.4));

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + (success ? 0.2 : 0.4));
    } catch (e) {
        
    }
}

// Success Popup Function - matches the design in the image
function showSuccessPopup(student, attendanceType, recordedTime) {
    // Remove any existing popup
    const existingPopup = document.getElementById('success-popup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Create popup overlay
    const overlay = document.createElement('div');
    overlay.id = 'success-popup';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

    // Create popup content
    const popup = document.createElement('div');
    popup.style.cssText = `
        background: white;
        border-radius: 20px;
        padding: 0;
        width: 400px;
        max-width: 90vw;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        transform: scale(0.8);
        transition: transform 0.3s ease;
        overflow: hidden;
    `;

    // Create header
    const header = document.createElement('div');
    header.style.cssText = `
        background: #f8f9fa;
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #dee2e6;
    `;
    header.innerHTML = `
        <h5 style="margin: 0; color: #333; font-weight: 600;">QR CODE DETECTED</h5>
    `;

    // Create blue success section
    const successSection = document.createElement('div');
    successSection.style.cssText = `
        background: linear-gradient(135deg, #4285f4, #6fa8f5);
        color: white;
        padding: 30px 20px;
        text-align: center;
    `;
    
    // Create blue rounded rectangle icon
    const icon = document.createElement('div');
    icon.style.cssText = `
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        margin: 0 auto 20px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    `;
    icon.innerHTML = '<i class="fas fa-check" style="color: white;"></i>';
    
    successSection.appendChild(icon);
    successSection.innerHTML += `<h4 style="margin: 0; font-weight: 600;">TIME IN/TIME OUT RECORDED!</h4>`;

    // Create details table
    const detailsTable = document.createElement('div');
    detailsTable.style.cssText = `
        padding: 20px;
    `;
    
    const tableStyle = `
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    `;
    
    const cellStyle = `
        padding: 12px;
        border: 1px solid #dee2e6;
        text-align: left;
    `;
    
    const labelStyle = `
        ${cellStyle}
        background: #f8f9fa;
        font-weight: 600;
        width: 30%;
    `;
    
    detailsTable.innerHTML = `
        <table style="${tableStyle}">
            <tr>
                <td style="${labelStyle}">ID NO</td>
                <td style="${cellStyle}">${student.student_id || student.id || 'N/A'}</td>
            </tr>
            <tr>
                <td style="${labelStyle}">FULL NAME</td>
                <td style="${cellStyle}">${student.name || 'N/A'}</td>
            </tr>
            <tr>
                <td style="${labelStyle}">Section</td>
                <td style="${cellStyle}">${student.section || 'N/A'}</td>
            </tr>
            <tr>
                <td style="${labelStyle}">TIME</td>
                <td style="${cellStyle}">${recordedTime || new Date().toLocaleString()}</td>
            </tr>
        </table>
    `;

    // Create close button
    const closeButton = document.createElement('button');
    closeButton.style.cssText = `
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 24px;
        color: #666;
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    `;
    closeButton.innerHTML = '×';
    closeButton.onmouseover = () => closeButton.style.background = '#f0f0f0';
    closeButton.onmouseout = () => closeButton.style.background = 'none';

     popup.appendChild(header);
    popup.appendChild(successSection);
    popup.appendChild(detailsTable);
    popup.appendChild(closeButton);
    overlay.appendChild(popup);
    document.body.appendChild(overlay);

     setTimeout(() => {
        overlay.style.opacity = '1';
        popup.style.transform = 'scale(1)';
    }, 10);

     const autoCloseTimer = setTimeout(() => {
        closePopup();
    }, 5000);

     function closePopup() {
        clearTimeout(autoCloseTimer);
        overlay.style.opacity = '0';
        popup.style.transform = 'scale(0.8)';
        setTimeout(() => overlay.remove(), 300);
    }

     closeButton.onclick = closePopup;
    overlay.onclick = (e) => {
        if (e.target === overlay) closePopup();
    };
}


