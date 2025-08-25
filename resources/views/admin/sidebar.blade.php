<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- FontAwesome from CDN with fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/all.min.js') }}"></script>
    
    <style>
        body {
            background: #f8fffe;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .content {
            margin-left: 250px;
            padding: 0 30px 30px 30px;
            transition: margin-left 0.3s;
            min-height: 100vh;
        }

        .content.expanded {
            margin-left: 60px;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: #2c3e50;
            border-right: 3px solid #00a86b;
            color: #ecf0f1;
            padding: 10px 0 30px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: width 0.3s;
            z-index: 997;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sidebar.closed {
            width: 60px;
        }

        .hamburger-menu {
            width: 100%;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .hamburger-menu:hover {
            background: rgba(0, 168, 107, 0.1);
        }

        .hamburger-icon {
            width: 24px;
            height: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            cursor: pointer;
            transition: all 0.3s;
        }

        .hamburger-icon span {
            display: block;
            height: 3px;
            width: 100%;
            background: #ecf0f1;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .hamburger-label {
            margin-left: 12px;
            font-size: 0.9em;
            font-weight: 500;
            color: #ecf0f1;
            transition: opacity 0.3s;
        }

        .sidebar.closed .hamburger-label {
            opacity: 0;
            display: none;
        }

        .sidebar.closed .hamburger-menu {
            justify-content: center;
            padding: 10px;
        }

        .sidebar .logo {
            width: 60px;
            height: 60px;
            background: #00a86b;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: #fff;
            font-weight: bold;
            transition: width 0.3s, height 0.3s, font-size 0.3s;
        }

        .sidebar.closed .logo {
            width: 30px;
            height: 30px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }

        .sidebar h2,
        .sidebar .user-info {
            text-align: center;
            transition: opacity 0.3s, max-height 0.3s;
            white-space: wrap;
            overflow: hidden;
        }

        .sidebar.closed h2,
        .sidebar.closed .user-info {
            opacity: 0;
            max-height: 0;
            padding: 0;
            margin: 0;
        }

        .sidebar h2 {
            margin: 0 0 20px 0;
            font-size: 1.1em;
            font-weight: 600;
            color: #ecf0f1;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            width: 100%;
            margin: 0;
        }

        .sidebar ul li {
            width: 100%;
            margin-bottom: 2px;
        }

        .sidebar ul li a,
        .sidebar ul li form button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(236, 240, 241, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 8px;
            margin: 2px 15px;
            background: none;
            border: none;
            width: calc(100% - 30px);
            text-align: left;
            font-size: 0.95em;
            line-height: 1.4;
            min-height: 44px;
            box-sizing: border-box;
            cursor: pointer;
        }

        .sidebar ul li a.active,
        .sidebar ul li a:hover,
        .sidebar ul li form button:hover {
            background: #00a86b;
            color: #fff;
            transform: translateX(3px);
        }

        .sidebar ul li a .icon,
        .sidebar ul li form button .icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
            flex-shrink: 0;
            text-align: center;
        }

        .sidebar ul li a span:not(.icon),
        .sidebar ul li form button span:not(.icon) {
            flex: 1;
            display: flex;
            align-items: center;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.closed ul li a,
        .sidebar.closed ul li form button {
            padding: 12px 15px;
            justify-content: center;
            gap: 0;
            min-height: 44px;
            margin: 2px 10px;
            width: calc(100% - 20px);
        }

        .sidebar.closed ul li a span:not(.icon),
        .sidebar.closed ul li form button span:not(.icon) {
            display: none;
        }

        .sidebar.closed .user-info {
            display: none;
        }

        .sidebar .user-info {
            font-size: 0.85em;
            color: #95a5a6;
            margin-bottom: 10px;
            transition: opacity 0.3s;
            font-weight: 400;
        }

        .hamburger {
            display: none; /* Hide the old hamburger button */
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 995;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            margin: 0 -30px 30px -30px;
            padding: 20px 30px;
            transition: all 0.3s;
        }

        .sticky-header h2 {
            margin: 0;
            font-size: 1.6em;
            font-weight: 600;
            color: #1f2937;
        }

        .sticky-header .subtitle {
            margin: 0;
            color: #6b7280;
            font-size: 0.9em;
        }

        .page-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .sticky-header h2 {
                font-size: 1.5em;
            }
            .sticky-header .subtitle {
                font-size: 0.9em;
                margin-bottom: 5px;
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
                padding: 0 20px 20px 20px;
            }
            .content.expanded {
                margin-left: 0;
            }
        }
            }
            .sticky-header {
                padding: 15px 20px 15px 100px;
                margin: 0 -20px 15px -20px;
                height: 72px;
            }
        }

        .sidebar.closed ~ .content .sticky-header {
            padding-left: 80px;
        }

        /* Custom Card Styles */
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: #00a86b;
            color: #fff;
            border-radius: 12px 12px 0 0 !important;
            border-bottom: none;
            font-weight: 600;
            padding: 16px 20px;
        }

        .card-body {
            color: #374151;
            padding: 20px;
        }

        .table {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
        }

        .table thead th {
            background: #f9fafb;
            color: #374151;
            border: none;
            font-weight: 600;
            padding: 16px 12px;
            font-size: 0.875em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.2s;
            border-bottom: 1px solid #f3f4f6;
        }

        .table tbody tr:hover {
            background: #f8fffe;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody td {
            border: none;
            padding: 16px 12px;
            vertical-align: middle;
        }

        /* Custom Button Styles */
        .btn-primary {
            background: #00a86b;
            border: 1px solid #00a86b;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.875em;
        }

        .btn-primary:hover {
            background: #008a5a;
            border-color: #008a5a;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #00a86b;
            border: 1px solid #00a86b;
        }

        .btn-success:hover {
            background: #008a5a;
            border-color: #008a5a;
        }

        .btn-secondary {
            background: #6b7280;
            border: 1px solid #6b7280;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #4b5563;
            border-color: #4b5563;
        }

        .btn-outline-primary {
            border-color: #00a86b;
            color: #00a86b;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #00a86b;
            border-color: #00a86b;
            color: #fff;
        }

        .btn-outline-danger {
            border-color: #ef4444;
            color: #ef4444;
            background: transparent;
        }

        .btn-outline-danger:hover {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8em;
            border-radius: 6px;
        }

        /* Custom Badge Styles */
        .badge {
            border-radius: 6px;
            padding: 4px 8px;
            font-weight: 500;
            font-size: 0.75em;
        }

        .badge.bg-primary {
            background: #3b82f6 !important;
        }

        .badge.bg-success {
            background: #00a86b !important;
        }

        .badge.bg-info {
            background: #06b6d4 !important;
        }

        /* Custom Alert Styles */
        .alert {
            border-radius: 8px;
            border: 1px solid;
            padding: 16px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }

        /* Custom Modal Styles */
        .modal-content {
            background: #fff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            background: #00a86b;
            color: #fff;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
            padding: 20px 24px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            color: #374151;
            padding: 24px;
        }

        .modal-footer {
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 0 0 16px 16px;
            padding: 16px 24px;
        }

        /* Custom Form Styles */
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            transition: all 0.2s ease;
            padding: 12px 16px;
        }

        .form-control:focus {
            border-color: #00a86b;
            box-shadow: 0 0 0 3px rgba(0, 168, 107, 0.1);
            outline: none;
        }

        .form-label {
            color: #374151;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.875em;
        }

        /* Custom Select Styles */
        .form-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            padding: 12px 16px;
        }

        .form-select:focus {
            border-color: #00a86b;
            box-shadow: 0 0 0 3px rgba(0, 168, 107, 0.1);
        }

        /* Custom Pagination Styles */
        .pagination .page-link {
            color: #00a86b;
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 6px;
        }

        .pagination .page-link:hover {
            color: #fff;
            background: #00a86b;
            border-color: #00a86b;
        }

        .pagination .page-item.active .page-link {
            background: #00a86b;
            border-color: #00a86b;
            color: #fff;
        }

        /* Custom Border Colors */
        .border-primary {
            border-color: #00a86b !important;
        }

        .border-success {
            border-color: #00a86b !important;
        }

        .border-info {
            border-color: #06b6d4 !important;
        }

        /* Text Colors */
        .text-primary {
            color: #00a86b !important;
        }

        .text-success {
            color: #00a86b !important;
        }

        .text-info {
            color: #06b6d4 !important;
        }

        .text-muted {
            color: #6b7280 !important;
        }

        /* Container Styles */
        .container-fluid {
            color: #374151;
        }

        /* Stats Card Special Styling */
        .stats-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .stats-card:hover {
            border-color: #00a86b;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 168, 107, 0.15);
        }

        .stats-card .display-4 {
            font-weight: 700;
        }

        /* Table responsive wrapper */
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* File input styling */
        .form-control[type="file"] {
            padding: 8px 12px;
        }

        /* Logo preview styling */
        .logo-preview {
            width: 80px;
            height: 80px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: #f9fafb;
        }

        .placeholder-logo {
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 15px;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }

        /* Global Admin Card Styles - Compact and Consistent Design */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            padding: 0.75rem 1rem;
            font-weight: 600;
        }

        .card-header h5, .card-header h6 {
            margin-bottom: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1rem;
            border-radius: 0 0 12px 12px;
        }

        .card-footer {
            background: #f8f9fa;
            border: none;
            border-radius: 0 0 12px 12px;
            padding: 0.75rem 1rem;
        }

        /* Compact Statistics Cards */
        .stats-card {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .stats-card.primary { border-left-color: #007bff; }
        .stats-card.success { border-left-color: #28a745; }
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.info { border-left-color: #17a2b8; }
        .stats-card.danger { border-left-color: #dc3545; }

        .stats-card .card-body {
            padding: 0.75rem 1rem;
        }

        .stats-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stats-card .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Compact Tables */
        .table-compact {
            font-size: 0.85rem;
        }

        .table-compact th,
        .table-compact td {
            padding: 0.5rem 0.75rem;
            vertical-align: middle;
        }

        .table-compact .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Action Buttons */
        .btn-action {
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Modal Improvements */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #e9ecef;
        }

        /* Form Controls */
        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            border-radius: 6px;
            padding: 0.4em 0.7em;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
        }

        /* Pagination */
        .pagination {
            margin-bottom: 0;
        }

        .page-link {
            border-radius: 6px;
            margin: 0 2px;
            border: 1px solid #e0e6ed;
            color: #667eea;
        }

        .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
        }

        /* Sticky Header */
        .sticky-header {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Responsive Grid Improvements */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1rem;
            }
            
            .card-header {
                padding: 0.5rem 0.75rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .stats-card .stat-value {
                font-size: 1.25rem;
            }
        }

        /* ===== GLOBAL ADMIN DESIGN SYSTEM ===== */
        
        /* Content Container */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        
        /* Header Row Layout */
        .header-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            margin-left: 1rem;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: white;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .header-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .header-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .header-center {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .header-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .header-right {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        /* Compact Form Controls */
        .form-control-compact {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            min-width: 120px;
        }
        
        .form-control-compact::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-control-compact:focus {
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.15);
            outline: none;
        }
        
        /* Compact Buttons */
        .btn-compact-primary {
            background: #28a745;
            border: 1px solid #28a745;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-compact-primary:hover {
            background: #218838;
            border-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
        }
        
        .btn-compact-secondary {
            background: #6c757d;
            border: 1px solid #6c757d;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-compact-secondary:hover {
            background: #5a6268;
            border-color: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-compact-warning {
            background: #ffc107;
            border: 1px solid #ffc107;
            color: #212529;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-compact-warning:hover {
            background: #e0a800;
            border-color: #e0a800;
            transform: translateY(-1px);
        }
        
        .btn-compact-danger {
            background: #dc3545;
            border: 1px solid #dc3545;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-compact-danger:hover {
            background: #c82333;
            border-color: #c82333;
            transform: translateY(-1px);
        }
        
        /* Compact Table */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-left: 1rem;
            margin-right: 1rem;
        }
        
        .table-compact {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }
        
        .table-compact thead {
            background: #f8f9fa;
        }
        
        .table-compact thead th {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border: none;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-compact tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }
        
        .table-compact tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table-compact tbody tr:last-child {
            border-bottom: none;
        }
        
        .table-compact tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border: none;
        }
        
        /* Student/Data Display Elements */
        .student-photo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        
        .student-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            color: #6c757d;
        }
        
        .student-info .name,
        .name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .student-info .details,
        .details {
            font-size: 0.875rem;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .contact-info .name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .contact-info .phone {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .school-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .date-range {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .time-ranges {
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .qr-code-small {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
        }
        
        /* Badge Styles */
        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #6c757d;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Button Groups */
        .btn-group {
            display: flex;
            gap: 0.25rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h5 {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        
        .empty-state p {
            margin: 0;
            opacity: 0.8;
        }
        
        /* Pagination */
        .pagination-wrapper {
            padding: 1rem;
            display: flex;
            justify-content: center;
            border-top: 1px solid #f3f4f6;
        }
        
        .pagination-wrapper .pagination {
            margin: 0;
        }
        
        /* ===== END GLOBAL DESIGN SYSTEM ===== */
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="hamburger-menu" id="hamburgerMenu">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="hamburger-label">Menu</span>
        </div>
        <div class="logo">
            <span>AD</span>
        </div>
        <h2>
            {{ auth()->user()->name ?? 'Admin' }}
        </h2>
        @if(auth()->check())
            <div class="user-info">
                {{ '@' . auth()->user()->username }}
            </div>
        @endif
        <ul>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-home"></i></span> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.manage-schools') }}" class="{{ request()->routeIs('admin.manage-schools') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-school"></i></span> <span>Schools</span>
                </a>
            </li>
                        <li>
                <a href="{{ route('admin.manage-semesters') }}" class="{{ request()->routeIs('admin.manage-semesters') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-calendar-alt"></i></span> <span>Semesters</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.manage-teachers') }}" class="{{ request()->routeIs('admin.manage-teachers') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-chalkboard-teacher"></i></span> <span>Teachers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.manage-students') }}" class="{{ request()->routeIs('admin.manage-students') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-user-graduate"></i></span> <span>Students</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.message') }}" class="{{ request()->routeIs('admin.message') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-comments"></i></span> <span>Message</span>
                </a>
            </li>
             
            <li>
                <a href="{{ route('admin.teacher-attendance-reports') }}" class="{{ request()->routeIs('admin.teacher-attendance-reports') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-chalkboard-teacher"></i></span> <span>Teacher Reports</span>
                </a>
            </li>
            <li>
                <a href="#" class="">
                    <span class="icon"><i class="fas fa-cog"></i></span> <span>Manage Account</span>
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}" style="width:100%;">
                    @csrf
                    <button type="submit" class="d-flex align-items-center">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span> <span>Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>
    <div class="content" id="content">
    <div id="flash-messages" style="position:relative; z-index:1001;"></div>
    @yield('content')
    </div>
     <script>
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        
        // Load saved state
        if (localStorage.getItem('sidebarClosed') === 'true') {
            sidebar.classList.add('closed');
            content.classList.add('expanded');
        }
        
        hamburgerMenu.addEventListener('click', () => {
            sidebar.classList.toggle('closed');
            content.classList.toggle('expanded');
            localStorage.setItem('sidebarClosed', sidebar.classList.contains('closed'));
        });
        
        // Global helper to display flash messages from AJAX JSON responses
        function showFlashFromJson(payload) {
            try {
                if(!payload) return;
                const container = document.getElementById('flash-messages');
                const wrapper = document.createElement('div');
                wrapper.className = 'alert alert-danger alert-dismissible fade show';
                wrapper.role = 'alert';

                const title = document.createElement('div');
                title.innerHTML = payload.message || 'Validation error';
                wrapper.appendChild(title);

                if(payload.errors && typeof payload.errors === 'object') {
                    const ul = document.createElement('ul');
                    ul.className = 'mb-0 mt-2';
                    for(const key in payload.errors) {
                        if(!Object.prototype.hasOwnProperty.call(payload.errors, key)) continue;
                        const items = payload.errors[key];
                        if(Array.isArray(items)) {
                            items.forEach(it => {
                                const li = document.createElement('li');
                                li.textContent = it;
                                ul.appendChild(li);
                            });
                        }
                    }
                    wrapper.appendChild(ul);
                }

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-close';
                btn.setAttribute('data-bs-dismiss', 'alert');
                btn.setAttribute('aria-label', 'Close');
                wrapper.appendChild(btn);

                // insert at top
                container.prepend(wrapper);
            } catch(e) {
                // silent
                console.error('flash helper error', e);
            }
        }

        // Intercept fetch responses globally (optional): hook fetch to auto-handle 422 JSON
        (function(){
            if(!window.fetch) return;
            const originalFetch = window.fetch;
            window.fetch = function(){
                return originalFetch.apply(this, arguments).then(resp => {
                    if(resp && resp.status === 422) {
                        const clone = resp.clone();
                        clone.json().then(json => {
                            // only show if structure matches
                            if(json && (json.errors || json.message)) {
                                showFlashFromJson(json);
                            }
                        }).catch(()=>{});
                    }
                    return resp;
                });
            };
        })();
    </script>
</body>
</html>
