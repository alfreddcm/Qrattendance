@extends('teacher/sidebar')
@section('title', 'Edit Student')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.student-current-picture {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.student-photo-placeholder {
    width: 96px;
    height: 96px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #6c757d;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.student-photo-placeholder:hover {
    border-color: #007bff;
    background: #f8f9ff;
}

.placeholder-icon {
    font-size: 1.8rem;
    margin-bottom: 4px;
    opacity: 0.7;
}

.placeholder-text {
    font-size: 0.7rem;
    font-weight: 500;
    text-align: center;
    line-height: 1.0;
}

.placeholder-text small {
    opacity: 0.8;
}

.placeholder-plus-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    opacity: 0.8;
}

.upload-buttons {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.3rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.75rem;
    min-width: 36px;
    height: 32px;
}

.preview-container {
    position: relative;
    display: inline-block;
}

.preview-image {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #28a745;
    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
}

.preview-check-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.camera-video {
    width: 100%;
    max-width: 400px;
    border-radius: 15px;
    border: 3px solid #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
}

.camera-preview-image {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 15px;
    border: 3px solid #28a745;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
}

.sticky-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1rem;
}
</style>

@section('content')

<div class="sticky-header ">
    <div class=" ">
        <div>
            <h5 class="mb-1"><i class="fas fa-user-graduate me-2"></i>Edit Student</h5>
            <p class="small text-muted mb-0">Update student information and records</p>
        </div>

    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Student Information</h6>
    </div>
    <div class="card-body p-3">

        <form method="POST" action="{{ route('teacher.students.update', $student->id) }}" enctype="multipart/form-data" id="editStudentForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            <input type="hidden" id="original_id_no" value="{{ $student->id_no }}">
            <input type="hidden" id="original_name" value="{{ $student->name }}">
            <input type="hidden" id="student_id" value="{{ $student->id }}">
            <input type="hidden" id="has_qr_code" value="{{ $student->qr_code ? '1' : '0' }}">

            <div class="row g-2">
                <div class="col-md-3">
                    <label for="picture" class="form-label small fw-bold">Photo</label>
                    <div class="d-flex flex-column align-items-center">
                         <div id="current-photo-container">
                            @if($student->picture)
                                <div class="mb-1">
                                    <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Current Picture" class="student-current-picture">
                                </div>
                                <small class="text-muted text-center">Current</small>
                            @else
                                <div class="mb-1">
                                    <div class="student-photo-placeholder" onclick="document.getElementById('picture').click();">
                                        <i class="fas fa-user-circle placeholder-icon"></i>
                                        <span class="placeholder-text">No Photo</span>
                                        <div class="placeholder-plus-badge">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted text-center">No photo</small>
                            @endif
                        </div>

                         <div id="image-preview" class="mt-1" style="display: none;">
                            <div class="preview-container">
                                <img id="preview-img" src="" alt="Preview" class="preview-image" style="display: block;">
                                <div class="preview-check-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <small class="text-success text-center d-block mt-1">Ready</small>
                        </div>

                        <div class="upload-buttons mt-1">
                            <button type="button" class="btn btn-primary btn-sm upload-btn" onclick="openCameraModal()" title="Take Photo">
                                <i class="fa fa-camera"></i>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm upload-btn" onclick="document.getElementById('picture').click()" title="Upload File">
                                <i class="fa fa-upload"></i>
                            </button>
                        </div>

                        <input type="file" class="form-control d-none" id="picture" name="picture" accept="image/*">
                        <input type="hidden" id="captured_image" name="captured_image">

                        <small class="form-text text-muted text-center mt-1">Max: 2MB</small>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="alert alert-warning d-flex align-items-center py-2 mb-2" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small><strong>Reminder:</strong> Changing student info may require updating their QR code!</small>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="id_no" class="form-label small">LRN</label>
                            <input type="text" class="form-control form-control-sm" id="id_no" name="id_no" value="{{ $student->id_no }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label small">Name</label>
                            <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ $student->name }}" required>
                        </div>

                        <div class="col-md-4">
                            <label for="gender" class="form-label small">Gender</label>
                            <select class="form-select form-select-sm" id="gender" name="gender" required>
                                <option value="">Select</option>
                                <option value="M" {{ $student->gender == 'M' ? 'selected' : '' }}>Male</option>
                                <option value="F" {{ $student->gender == 'F' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="age" class="form-label small">Age</label>
                            <input type="number" class="form-control form-control-sm" id="age" name="age" value="{{ $student->age }}" required>
                        </div>

                        <div class="col-md-4">
                            <label for="cp_no" class="form-label small">CP No</label>
                            <input type="tel" class="form-control form-control-sm" id="cp_no" name="cp_no" value="{{ $student->cp_no }}" required pattern="[0-9]{11}" maxlength="11" title="Please enter 11 digits">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="section_dropdown" class="form-label small">Grade Level & Section</label>
                    <select class="form-select form-select-sm" id="section_dropdown" name="section_id" required>
                        <option value="">Select Grade Level & Section</option>
                        @php
                            $teacherSections = App\Models\Section::where('teacher_id', auth()->id())
                                ->orderBy('gradelevel')
                                ->orderBy('name')
                                ->get();
                            $studentHasValidSection = $student->section_id && $teacherSections->contains('id', $student->section_id);
                        @endphp

                        @if($student->section && !$studentHasValidSection)

                            <option value="{{ $student->section_id }}" selected class="text-warning">
                                Grade {{ $student->section->gradelevel }} - {{ $student->section->name }} (Current - Not in your sections)
                            </option>
                            <option disabled>──────────────</option>
                        @endif

                        @foreach($teacherSections as $section)
                            <option value="{{ $section->id }}"
                                    {{ $student->section_id == $section->id ? 'selected' : '' }}>
                                Grade {{ $section->gradelevel }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        @if(!$studentHasValidSection && $student->section)
                            <span class="text-warning">Current section not in your managed sections.</span>
                        @endif
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="school_year_id" class="form-label small">School Year</label>
                    <select class="form-select form-select-sm" id="school_year_id" name="school_year_id" required>
                        @foreach(\App\Models\SchoolYear::all() as $schoolYear)
                            <option value="{{ $schoolYear->id }}" {{ $student->school_year_id == $schoolYear->id ? 'selected' : '' }}>
                                @if($schoolYear->school_year_start && $schoolYear->school_year_end)
                                    {{ $schoolYear->school_year_start }}–{{ $schoolYear->school_year_end }}
                                @else
                                    {{ $schoolYear->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12">
                    <label for="address" class="form-label small">Address</label>
                    <input type="text" class="form-control form-control-sm" id="address" name="address" value="{{ $student->address }}" required>
                </div>


                <div class="col-md-12"><hr class="my-2"><h6 class="small text-muted">Contact Person Information</h6></div>

                <div class="col-md-4">
                    <label for="contact_person_name" class="form-label small">Contact Person Name</label>
                    <input type="text" class="form-control form-control-sm" id="contact_person_name" name="contact_person_name" value="{{ $student->contact_person_name }}">
                </div>

                <div class="col-md-4">
                    <label for="contact_person_relationship" class="form-label small">Relationship</label>
                    <select class="form-select form-select-sm" id="contact_person_relationship" name="contact_person_relationship">
                        <option value="">Select Relationship</option>
                        <option value="Parent" {{ $student->contact_person_relationship == 'Parent' ? 'selected' : '' }}>Parent</option>
                        <option value="Guardian" {{ $student->contact_person_relationship == 'Guardian' ? 'selected' : '' }}>Guardian</option>
                        <option value="Sibling" {{ $student->contact_person_relationship == 'Sibling' ? 'selected' : '' }}>Sibling</option>
                        <option value="Spouse" {{ $student->contact_person_relationship == 'Spouse' ? 'selected' : '' }}>Spouse</option>
                        <option value="Relative" {{ $student->contact_person_relationship == 'Relative' ? 'selected' : '' }}>Relative</option>
                        <option value="Other" {{ $student->contact_person_relationship == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="contact_person_contact" class="form-label small">Contact Number</label>
                    <input type="tel" class="form-control form-control-sm" id="contact_person_contact" name="contact_person_contact" value="{{ $student->contact_person_contact }}" pattern="[0-9]{11}" maxlength="11" title="Please enter 11 digits">
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-between">
                <a href="{{ route('teacher.students') }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Update Student</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <center><h5 class="modal-title" id="cameraModalLabel">Take Photo</h5></center>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="camera-container">
                    <center><video id="camera-video" autoplay playsinline class="camera-video"></video></center>
                    <canvas id="camera-canvas" style="display: none;"></canvas>
                </div>
                <div id="camera-controls" class="mt-3">
                    <button type="button" class="btn btn-primary" onclick="capturePhoto()">
                        <i class="fa fa-camera"></i> Capture Photo
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="retakePhoto()" style="display: none;" id="retake-btn">
                        <i class="fa fa-redo"></i> Retake
                    </button>
                </div>
                <div id="camera-preview" class="mt-3" style="display: none;">
                    <img id="captured-photo" src="" alt="Captured Photo" class="camera-preview-image">
                </div>
                <div id="camera-error" class="mt-3 alert alert-danger" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i> Camera not available. Please upload a file instead.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="usePhoto()" id="use-photo-btn" style="display: none;">
                    <i class="fa fa-check"></i> Use Photo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Camera functionality
let videoStream = null;
let cameraModal = null;

function openCameraModal() {
    cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    cameraModal.show();

    // Reset camera state
    document.getElementById('camera-preview').style.display = 'none';
    document.getElementById('retake-btn').style.display = 'none';
    document.getElementById('use-photo-btn').style.display = 'none';
    document.getElementById('camera-error').style.display = 'none';
    document.getElementById('camera-video').style.display = 'block';

    startCamera();
}

function startCamera() {
    const video = document.getElementById('camera-video');

    // Check if camera is supported
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError();
        return;
    }

    navigator.mediaDevices.getUserMedia({
        video: {
            width: { ideal: 640 },
            height: { ideal: 480 },
            facingMode: 'user' // Front camera by default
        }
    })
    .then(function(stream) {
        videoStream = stream;
        video.srcObject = stream;
    })
    .catch(function(err) {
        console.error('Error accessing camera:', err);
        showCameraError();
    });
}

function showCameraError() {
    document.getElementById('camera-video').style.display = 'none';
    document.getElementById('camera-error').style.display = 'block';
}

function capturePhoto() {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('camera-canvas');
    const context = canvas.getContext('2d');

    // Set canvas size to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame to canvas
    context.drawImage(video, 0, 0);

    // Convert canvas to data URL
    const dataURL = canvas.toDataURL('image/jpeg', 0.8);

    // Show preview
    document.getElementById('captured-photo').src = dataURL;
    document.getElementById('camera-preview').style.display = 'block';
    document.getElementById('camera-video').style.display = 'none';
    document.getElementById('retake-btn').style.display = 'inline-block';
    document.getElementById('use-photo-btn').style.display = 'inline-block';
}

function retakePhoto() {
    document.getElementById('camera-preview').style.display = 'none';
    document.getElementById('camera-video').style.display = 'block';
    document.getElementById('retake-btn').style.display = 'none';
    document.getElementById('use-photo-btn').style.display = 'none';
}

function usePhoto() {
    const dataURL = document.getElementById('captured-photo').src;

    // Set the captured image data
    document.getElementById('captured_image').value = dataURL;

    // Show preview in main form
    showImagePreview(dataURL);

    // Close modal
    stopCamera();
    cameraModal.hide();
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
}

function showImagePreview(src) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const currentPhotoContainer = document.getElementById('current-photo-container');

    // Hide the current/no photo container
    currentPhotoContainer.style.display = 'none';

    // Ensure the image loads properly
    previewImg.onload = function() {
        preview.style.display = 'block';
    };

    previewImg.onerror = function() {
        console.error('Error loading image preview');
        preview.style.display = 'none';
        // Show the current photo container again if preview fails
        currentPhotoContainer.style.display = 'block';
    };

    // Set the source and make sure it's visible
    previewImg.src = src;
    previewImg.style.display = 'block';
}

// Close camera when modal is hidden
document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
    stopCamera();
});

// File upload preview
document.getElementById('picture').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];

        // Check if it's an image file
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            this.value = ''; // Clear the input
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
            // Clear captured image if file is uploaded
            document.getElementById('captured_image').value = '';
        }
        reader.readAsDataURL(file);
    }
});

// ============================================
// QR CODE REGENERATION DETECTION SYSTEM
// ============================================

/**
 * QR CODE DATA STRUCTURE:
 * 
 * 1. QR Code Data Format: {id_no}_{10-char-random}
 *    Example: "2123123_AB12CD34EF"
 * 
 * 2. QR Code File Name: qr_codes/{id_no}_{sanitized_name}.svg
 *    Example: "qr_codes/2123123_John_Doe.svg"
 * 
 * 3. Database Fields:
 *    - qr_code: File path (e.g., "qr_codes/2123123_John_Doe.svg")
 *    - stud_code: QR data (e.g., "2123123_AB12CD34EF")
 * 
 * CRITICAL VARIABLES that affect QR code:
 *    - id_no: Used in both QR data AND filename
 *    - name: Used in filename (sanitized)
 * 
 * When these change:
 *    1. Old QR file becomes invalid (wrong filename)
 *    2. QR data becomes invalid (wrong id_no)
 *    3. Must delete old QR and regenerate new one
 */

const editForm = document.getElementById('editStudentForm');
const originalIdNo = document.getElementById('original_id_no').value;
const originalName = document.getElementById('original_name').value;
const studentId = document.getElementById('student_id').value;
const hasQrCode = document.getElementById('has_qr_code').value === '1';

editForm.addEventListener('submit', function(e) {
    const currentIdNo = document.getElementById('id_no').value.trim();
    const currentName = document.getElementById('name').value.trim();
    
    // Check if QR-critical fields have changed
    const idNoChanged = currentIdNo !== originalIdNo;
    const nameChanged = currentName !== originalName;
    const qrCriticalFieldsChanged = idNoChanged || nameChanged;
    
    // If student has QR code AND critical fields changed
    if (hasQrCode && qrCriticalFieldsChanged) {
        e.preventDefault();
        
        let changedFields = [];
        if (idNoChanged) changedFields.push(`LRN: "${originalIdNo}" → "${currentIdNo}"`);
        if (nameChanged) changedFields.push(`Name: "${originalName}" → "${currentName}"`);
        
        Swal.fire({
            title: 'QR Code Will Be Deleted',
            html: `
                <div style="text-align: left; padding: 10px;">
                    <p><strong>You changed critical student information:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        ${changedFields.map(field => `<li>${field}</li>`).join('')}
                    </ul>
                    <p style="margin-top: 15px; color: #dc3545;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        The existing QR code will be <strong>deleted</strong> because:
                    </p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #6c757d; font-size: 0.9em;">
                        ${idNoChanged ? '<li>LRN is used in QR code data</li>' : ''}
                        ${nameChanged || idNoChanged ? '<li>File name must match student info</li>' : ''}
                    </ul>
                    <p style="margin-top: 15px; color: #28a745;">
                        <i class="fas fa-sync-alt"></i> 
                        You can regenerate a new QR code after updating.
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Update & Delete QR',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'swal-wide'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show the regeneration prompt
                Swal.fire({
                    title: 'Regenerate QR Code Now?',
                    text: 'Would you like to generate a new QR code immediately after updating?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Generate QR',
                    cancelButtonText: 'No, I\'ll do it later'
                }).then((regenerateResult) => {
                    // Store the user's choice
                    const shouldRegenerate = regenerateResult.isConfirmed;
                    
                    // Add hidden field to track regeneration preference
                    const regenerateInput = document.createElement('input');
                    regenerateInput.type = 'hidden';
                    regenerateInput.name = 'regenerate_qr';
                    regenerateInput.value = shouldRegenerate ? '1' : '0';
                    editForm.appendChild(regenerateInput);
                    
                    // Submit the form
                    editForm.submit();
                });
            }
        });
    }
});
</script>

<style>
.swal-wide {
    width: 600px !important;
}
</style>

@endsection
