@extends('teacher/sidebar')
@section('title', 'Edit Student')

<style>
.student-current-picture {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 15px;
    border: 3px solid #e9ecef;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.student-photo-placeholder {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #6c757d;
    border-radius: 15px;
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
    font-size: 2.5rem;
    margin-bottom: 8px;
    opacity: 0.7;
}

.placeholder-text {
    font-size: 0.75rem;
    font-weight: 500;
    text-align: center;
    line-height: 1.2;
}

.placeholder-text small {
    opacity: 0.8;
}

.placeholder-plus-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    opacity: 0.8;
}

.upload-buttons {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.upload-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 500;
}

.preview-container {
    position: relative;
    display: inline-block;
}

.preview-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 15px;
    border: 3px solid #28a745;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
}

.preview-check-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
</style>

@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            
            <h2>
                <span class="me-2">✏️</span>
                Edit Student
            </h2>
            <p class="subtitle">Update student information and records</p>
        </div>
       
    </div>
</div>

<div class="card shadow-sm">
    
    <div class="card-header bg-primary text-white">
        
        <h5 class="mb-0">
            <i class="fas fa-edit me-2"></i>
            Student Information
        </h5>
    </div>
    <div class="card-body">
        
        <form method="POST" action="{{ route('teacher.students.update', $student->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            
            <div class="row g-3">
                <div class="col-md-12">
                     <div class="page-actions">
            <a href="{{ route('teacher.students') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Students
            </a>
        </div>
                    <label for="picture" class="form-label">Student Picture</label>
                    @if($student->picture)
                        <div class="mb-2">
                            <img src="{{ asset('storage/student_pictures/' . $student->picture) }}" alt="Current Picture" class="student-current-picture">
                            <p class="text-muted mt-2 small">Current Picture</p>
                        </div>
                    @else
                        <div class="mb-2">
                            <div class="student-photo-placeholder" onclick="document.getElementById('picture').click();">
                                <i class="fas fa-user-circle placeholder-icon"></i>
                                <span class="placeholder-text">No Photo<br><small>Click to add</small></span>
                                <div class="placeholder-plus-badge">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                            <p class="text-muted mt-2 small">No picture uploaded</p>
                        </div>
                    @endif
                    <div class="upload-buttons">
                        <button type="button" class="btn btn-outline-primary btn-sm upload-btn" onclick="openCameraModal()">
                            <i class="fa fa-camera"></i> <span>Take Photo</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm upload-btn" onclick="document.getElementById('picture').click()">
                            <i class="fa fa-upload"></i> <span>Upload File</span>
                        </button>
                    </div>
                    <input type="file" class="form-control d-none" id="picture" name="picture" accept="image/*">
                    <input type="hidden" id="captured_image" name="captured_image">
                    <div id="image-preview" class="mt-2" style="display: none;">
                        <div class="preview-container">
                            <img id="preview-img" src="" alt="Preview" class="preview-image">
                            <div class="preview-check-badge">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <p class="text-success mt-2 small mb-0"><i class="fas fa-check-circle me-1"></i>New photo ready</p>
                    </div>
                    <small class="form-text text-muted">Take a photo or upload JPG, PNG, or GIF. Max size: 2MB. Leave empty to keep current picture.</small>
                </div>
                <div class="col-md-12">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Reminder:</strong> Changing student information may require updating their QR code!
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="id_no" class="form-label">ID No</label>
                    <input type="text" class="form-control" id="id_no" name="id_no" value="{{ $student->id_no }}" required>
                </div>
                
                <div class="col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $student->name }}" required>
                </div>
                
                <div class="col-md-6">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select</option>
                        <option value="M" {{ $student->gender == 'M' ? 'selected' : '' }}>Male</option>
                        <option value="F" {{ $student->gender == 'F' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="age" class="form-label">Age</label>
                    <input type="number" class="form-control" id="age" name="age" value="{{ $student->age }}" required>
                </div>
                
                <div class="col-md-12">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="{{ $student->address }}" required>
                </div>
                
                <div class="col-md-6">
                    <label for="cp_no" class="form-label">CP No</label>
                    <input type="text" class="form-control" id="cp_no" name="cp_no" value="{{ $student->cp_no }}" required>
                </div>
                
                <div class="col-md-6">
                    <label for="semester_id" class="form-label">Semester</label>
                    <select class="form-select" id="semester_id" name="semester_id" required>
                        @foreach(\App\Models\Semester::all() as $semester)
                            <option value="{{ $semester->id }}" {{ $student->semester_id == $semester->id ? 'selected' : '' }}>
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Contact Person Information -->
                <div class="col-md-12">
                    <hr>
                    <h6>Contact Person Information</h6>
                </div>
                
                <div class="col-md-6">
                    <label for="contact_person_name" class="form-label">Contact Person Name</label>
                    <input type="text" class="form-control" id="contact_person_name" name="contact_person_name" value="{{ $student->contact_person_name }}">
                </div>
                
                <div class="col-md-6">
                    <label for="contact_person_relationship" class="form-label">Relationship</label>
                    <select class="form-select" id="contact_person_relationship" name="contact_person_relationship">
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
                    <label for="contact_person_contact" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact_person_contact" name="contact_person_contact" value="{{ $student->contact_person_contact }}">
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update Student</button>
                <a href="{{ route('teacher.students') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Camera Modal -->
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
    previewImg.src = src;
    preview.style.display = 'block';
}

// Close camera when modal is hidden
document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
    stopCamera();
});

// File upload preview
document.getElementById('picture').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
            // Clear captured image if file is uploaded
            document.getElementById('captured_image').value = '';
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

@endsection
