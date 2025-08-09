<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
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
            transition: opacity 0.3s, max-height 0.3s;
            white-space: nowrap;
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
            font-size: 1.3em;
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
            margin-bottom: 20px;
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
                <a href="{{ route('admin.manage-teachers') }}" class="{{ request()->routeIs('admin.manage-teachers') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-chalkboard-teacher"></i></span> <span>Teachers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.manage-semesters') }}" class="{{ request()->routeIs('admin.manage-semesters') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-calendar-alt"></i></span> <span>Semesters</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.manage-students') }}" class="{{ request()->routeIs('admin.manage-students') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-user-graduate"></i></span> <span>Students</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.attendance-reports') }}" class="{{ request()->routeIs('admin.attendance-reports') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span> <span>Reports</span>
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
    </script>
</body>
</html>
