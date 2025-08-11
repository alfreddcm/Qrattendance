@extends('teacher/sidebar')
@section('title', 'SMS Messages')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fs-5 mb-1">
                <span class="me-2">💬</span>
                SMS Messages
            </h4>
            <p class="subtitle fs-6 mb-0">View and manage SMS message history</p>
        </div>
        
    </div>
</div>

<div class="container mt-4">
    <!-- Filters and Stats -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-filter me-1"></i>Filters</h6>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Student</label>
                            <select class="form-select form-select-sm" id="studentFilter">
                                <option value="">All Students</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select form-select-sm" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="sent">Sent</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control form-control-sm" id="startDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="endDate">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary btn-sm" onclick="loadMessages()">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-1"></i>SMS Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-value text-success" id="totalSent">0</div>
                                <div class="stat-label">Sent</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-value text-danger" id="totalFailed">0</div>
                                <div class="stat-label">Failed</div>
                            </div>
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
                    <button class="btn btn-outline-primary btn-sm" onclick="testSMSGateway()">
                        <i class="fas fa-signal me-1"></i>Check SMS Status
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#composeModal">
                        <i class="fas fa-plus me-1"></i>Send SMS
                    </button>
                </div>
            </div>
        </div>


       
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="messagesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2 small">Date</th>
                            <th class="py-2 small">Student</th>
                            <th class="py-2 small">Message</th>
                            <th class="py-2 small">Teacher</th>
                            <th class="py-2 small">Status</th>
                            <th class="py-2 small">Actions</th>
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
                                <input class="form-check-input" type="radio" name="recipientType" id="allParentsRadio" value="all_parents" checked onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="allParentsRadio">
                                    <i class="fas fa-users me-2"></i>All Student Parents
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="recipientType" id="specificStudentRadio" value="specific_student" onchange="toggleRecipientOptions()">
                                <label class="form-check-label small" for="specificStudentRadio">
                                    <i class="fas fa-user me-2"></i>Specific Student
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
                            <div id="allParentsDiv">
                                <label class="form-label mb-1">Recipients</label>
                                <div class="alert alert-info small py-1 px-2 mb-1">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Message will be sent to all parents of students in your class
                                </div>
                            </div>
                            <div id="studentSelectDiv" style="display:none;">
                                <label for="studentSelect" class="form-label mb-1">Select Student</label>
                                <select class="form-select form-select-sm" id="studentSelect" onchange="onStudentSelect()">
                                    <option value="">Choose a student...</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" 
                                                data-phone="{{ $student->contact_person_contact ?? '' }}" 
                                                data-parent="{{ $student->contact_person_contact ?? '' }}" 
                                                data-parent-name="{{ $student->contact_person_name ?? 'Unknown' }}">
                                            {{ $student->name }}
                                        </option>
                                    @endforeach
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
                    
                    <!-- Student Information Panel -->
                    <div class="mb-2" id="studentInfoPanel" style="display:none;">
                        <div class="card border-info mb-0">
                            <div class="card-header bg-light py-1 px-2">
                                <h6 class="mb-0 small"><i class="fas fa-user me-1"></i>Student Information</h6>
                            </div>
                            <div class="card-body py-1 px-2">
                                <div class="row g-1">
                                    <div class="col-6">
                                        <small class="text-muted">Student:</small>
                                        <div class="fw-bold small" id="studentInfoName">-</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Section:</small>
                                        <div class="fw-bold small" id="studentInfoSection">-</div>
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
                            <option value="absent">Absent Today</option>
                            <option value="reminder">General Reminder</option>
                            <option value="meeting">Parent Meeting</option>
                            <option value="assignment">Assignment Reminder</option>
                            <option value="exam">Exam Notice</option>
                            <option value="event">School Event</option>
                            <option value="performance">Academic Performance</option>
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
                                <div class="fw-bold">{{ Auth::user()->name ?? 'Teacher Name' }}</div>
                                <div>{{ Auth::user()->section_name ?? 'Section Name' }}</div>
                                <div>{{ Auth::user()->school->name ?? 'School Name' }}</div>
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
 <!-- content  -->
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
.stat-item {
    padding: 10px 0;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}
.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}
.badge {
    font-size: 0.75rem;
}
.message-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Compact table styles */
#messagesTable {
    font-size: 0.875rem;
}
#messagesTable .small {
    font-size: 0.8rem !important;
}
#messagesTable td, #messagesTable th {
    padding: 0.5rem 0.75rem !important;
    vertical-align: middle;
}
#messagesTable .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.sticky-header {
    background: white;
    padding: 1rem 0;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

/* Alert Modal Styles */
#alertModal .modal-sm {
    max-width: 400px;
}

#alertModal .modal-header {
    padding: 0.75rem 1rem;
}

#alertModal .modal-body {
    padding: 1rem;
    font-size: 0.95rem;
}

#alertModal .modal-footer {
    padding: 0.5rem 1rem;
}

/* Compact Student Info */
#studentInfoPanel .card-body {
    font-size: 0.875rem;
}

#studentInfoPanel .small {
    font-size: 0.8rem !important;
}
</style>

<script>
let currentPage = 1;
let totalPages = 1;
let allStudents = [];

const messageTemplates = {
    absent: "Good day! Your child [STUDENT_NAME] was absent from school today ([DATE]). Please ensure they attend tomorrow with a valid excuse letter if needed.",
    reminder: "Good day! This is a reminder about your child [STUDENT_NAME] from [SECTION]. Please check with your child for any assignments or announcements.",
    meeting: "Good day! You are invited to a parent meeting regarding your child [STUDENT_NAME] on [DATE]. Please contact the school for more details.",
    assignment: "Good day! Your child [STUDENT_NAME] has pending assignments. Please remind them to submit their work on time.",
    exam: "Good day! This is to inform you that [STUDENT_NAME] has an upcoming exam. Please ensure they are well-prepared.",
    event: "Good day! We would like to inform you about an upcoming school event involving [STUDENT_NAME]. More details will follow.",
    performance: "Good day! We would like to discuss [STUDENT_NAME]'s academic performance with you. Please contact us at your earliest convenience."
};

 const teacherInfo = {
    name: '{{ Auth::user()->name ?? "Teacher Name" }}',
    section: '{{ Auth::user()->section_name ?? "Section Name" }}',
    school: '{{ Auth::user()->school->name ?? "School Name" }}'
};

function loadStudents() {
    console.log('Loading students for filter and selector...');
    fetch('/teacher/get-students')
        .then(response => {
            console.log('Students API response:', response);
            return response.json();
        })
        .then(students => {
            console.log('Students loaded:', students);
            allStudents = students;
            
            // Populate both filter dropdown and student selector (if they exist on page load)
            const studentFilter = $('#studentFilter');
            studentFilter.empty().append('<option value="">All Students</option>');
            
            students.forEach(student => {
                const option = `<option value="${student.id}">${student.name}</option>`;
                studentFilter.append(option);
            });
        })
        .catch(error => {
            console.error('Error loading students:', error);
            showAlert('Error loading students: ' + error.message, 'danger');
        });
}

 function loadMessages() {
    const filters = {
        student_id: $('#studentFilter').val(),
        status: $('#statusFilter').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        page: currentPage
    };
    
    Object.keys(filters).forEach(key => {
        if (!filters[key]) delete filters[key];
    });
    
    const queryString = new URLSearchParams(filters).toString();
    
    fetch(`/teacher/outbound-messages?${queryString}`)
        .then(response => response.json())
        .then(data => {
            console.log('API response:', data); // Debug log
            if (data.success) {
                if (!data.messages || !Array.isArray(data.messages) || data.messages.length === 0) {
                    displayEmptyState('No messages found');
                } else {
                    displayMessages(data.messages);
                }
                updatePagination(data.pagination);
                updateStats(data.stats);
            } else {
                showAlert('Error loading messages: ' + data.message, 'danger');
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
    
    console.log('Displaying messages:', messages); // Debug log
    
    const rows = messages.map(message => {
        const statusBadge = getStatusBadge(message.status);
        const studentName = message.student ? message.student.name : 'Unknown';
        const teacherName = message.student && message.student.user ? message.student.user.name : 'Unknown Teacher';
        const messagePreview = message.message && message.message.length > 70 ? 
            message.message.substring(0, 70) + '...' : (message.message || 'No message');
        
        return `
            <tr>
                <td class="py-1 small">${formatDateTimeCompact(message.created_at)}</td>
                <td class="py-1 small">${studentName}</td>
                <td class="py-1 small">
                    <span class="message-preview" title="${escapeHtml(message.message)}">
                        ${escapeHtml(messagePreview)}
                    </span>
                </td>
                <td class="py-1 small">${teacherName}</td>
                <td class="py-1">${statusBadge}</td>
                <td class="py-1">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="viewMessage(${message.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${message.message_id ? `
                            <button class="btn btn-outline-info btn-sm" onclick="checkMessageStatus(${message.id})" title="Check Status">
                                <i class="fas fa-sync"></i>
                            </button>
                        ` : ''}
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
    
    // Hide all option divs first
    $('#allParentsDiv').hide();
    $('#studentSelectDiv').hide();
    $('#customNumberDiv').hide();
    $('#studentInfoPanel').hide();
    
    // Show the selected option
    if (selectedType === 'all_parents') {
        $('#allParentsDiv').show();
    } else if (selectedType === 'specific_student') {
        $('#studentSelectDiv').show();
        // Show student info if a student is already selected
        const selectedValue = $('#studentSelect').val();
        if (selectedValue) {
            $('#studentInfoPanel').show();
            onStudentSelect(); // Update info panel
        }
    } else if (selectedType === 'custom') {
        $('#customNumberDiv').show();
    }
}

 function applyTemplate() {
    const template = $('#messageTemplate').val();
    if (template && messageTemplates[template]) {
        const selectedType = $('input[name="recipientType"]:checked').val();
        let message = messageTemplates[template];
        
        // Replace date placeholder
        const today = new Date().toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        message = message.replace(/\[DATE\]/g, today);
        
        // Replace student-specific placeholders
        if (selectedType === 'specific_student') {
            const selectedStudent = $('#studentSelect option:selected').text();
            if (selectedStudent && selectedStudent !== 'Choose a student...') {
                message = message.replace(/\[STUDENT_NAME\]/g, selectedStudent);
            } else {
                message = message.replace(/\[STUDENT_NAME\]/g, 'your child');
            }
        } else {
            // For all parents or custom, use generic term
            message = message.replace(/\[STUDENT_NAME\]/g, 'your child');
        }
        
        message = message.replace(/\[SECTION\]/g, teacherInfo.section);
        
        $('#messageText').val(message).trigger('input');
        
        // Auto-add signature if enabled
        if ($('#autoSignature').is(':checked')) {
            addSignature();
        }
    }
}

// Handle student selection change
function onStudentSelect() {
    console.log('Student selection changed');
    const studentSelect = $('#studentSelect');
    const selectedValue = studentSelect.val();
    const selectedOption = studentSelect.find('option:selected');
    
    if (selectedValue && selectedValue !== '') {
        // Show info for specific student
        const studentName = selectedOption.text();
        const parentContact = selectedOption.data('parent') || 'No parent contact';
        const parentName = selectedOption.data('parent-name') || 'Unknown';
        
        $('#studentInfoName').text(studentName);
        $('#studentInfoSection').text(teacherInfo.section);
        $('#studentInfoContact').text(parentContact);
        $('#studentInfoParent').text(parentName);
        $('#studentInfoPanel').show();
        
        // Auto-fill student name in message if a template is selected
        const currentTemplate = $('#messageTemplate').val();
        if (currentTemplate) {
            applyTemplate(); // Re-apply template with student name
        }
    } else {
        // Hide panel when no student selected
        $('#studentInfoPanel').hide();
    }
}

// Add signature to message
function addSignature() {
    const messageArea = $('#messageText');
    let currentMessage = messageArea.val().trim();
    
    // Remove existing signature if present
    const signatureStart = currentMessage.lastIndexOf('\n\nFrom:');
    if (signatureStart !== -1) {
        currentMessage = currentMessage.substring(0, signatureStart);
    }
    
    // Generate new signature
    const signature = `\n\nFrom: ${teacherInfo.name}\n${teacherInfo.section}\n${teacherInfo.school}`;
    
    messageArea.val(currentMessage + signature).trigger('input');
}

// Send SMS
function sendSMS() {
    const selectedType = $('input[name="recipientType"]:checked').val();
    let phoneNumber = '';
    let studentId = null;
    
    if (selectedType === 'all_parents') {
        phoneNumber = 'all_parents';
    } else if (selectedType === 'specific_student') {
        studentId = $('#studentSelect').val();
        
        if (!studentId) {
            showAlert('Please select a student', 'warning');
            return;
        }
        
        // Use parent contact from selected option
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
        send_to_all: selectedType === 'all_parents'
    };
    
    if (studentId) {
        data.student_id = studentId;
    }
    
    const sendBtn = $('.modal-footer .btn-primary');
    sendBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sending...');
    
    fetch('/teacher/send-sms', {
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
            // Reset to default state
            $('#allParentsRadio').prop('checked', true);
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

// Check message status
function checkMessageStatus(messageId) {
    fetch(`/teacher/message-status/${messageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(`Message status: ${data.delivery_status}`, 'info');
                loadMessages(); // Refresh to show updated status
            } else {
                showAlert('Error checking status: ' + data.message, 'warning');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error checking message status', 'danger');
        });
}

// Test SMS Gateway
function testSMSGateway() {
    fetch('/teacher/test-sms-gateway')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert('SMS working!', 'success');
            } else {
                showAlert('SMS test failed contact Admin : ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error testing SMS gateway contact Admin', 'danger');
        });
}

 function viewMessage(messageId) {
    fetch(`/teacher/outbound-messages`)
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
    const teacherName = message.student && message.student.user ? message.student.user.name : 'Unknown Teacher';
    const content = `
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Date & Time:</strong></div>
            <div class="col-sm-9">${formatDateTime(message.created_at)}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Student:</strong></div>
            <div class="col-sm-9">${message.student ? message.student.name : 'Unknown'}</div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-3"><strong>Teacher:</strong></div>
            <div class="col-sm-9">${teacherName}</div>
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

 function addSignature() {
    const messageArea = $('#messageText');
    let currentMessage = messageArea.val().trim();
    
     const signatureStart = currentMessage.lastIndexOf('\n\nFrom:');
    if (signatureStart !== -1) {
        currentMessage = currentMessage.substring(0, signatureStart);
    }
    
     const signature = `\n\nFrom: ${teacherInfo.name}\n${teacherInfo.section}\n${teacherInfo.school}`;
    
    messageArea.val(currentMessage + signature).trigger('input');
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
    console.log('Document ready - initializing SMS message interface...');
    console.log('jQuery version:', $.fn.jquery);
   loadStudents(); 
    loadMessages();
    
     $('#messageText').on('input', function() {
        const text = $(this).val();
        const charCount = text.length;
        const smsCount = Math.ceil(charCount / 160) || 1;
        
        $('#charCount').text(charCount);
        $('#smsCount').text(smsCount);
    });
    
    console.log('Event handlers attached successfully');
    
     $('#autoSignature').on('change', function() {
        if (this.checked) {
            addSignature();
        } else {
             const messageArea = $('#messageText');
            let currentMessage = messageArea.val().trim();
            const signatureStart = currentMessage.lastIndexOf('\n\nFrom:');
            if (signatureStart !== -1) {
                messageArea.val(currentMessage.substring(0, signatureStart)).trigger('input');
            }
        }
    });
    
     $('#studentSelect').on('change', function() {
        onStudentSelect();
    });
    
     $('#customNumber').on('input', function() {
        formatCustomNumber();
    });
    
     $('#recipientType').on('change', function() {
        toggleRecipientOptions();
    });
    
     toggleRecipientOptions();
    
     setInterval(loadMessages, 30000);
});
</script>

@endsection