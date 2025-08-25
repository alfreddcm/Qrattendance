@extends('admin/sidebar')
@section('title', 'SMS Messages')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center" style="margin-left: 1rem;" >
        <div>
            <h4 class="fs-5 mb-1">
                <i class="fas fa-sms me-2"></i>
                SMS Messages
            </h4>
            <p class="subtitle fs-6 mb-0">Send SMS notifications and announcements</p>
        </div>
        
    </div>
</div>

<div class="container-fluid">

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Recipient Type</label>
                            <select class="form-select" id="recipientTypeFilter">
                                <option value="">All Types</option>
                                <option value="teacher">Teachers</option>
                                <option value="student">Students</option>
                                <option value="broadcast">Broadcast</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="sent">Sent</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary btn-action" onclick="loadMessages()">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-action" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card stats-card primary">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>SMS Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-value text-success" id="totalSent">0</div>
                            <div class="stat-label">Sent</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-value text-danger" id="totalFailed">0</div>
                            <div class="stat-label">Failed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="mb-0"><i class="fas fa-list me-1"></i>Message History</h6>
                </div>
                <div class="col text-end">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-secondary btn-action" onclick="loadMessages()" title="Refresh Messages">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button class="btn btn-outline-primary btn-action" id="checkSmsStatusBtn" onclick="testSMSGateway()">
                            <i class="fas fa-signal me-1"></i><span id="checkSmsStatusText">Check SMS Status</span>
                            <span id="checkSmsStatusSpinner" class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                        </button>
                        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="fas fa-plus me-1"></i>Send SMS
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-compact" id="messagesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Recipient</th>
                            <th>Message</th>
                            <th>Sender</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="messagesTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-2">Loading messages...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav aria-label="Messages pagination" class="mt-3">
                <ul class="pagination pagination-sm justify-content-center" id="pagination">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Compose SMS Modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title fs-6" id="composeModalLabel">
                    <i class="fas fa-sms me-2"></i>Send SMS Message
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2 px-3">
                <form id="smsForm">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Send To</label>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="recipientType" id="allTeachersRadio" value="all_teachers" checked onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="allTeachersRadio">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>All Teachers
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="recipientType" id="specificTeacherRadio" value="specific_teacher" onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="specificTeacherRadio">
                                    <i class="fas fa-user-tie me-2"></i>Specific Teacher
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="recipientType" id="allParentsRadio" value="all_parents" onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="allParentsRadio">
                                    <i class="fas fa-users me-2"></i>All Student Parents
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="recipientType" id="specificStudentRadio" value="specific_student" onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="specificStudentRadio">
                                    <i class="fas fa-user-graduate me-2"></i>Specific Student
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipientType" id="customNumberRadio" value="custom" onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="customNumberRadio">
                                    <i class="fas fa-phone me-2"></i>Custom Number
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="allTeachersDiv">
                                <label class="form-label mb-1">Recipients</label>
                                <div class="alert alert-info small py-1 px-2 mb-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Message will be sent to all teachers in the system
                                </div>
                            </div>
                            <div id="teacherSelectDiv" style="display:none;">
                                <label for="teacherSelect" class="form-label mb-1">Select Teacher</label>
                                <select class="form-select form-select-sm" id="teacherSelect" onchange="onTeacherSelect()">
                                    <option value="">Choose a teacher...</option>
                                </select>
                            </div>
                            <div id="allParentsDiv" style="display:none;">
                                <label class="form-label mb-1">Recipients</label>
                                <div class="alert alert-info small py-1 px-2 mb-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Message will be sent to all parents of students in the system
                                </div>
                            </div>
                            <div id="studentSelectDiv" style="display:none;">
                                <label for="studentSelect" class="form-label mb-1">Select Student</label>
                                <select class="form-select form-select-sm" id="studentSelect" onchange="onStudentSelect()">
                                    <option value="">Choose a student...</option>
                                </select>
                            </div>
                            <div id="customNumberDiv" style="display:none;">
                                <label for="customNumber" class="form-label mb-1">Mobile Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">+63</span>
                                    <input type="text" class="form-control" id="customNumber" placeholder="9123456789" maxlength="10" pattern="[0-9]{10}">
                                </div>
                                <div class="form-text small">Enter 10 digits (e.g., 9123456789)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2" id="teacherInfoPanel" style="display:none;">
                        <div class="card border-info mb-0">
                            <div class="card-header bg-light py-1 px-2">
                                <h6 class="mb-0 small"><i class="fas fa-chalkboard-teacher me-1"></i>Teacher Information</h6>
                            </div>
                            <div class="card-body py-1 px-2">
                                <div class="row g-1">
                                    <div class="col-6">
                                        <small class="text-muted">Teacher:</small>
                                        <div class="fw-bold small" id="teacherInfoName">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Section:</small>
                                        <div class="fw-bold small" id="teacherInfoSection">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Contact:</small>
                                        <div class="fw-bold small" id="teacherInfoContact">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">School:</small>
                                        <div class="fw-bold small" id="teacherInfoSchool">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2" id="studentInfoPanel" style="display:none;">
                        <div class="card border-info mb-0">
                            <div class="card-header bg-light py-1 px-2">
                                <h6 class="mb-0 small"><i class="fas fa-user-graduate me-1"></i>Student Information</h6>
                            </div>
                            <div class="card-body py-1 px-2">
                                <div class="row g-1">
                                    <div class="col-6">
                                        <small class="text-muted">Student:</small>
                                        <div class="fw-bold small" id="studentInfoName">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Teacher:</small>
                                        <div class="fw-bold small" id="studentInfoTeacher">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Contact:</small>
                                        <div class="fw-bold small" id="studentInfoContact">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Parent:</small>
                                        <div class="fw-bold small" id="studentInfoParent">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label for="messageTemplate" class="form-label mb-1">Message Template</label>
                        <select class="form-select form-select-sm" id="messageTemplate" onchange="applyTemplate()">
                            <option value="">Custom Message</option>
                            <option value="announcement">General Announcement</option>
                            <option value="meeting">Meeting Notice</option>
                            <option value="reminder">Reminder</option>
                            <option value="urgent">Urgent Notice</option>
                            <option value="training">Training Notice</option>
                            <option value="system">System Maintenance</option>
                            <option value="holiday">Holiday Notice</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label for="messageText" class="form-label mb-1">Message</label>
                        <textarea class="form-control form-control-sm" id="messageText" rows="4" maxlength="1000" placeholder="Type your message here..."></textarea>
                        <div class="d-flex justify-content-between">
                            <div class="form-text small">Characters: <span id="charCount">0</span>/1000</div>
                            <div class="form-text small">Estimated SMS: <span id="smsCount">1</span></div>
                        </div>
                    </div>
                    
                    <!-- Auto Signature Settings -->
                    <div class="mb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="autoSignature" checked>
                            <label class="form-check-label small" for="autoSignature">
                                Add automatic signature
                            </label>
                        </div>
                        <div class="mt-1" id="signaturePreview">
                            <small class="text-muted">Signature preview:</small>
                            <div class="p-1 bg-light rounded small" id="signatureText">
                                <div class="fw-bold">{{ Auth::user()->name ?? 'Admin' }}</div>
                                <div>System Administrator</div>
                                <div>{{ Auth::user()->school->name ?? 'School Administration' }}</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="sendSMS()">
                    <i class="fas fa-paper-plane me-1"></i>Send SMS
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Message Detail Modal -->
<div class="modal fade" id="messageDetailModal" tabindex="-1" aria-labelledby="messageDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageDetailModalLabel">
                    <i class="fas fa-envelope me-2"></i>Message Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="messageDetailContent">
                <!-- content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="alertModalLabel">Alert</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="alertModalBody">
                <!-- Alert message will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<style>
.message-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Compact Info Panels */
#teacherInfoPanel .card-body,
#studentInfoPanel .card-body {
    font-size: 0.875rem;
}

#teacherInfoPanel .small,
#studentInfoPanel .small {
    font-size: 0.8rem !important;
}
</style>

<script>
let currentPage = 1;
let totalPages = 1;
let allTeachers = [];
let allStudents = [];

const messageTemplates = {
    announcement: "Good day! This is an important announcement from the administration. Please be informed that [DETAILS]. Thank you for your attention.",
    meeting: "Good day! You are invited to attend a meeting on [DATE] at [TIME]. The agenda includes [AGENDA]. Please confirm your attendance.",
    reminder: "Good day! This is a friendly reminder about [TOPIC]. Please ensure that [ACTION] is completed by [DATE].",
    urgent: "URGENT: This is an urgent notice regarding [SUBJECT]. Immediate attention is required. Please [ACTION] as soon as possible.",
    training: "Good day! We are pleased to inform you about an upcoming training session on [TOPIC] scheduled for [DATE]. Please [ACTION].",
    system: "System Maintenance Notice: The attendance system will be temporarily unavailable on [DATE] from [START_TIME] to [END_TIME] for maintenance.",
    holiday: "Holiday Notice: Please be informed that [DATE] is declared a non-working holiday. Classes and office work are suspended."
};

const adminInfo = {
    name: '{{ Auth::user()->name ?? "Administrator" }}',
    title: 'System Administrator',
    school: '{{ Auth::user()->school->name ?? "School Administration" }}'
};

function loadTeachers() {
    console.log('Loading teachers...');
    fetch('/admin/get-teachers')
        .then(response => {
            console.log('Teachers API response:', response);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Teachers loaded:', data.teachers);
                allTeachers = data.teachers;
                
                const teacherSelect = $('#teacherSelect');
                teacherSelect.empty().append('<option value="">Choose a teacher...</option>');
                
                data.teachers.forEach(teacher => {
                    const option = $('<option></option>')
                        .val(teacher.id)
                        .data('section', teacher.section ? teacher.section.name : '')
                        .data('phone', teacher.phone_number || '')
                        .data('school', teacher.school ? teacher.school.name : '')
                        .text(teacher.name.trim());
                    teacherSelect.append(option);
                });
            } else {
                console.error('Error loading teachers:', data.message);
                showAlert('Error loading teachers: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading teachers:', error);
            showAlert('Error loading teachers: ' + error.message, 'danger');
        });
}

function loadStudents() {
    console.log('Loading students...');
    fetch('/admin/get-all-students')
        .then(response => {
            console.log('Students API response:', response);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Students loaded:', data.students);
                allStudents = data.students;
                
                const studentSelect = $('#studentSelect');
                studentSelect.empty().append('<option value="">Choose a student...</option>');
                
                data.students.forEach(student => {
                    const option = $('<option></option>')
                        .val(student.id)
                        .data('teacher', student.user ? student.user.name : 'Unknown')
                        .data('parent', student.contact_person_contact || '')
                        .data('parent-name', student.contact_person_name || '')
                        .data('school', student.school ? student.school.name : '')
                        .text(student.name.trim());
                    studentSelect.append(option);
                });
            } else {
                console.error('Error loading students:', data.message);
                showAlert('Error loading students: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
            showAlert('Error loading students: ' + error.message, 'danger');
        });
}

function loadMessages() {
    const filters = {
        recipient_type: $('#recipientTypeFilter').val(),
        status: $('#statusFilter').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        page: currentPage
    };
    
    Object.keys(filters).forEach(key => {
        if (!filters[key]) delete filters[key];
    });
    
    const queryString = new URLSearchParams(filters).toString();
    
    fetch(`/admin/outbound-messages?${queryString}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API response:', data);
            if (data.success) {
                if (!data.messages || !Array.isArray(data.messages) || data.messages.length === 0) {
                    displayEmptyState('No messages found');
                    updatePagination(null);
                } else {
                    displayMessages(data.messages);
                    updatePagination(data.pagination);
                }
                updateStats(data.stats);
            } else {
                console.error('API returned error:', data.message);
                showAlert('Error loading messages: ' + (data.message || 'Unknown error'), 'danger');
                displayEmptyState('Error loading messages');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading messages', 'danger');
            displayEmptyState('Error loading messages');
        });
}

function displayMessages(messages) {
    const tbody = $('#messagesTableBody');
    
    if (!messages || !Array.isArray(messages) || messages.length === 0) {
        displayEmptyState('No messages found');
        return;
    }
    
    console.log('Displaying messages:', messages);
    
    const rows = messages.map(message => {
        const statusBadge = getStatusBadge(message.status);
        
        // Handle different recipient types
        let recipientDisplay = '';
        if (message.recipient_type === 'broadcast') {
            recipientDisplay = `Broadcast (${message.recipient_count || 'Multiple'})`;
        } else if (message.recipient_type === 'teacher') {
            recipientDisplay = message.teacher ? `Teacher: ${message.teacher.name}` : 'Teacher';
        } else if (message.recipient_type === 'student') {
            recipientDisplay = message.student ? `Student: ${message.student.name}` : 'Student';
        } else {
            recipientDisplay = 'Custom Number';
        }
        
        const senderName = message.admin ? message.admin.name : (message.teacher ? message.teacher.name : 'System');
        const messagePreview = message.message && message.message.length > 70 ? 
            message.message.substring(0, 70) + '...' : (message.message || 'No message');
        
        return `
            <tr>
                <td class="py-1 small">${formatDateTimeCompact(message.created_at)}</td>
                <td class="py-1 small">${recipientDisplay}</td>
                <td class="py-1 small">
                    <span class="message-preview" title="${escapeHtml(message.message)}">
                        ${escapeHtml(messagePreview)}
                    </span>
                </td>
                <td class="py-1 small">${senderName}</td>
                <td class="py-1">${statusBadge}</td>
                <td class="py-1">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewMessage(${message.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    tbody.html(rows);
}

function displayEmptyState(message) {
    $('#messagesTableBody').html(`
        <tr>
            <td colspan="6" class="text-center py-4">
                <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                <div>${message}</div>
            </td>
        </tr>
    `);
}

function getStatusBadge(status) {
    const badges = {
        pending: '<span class="badge bg-warning">Pending</span>',
        sent: '<span class="badge bg-info">Sent</span>',
        delivered: '<span class="badge bg-success">Delivered</span>',
        failed: '<span class="badge bg-danger">Failed</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function updatePagination(pagination) {
    if (!pagination) return;
    
    currentPage = pagination.current_page;
    totalPages = pagination.last_page;
    
    const paginationEl = $('#pagination');
    
    if (totalPages <= 1) {
        paginationEl.empty();
        return;
    }
    
    let paginationHtml = '';
    
    if (currentPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a></li>`;
    }
    
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
        const activeClass = i === currentPage ? 'active' : '';
        paginationHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
    }
    
    if (currentPage < totalPages) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a></li>`;
    }
    
    paginationEl.html(paginationHtml);
}

function updateStats(stats) {
    if (stats) {
        $('#totalSent').text(stats.sent || 0);
        $('#totalFailed').text(stats.failed || 0);
    }
}

function changePage(page) {
    currentPage = page;
    loadMessages();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    currentPage = 1;
    loadMessages();
}

function toggleRecipientOptions() {
    const selectedType = $('input[name="recipientType"]:checked').val();
    
    // Hide all option divs and info panels
    $('#allTeachersDiv').hide();
    $('#teacherSelectDiv').hide();
    $('#allParentsDiv').hide();
    $('#studentSelectDiv').hide();
    $('#customNumberDiv').hide();
    $('#teacherInfoPanel').hide();
    $('#studentInfoPanel').hide();
    
    // Show relevant options based on selection
    if (selectedType === 'all_teachers') {
        $('#allTeachersDiv').show();
    } else if (selectedType === 'specific_teacher') {
        $('#teacherSelectDiv').show();
        const selectedValue = $('#teacherSelect').val();
        if (selectedValue) {
            $('#teacherInfoPanel').show();
            onTeacherSelect();
        }
    } else if (selectedType === 'all_parents') {
        $('#allParentsDiv').show();
    } else if (selectedType === 'specific_student') {
        $('#studentSelectDiv').show();
        const selectedValue = $('#studentSelect').val();
        if (selectedValue) {
            $('#studentInfoPanel').show();
            onStudentSelect();
        }
    } else if (selectedType === 'custom') {
        $('#customNumberDiv').show();
    }
}

function onTeacherSelect() {
    console.log('Teacher selection changed');
    const teacherSelect = $('#teacherSelect');
    const selectedValue = teacherSelect.val();
    const selectedOption = teacherSelect.find('option:selected');
    
    if (selectedValue && selectedValue !== '') {
        const teacherName = selectedOption.text();
        const teacherSection = selectedOption.data('section') || 'Not assigned';
        const teacherPhone = selectedOption.data('phone') || 'No phone contact';
        const teacherSchool = selectedOption.data('school') || 'Unknown';
        
        $('#teacherInfoName').text(teacherName);
        $('#teacherInfoSection').text(teacherSection);
        $('#teacherInfoContact').text(teacherPhone);
        $('#teacherInfoSchool').text(teacherSchool);
        $('#teacherInfoPanel').show();
        
        const currentTemplate = $('#messageTemplate').val();
        if (currentTemplate) {
            applyTemplate();
        }
    } else {
        $('#teacherInfoPanel').hide();
    }
}

function onStudentSelect() {
    console.log('Student selection changed');
    const studentSelect = $('#studentSelect');
    const selectedValue = studentSelect.val();
    const selectedOption = studentSelect.find('option:selected');
    
    if (selectedValue && selectedValue !== '') {
        const studentName = selectedOption.text();
        const studentTeacher = selectedOption.data('teacher') || 'Unknown';
        const parentContact = selectedOption.data('parent') || 'No parent contact';
        const parentName = selectedOption.data('parent-name') || 'Unknown';
        
        $('#studentInfoName').text(studentName);
        $('#studentInfoTeacher').text(studentTeacher);
        $('#studentInfoContact').text(parentContact);
        $('#studentInfoParent').text(parentName);
        $('#studentInfoPanel').show();
        
        const currentTemplate = $('#messageTemplate').val();
        if (currentTemplate) {
            applyTemplate();
        }
    } else {
        $('#studentInfoPanel').hide();
    }
}

function applyTemplate() {
    const template = $('#messageTemplate').val();
    if (template && messageTemplates[template]) {
        const selectedType = $('input[name="recipientType"]:checked').val();
        let message = messageTemplates[template];
        
        const today = new Date().toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        message = message.replace(/\[DATE\]/g, today);
        
        // Replace placeholders based on recipient type
        if (selectedType === 'specific_teacher') {
            const selectedTeacher = $('#teacherSelect option:selected').text();
            if (selectedTeacher && selectedTeacher !== 'Choose a teacher...') {
                message = message.replace(/\[TEACHER_NAME\]/g, selectedTeacher);
            }
        } else if (selectedType === 'specific_student') {
            const selectedStudent = $('#studentSelect option:selected').text();
            if (selectedStudent && selectedStudent !== 'Choose a student...') {
                message = message.replace(/\[STUDENT_NAME\]/g, selectedStudent);
            }
        }
        
        // Replace common placeholders with placeholder text
        message = message.replace(/\[DETAILS\]/g, '[Please specify details]');
        message = message.replace(/\[TIME\]/g, '[Please specify time]');
        message = message.replace(/\[AGENDA\]/g, '[Please specify agenda]');
        message = message.replace(/\[TOPIC\]/g, '[Please specify topic]');
        message = message.replace(/\[ACTION\]/g, '[Please specify action]');
        message = message.replace(/\[SUBJECT\]/g, '[Please specify subject]');
        message = message.replace(/\[START_TIME\]/g, '[Start time]');
        message = message.replace(/\[END_TIME\]/g, '[End time]');
        
        $('#messageText').val(message).trigger('input');
        
        if ($('#autoSignature').is(':checked')) {
            addSignature();
        }
    }
}

function addSignature() {
    const messageArea = $('#messageText');
    let currentMessage = removeSignature(messageArea.val().trim());
    
    const signature = `\n\nFrom:\n${adminInfo.name}\n${adminInfo.title}\n${adminInfo.school}`;
    
    messageArea.val(currentMessage + signature).trigger('input');
}

function removeSignature(message) {
    const signatureStart = message.lastIndexOf('\n\nFrom:\n');
    if (signatureStart !== -1) {
        return message.substring(0, signatureStart);
    }
    return message;
}

function sendSMS() {
    const selectedType = $('input[name="recipientType"]:checked').val();
    let phoneNumber = '';
    let teacherId = null;
    let studentId = null;
    
    if (selectedType === 'all_teachers') {
        phoneNumber = 'all_teachers';
    } else if (selectedType === 'specific_teacher') {
        teacherId = $('#teacherSelect').val();
        
        if (!teacherId) {
            showAlert('Please select a teacher', 'warning');
            return;
        }
        
        const selectedOption = $('#teacherSelect option:selected');
        phoneNumber = selectedOption.data('phone');
        
        if (!phoneNumber) {
            showAlert('Selected teacher has no contact number', 'warning');
            return;
        }
    } else if (selectedType === 'all_parents') {
        phoneNumber = 'all_parents';
    } else if (selectedType === 'specific_student') {
        studentId = $('#studentSelect').val();
        
        if (!studentId) {
            showAlert('Please select a student', 'warning');
            return;
        }
        
        const selectedOption = $('#studentSelect option:selected');
        phoneNumber = selectedOption.data('parent');
        
        if (!phoneNumber) {
            showAlert('Selected student has no parent/guardian contact number', 'warning');
            return;
        }
    } else if (selectedType === 'custom') {
        const customNumberInput = $('#customNumber').val();
        if (!customNumberInput || customNumberInput.length !== 10) {
            showAlert('Please enter a valid 10-digit phone number', 'warning');
            return;
        }
        
        phoneNumber = '+63' + customNumberInput;
    } else {
        showAlert('Please select a recipient type', 'warning');
        return;
    }
    
    const message = $('#messageText').val().trim();
    if (!message) {
        showAlert('Please enter a message', 'warning');
        return;
    }
    
    const data = {
        number: phoneNumber,
        message: message,
        send_to_all: selectedType.includes('all_'),
        recipient_type: selectedType
    };
    
    if (teacherId) {
        data.teacher_id = teacherId;
    }
    
    if (studentId) {
        data.student_id = studentId;
    }
    
    const sendBtn = $('.modal-footer .btn-primary');
    sendBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sending...');
    
    fetch('/admin/send-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert('SMS sent successfully!', 'success');
            $('#composeModal').modal('hide');
            $('#smsForm')[0].reset();
            $('#charCount').text('0');
            $('#smsCount').text('1');
            $('#allTeachersRadio').prop('checked', true);
            toggleRecipientOptions();
            loadMessages();
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error sending SMS', 'danger');
    })
    .finally(() => {
        sendBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Send SMS');
    });
}

// Test SMS Gateway
function testSMSGateway() {
    const btn = document.getElementById('checkSmsStatusBtn');
    const text = document.getElementById('checkSmsStatusText');
    const spinner = document.getElementById('checkSmsStatusSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    text.textContent = 'Checking...';
    
    fetch('/admin/test-sms-gateway')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.reachable === true) {
                showAlert('SMS gateway is working!', 'success');
            } else if (data.status === 'success' && data.reachable === false) {
                showAlert('SMS test failed: Gateway not reachable', 'danger');
            } else {
                showAlert('SMS test failed: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error testing SMS gateway', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            text.textContent = 'Check SMS Status';
        });
}

function viewMessage(messageId) {
    fetch(`/admin/outbound-messages`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.messages.find(m => m.id == messageId);
                if (message) {
                    showMessageDetails(message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading message details', 'danger');
        });
}

function showMessageDetails(message) {
    // Handle different recipient types
    let recipientDisplay = '';
    let recipientIcon = '';
    
    if (message.recipient_type === 'broadcast') {
        recipientDisplay = `Broadcast (${message.recipient_count || 'Multiple'} recipients)`;
        recipientIcon = '<i class="fas fa-broadcast-tower text-primary me-1"></i>';
    } else if (message.recipient_type === 'teacher') {
        recipientDisplay = message.teacher ? message.teacher.name : 'Teacher';
        recipientIcon = '<i class="fas fa-chalkboard-teacher text-success me-1"></i>';
    } else if (message.recipient_type === 'student') {
        recipientDisplay = message.student ? message.student.name : 'Student';
        recipientIcon = '<i class="fas fa-user-graduate text-info me-1"></i>';
    } else {
        recipientDisplay = 'Custom Number: ' + (message.contact_number || 'Unknown');
        recipientIcon = '<i class="fas fa-phone text-warning me-1"></i>';
    }
    
    const senderName = message.admin ? message.admin.name : (message.teacher ? message.teacher.name : 'System');
    const content = `
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Date & Time:</strong></div>
            <div class="col-sm-9">${formatDateTime(message.created_at)}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Recipient:</strong></div>
            <div class="col-sm-9">${recipientIcon}${recipientDisplay}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Sender:</strong></div>
            <div class="col-sm-9"><i class="fas fa-user-shield text-primary me-1"></i>${senderName}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Type:</strong></div>
            <div class="col-sm-9">
                <span class="badge ${message.recipient_type === 'broadcast' ? 'bg-primary' : 'bg-info'}">
                    ${message.recipient_type === 'broadcast' ? 'Broadcast' : 'Individual'}
                </span>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Status:</strong></div>
            <div class="col-sm-9">${getStatusBadge(message.status)}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Message:</strong></div>
            <div class="col-sm-9">
                <div class="border p-2 bg-light rounded">${escapeHtml(message.message)}</div>
            </div>
        </div>
    `;
    
    $('#messageDetailContent').html(content);
    $('#messageDetailModal').modal('show');
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

function formatDateTimeCompact(dateTime) {
    const date = new Date(dateTime);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return 'Today ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + 
               date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showAlert(message, type) {
    const modalTitle = $('#alertModalLabel');
    const modalBody = $('#alertModalBody');
    const modal = $('#alertModal');
    
    const titles = {
        success: '<i class="fas fa-check-circle text-success me-2"></i>Success',
        danger: '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Error',
        warning: '<i class="fas fa-exclamation-triangle text-warning me-2"></i>Warning',
        info: '<i class="fas fa-info-circle text-info me-2"></i>Information'
    };
    
    modalTitle.html(titles[type] || titles.info);
    modalBody.text(message);
    
    modal.modal('show');
    
    if (type === 'success') {
        setTimeout(() => {
            modal.modal('hide');
        }, 3000);
    }
}

function formatCustomNumber() {
    const input = $('#customNumber');
    let value = input.val().replace(/\D/g, '');
    
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    
    input.val(value);
    
    if (value.length === 10 && value.startsWith('9')) {
        input.removeClass('is-invalid').addClass('is-valid');
    } else if (value.length > 0) {
        input.removeClass('is-valid').addClass('is-invalid');
    } else {
        input.removeClass('is-valid is-invalid');
    }
}

$(document).ready(function() {
    console.log('Document ready - initializing Admin SMS message interface...');
    console.log('jQuery version:', $.fn.jquery);
    
    // Load data
    loadTeachers();
    loadStudents();
    loadMessages();
    
    // Character count handler
    $('#messageText').on('input', function() {
        const text = $(this).val();
        const charCount = text.length;
        const smsCount = Math.ceil(charCount / 160) || 1;
        
        $('#charCount').text(charCount);
        $('#smsCount').text(smsCount);
    });
    
    console.log('Event handlers attached successfully');
    
    // Auto signature handler
    $('#autoSignature').on('change', function() {
        if (this.checked) {
            addSignature();
        } else {
            const messageArea = $('#messageText');
            let currentMessage = removeSignature(messageArea.val().trim());
            messageArea.val(currentMessage).trigger('input');
        }
    });
    
    // Selection handlers
    $('#teacherSelect').on('change', function() {
        onTeacherSelect();
    });
    
    $('#studentSelect').on('change', function() {
        onStudentSelect();
    });
    
    // Custom number formatting
    $('#customNumber').on('input', function() {
        formatCustomNumber();
    });
    
    // Recipient type change handler
    $('input[name="recipientType"]').on('change', function() {
        toggleRecipientOptions();
    });
    
    // Initialize recipient options
    toggleRecipientOptions();
    
    // Auto refresh messages every 30 seconds
    setInterval(loadMessages, 30000);
});
</script>

@endsection