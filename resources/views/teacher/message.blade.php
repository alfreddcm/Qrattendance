@extends('teacher/sidebar')
@section('title', 'Messages')
@section('content')

<div class="sticky-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>
                <span class="me-2">💬</span>
                Messages
            </h2>
            <p class="subtitle">View and manage your messages</p>
        </div>
       
    </div>
</div>

<div class="container mt-4">
 
    <div class="table-responsive">    <div class="page-actions">
            <button class="btn btn-primary" disabled>
                <i class="fas fa-plus me-1"></i>New Message
            </button>
        </div>
    <br>
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>No.</th>
                    <th>Time & Date Sent</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Archive</th>
                </tr>
            </thead>
            <tbody>
                <!-- Placeholder rows -->
                <tr>
                    <td>1</td>
                    <td>2024-06-10 09:30 AM</td>
                    <td>Lorem ipsum dolor sit amet...</td>
                    <td><span class="badge badge-success">Delivered</span></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" disabled>Archive</button>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>2024-06-09 02:15 PM</td>
                    <td>Another message example...</td>
                    <td><span class="badge badge-danger">Failed</span></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" disabled>Archive</button>
                    </td>
                </tr>
                <!-- Add more rows dynamically -->
            </tbody>
        </table>
    </div>
</div>
@endsection