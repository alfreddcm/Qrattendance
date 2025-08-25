<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    <script src="{{ asset('js/all.min.js') }}"></script>
    
    <style>
        
        #camera-video {
            transform: scaleX(-1); 
        }
        
        #captured-photo {
            transform: scaleX(-1); 
        }
        
        .camera-btn {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .image-preview-container {
            position: relative;
            display: inline-block;
        }
        
        .image-preview-container .btn-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        
        .analytics-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .analytics-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .analytics-card:hover {
            transform: translateY(-2px);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
 
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            height: 100vh;
            display: flex;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            border-right: 3px solid #00a86b;
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 5px 0 10px 0;
            transition: width 0.3s;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
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
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 50%;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            color: #2c3e50;
            font-weight: bold;
            transition: width 0.3s, height 0.3s, font-size 0.3s;
            overflow: hidden;
            position: relative;
        }
        .sidebar .logo img {
            transition: width 0.3s, height 0.3s;
        }
        .sidebar.closed .logo {
            width: 40px;
            height: 40px;
            font-size: 1em;
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
            margin: 0 0 25px 0;
            font-size: 1.1em;
            font-weight: 600;
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
            gap: 15px;
            padding: 16px 15px;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, padding 0.3s;
            border-left: 4px solid transparent;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 1em;
            line-height: 1.4;
            min-height: 52px;
            box-sizing: border-box;
            cursor: pointer;
        }
        .sidebar ul li a.active,
        .sidebar ul li a:hover,
        .sidebar ul li form button:hover {
            background: #34495e;
            border-left: 4px solid #1abc9c;
        }
        .sidebar ul li a .icon,
        .sidebar ul li form button .icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
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
            padding: 16px 20px;
            justify-content: center;
            gap: 0;
            min-height: 52px;
        }
        .sidebar.closed ul li a span:not(.icon),
        .sidebar.closed ul li form button span:not(.icon) {
            display: none;
        }
        .sidebar.closed .user-info {
            display: none;
        }
        .sidebar .user-info {
            font-size: 0.95em;
            color: #bbb;
            margin-bottom: 10px;
            transition: opacity 0.3s;
        }
        .content {
            flex: 1;
            padding: 0 40px 40px 40px;
            background: #f4f4f4;
            min-width: 0;
            height: 100vh;
            overflow-y: auto;
            transition: margin-left 0.3s;
            position: relative;
            margin-left: 250px;
        }

        .sidebar.closed ~ .content {
            margin-left: 60px;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 997;  
            background: #f4f4f4;
            padding: 20px 20px 20px 80px;  
            margin: 0 -40px 20px -40px;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 72px;  
            display: flex;
            align-items: center;
        }
        
        .sticky-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sticky-header .subtitle {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 5px 0 0 0;
            font-weight: normal;
        }
        
        .sticky-header .page-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .sticky-header {
                padding: 15px 20px;
                margin: 0 -20px 15px -20px;
            }
            
            .sticky-header h2 {
                font-size: 1.5rem;
            }
            
            .sticky-header .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            
            .sticky-header .page-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .sticky-header h2 {
                font-size: 1.25rem;
            }
            
            .sticky-header .page-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .sticky-header .page-actions .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 998;
            }
            .content {
                padding: 0 20px 20px 20px;
                margin-left: 0;
            }
            .sidebar.closed {
                transform: translateX(-100%);
            }
            .sidebar.closed ~ .content {
                margin-left: 0;
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
        
        .sidebar.closed ~ .content .sticky-header {
            padding-left: 80px; 
        }
</style>
</head>

<body>
    <div id="mobile-overlay" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:#222; color:#fff; z-index:9999; align-items:center; justify-content:center; flex-direction:column; text-align:center; font-size:1.5em;">
        <div>Dev is tired,<br>Please switch to desktop</div>
    </div>
    <div class="sidebar" id="sidebar">
         <div class="hamburger-menu" id="hamburgerToggle">
            <div class="hamburger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="hamburger-label">Menu</span>
        </div>

        <div class="logo">
            @if(auth()->user()->school && auth()->user()->school->logo)
                <img src="{{ asset('storage/' . auth()->user()->school->logo) }}" 
                     alt="{{ auth()->user()->school->name ?? 'School' }} Logo" 
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <span style="display: none;">QR</span>
            @else
                <span>QR</span>
            @endif
        </div>
        <h2>
            {{ auth()->user()->name ?? 'Account' }}
        </h2>
        @if(auth()->check())
            <div class="user-info">
                {{ '@' . auth()->user()->username }}
            </div>
        @endif
        <ul>
            <li>
                <a href="{{ route('teacher.dashboard') }}" class="{{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-home"></i></span> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.semesters') }}" class="{{ request()->routeIs('teacher.semesters') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-layer-group"></i></span> <span>Semester and Sections</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.students') }}" class="{{ request()->routeIs('teacher.students') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-user-graduate"></i></span> <span>Student</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.message') }}" class="{{ request()->routeIs('teacher.message') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-comments"></i></span> <span>Message</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.attendance') }}" class="{{ request()->routeIs('teacher.attendance') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-calendar-check"></i></span> <span>Attendance</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.report') }}" class="{{ request()->routeIs('teacher.report') ? 'active' : '' }}">
                    <span class="icon"><i class="fas fa-chart-bar"></i></span> <span>Report</span>
                </a>
            </li>
            <li>
                <a href="{{ route('teacher.account') }}" class="{{ request()->routeIs('teacher.account') ? 'active' : '' }}">
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
    <div class="content">
        @yield('content')
    </div>
    <script>
        // Mobile overlay logic
        function checkMobileOverlay() {
            if (window.innerWidth <= 600) {
                document.getElementById('mobile-overlay').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } else {
                document.getElementById('mobile-overlay').style.display = 'none';
                document.body.style.overflow = '';
            }
        }
        window.addEventListener('resize', checkMobileOverlay);
        window.addEventListener('DOMContentLoaded', checkMobileOverlay);
  
        const hamburgerToggle = document.getElementById('hamburgerToggle');
        const sidebar = document.getElementById('sidebar');

        
        if (localStorage.getItem('sidebarClosed') === 'true') {
            sidebar.classList.add('closed');
        }

        hamburgerToggle.addEventListener('click', () => {
            sidebar.classList.toggle('closed');
            
            localStorage.setItem('sidebarClosed', sidebar.classList.contains('closed'));
        });
    </script>
</body>
</html>