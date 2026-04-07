<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    @if(auth()->check() && auth()->user()->role === 'student')
        <meta name="theme-color" content="#0d6efd">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="apple-touch-icon" href="{{ asset('img/icon.png') }}">
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (local SVG+JS mode avoids missing webfont issues) -->
    <script defer src="{{ asset('webfonts/js/all.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>
        body {
            background-color: #f5f7fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        main {
            min-height: 100vh;
            padding-top: 20px;
        }

        .mobile-fab,
        .mobile-fab-panel {
            display: none;
        }

        @media (max-width: 991.98px) {
            main {
                padding-bottom: 96px;
            }

            .navbar-toggler {
                display: none !important;
            }

            .desktop-nav-links {
                display: none !important;
            }

            .mobile-fab,
            .mobile-fab-panel {
                display: block;
            }

            .navbar .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        .mobile-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1040;
        }

        .mobile-fab-toggle {
            width: 58px;
            height: 58px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #fff;
            box-shadow: 0 12px 30px rgba(13, 110, 253, 0.35);
        }

        .mobile-fab-panel {
            position: fixed;
            right: 1rem;
            bottom: 5rem;
            z-index: 1039;
            width: 220px;
            transform: translateY(12px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease;
        }

        .mobile-fab-panel.show {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-fab-menu {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .mobile-fab-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.85rem 1rem;
            color: #334155;
            text-decoration: none;
            border: none;
            background: transparent;
        }

        .mobile-fab-link:hover {
            background: #f8fafc;
            color: #0d6efd;
        }

        .mobile-fab-divider {
            height: 1px;
            background: rgba(15, 23, 42, 0.08);
        }

    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('student.dashboard') }}">
                <i class="fas fa-graduation-cap me-2"></i>
                QR Attendance
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto desktop-nav-links">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('student.dashboard') }}">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('student.attendance') }}">
                            <i class="fas fa-history me-1"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('student.account') }}">
                            <i class="fas fa-user-circle me-1"></i> Account
                        </a>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link btn btn-link" data-pwa-install-trigger style="border: none; cursor: pointer;">
                            <i class="fas fa-download me-1"></i> Install App
                        </button>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link" style="border: none; cursor: pointer;">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Alerts -->
    @if(session('success'))
        <div class="container-fluid">
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="container-fluid">
            <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="container-fluid">
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Errors:</strong>
                <ul class="mb-0 mt-2 ms-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <div class="mobile-fab-panel" id="mobileFabPanel">
        <div class="mobile-fab-menu">
            <a class="mobile-fab-link" href="{{ route('student.dashboard') }}">
                <i class="fas fa-home text-primary"></i>
                <span>Dashboard</span>
            </a>
            <div class="mobile-fab-divider"></div>
            <a class="mobile-fab-link" href="{{ route('student.attendance') }}">
                <i class="fas fa-history text-success"></i>
                <span>Attendance</span>
            </a>
            <div class="mobile-fab-divider"></div>
            <a class="mobile-fab-link" href="{{ route('student.account') }}">
                <i class="fas fa-user-circle text-warning"></i>
                <span>Account</span>
            </a>
            <div class="mobile-fab-divider"></div>
            <button type="button" class="mobile-fab-link" data-pwa-install-trigger>
                <i class="fas fa-download text-info"></i>
                <span>Install App</span>
            </button>
            <div class="mobile-fab-divider"></div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="mobile-fab-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <div class="mobile-fab">
        <button type="button" class="mobile-fab-toggle" id="mobileFabToggle" aria-label="Open navigation menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('mobileFabToggle');
            const panel = document.getElementById('mobileFabPanel');

            if (toggle && panel) {
                toggle.addEventListener('click', function () {
                    panel.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!panel.contains(event.target) && !toggle.contains(event.target)) {
                        panel.classList.remove('show');
                    }
                });
            }
        });
    </script>

    @if(auth()->check() && auth()->user()->role === 'student')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const installButtons = Array.from(document.querySelectorAll('[data-pwa-install-trigger]'));
                const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                let deferredPrompt = null;

                if (isStandalone) {
                    return;
                }

                const updateInstallState = function () {
                    const ready = deferredPrompt !== null;
                    installButtons.forEach(function (button) {
                        button.disabled = !ready;
                    });
                };

                updateInstallState();

                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    deferredPrompt = event;
                    updateInstallState();
                });

                window.addEventListener('appinstalled', function () {
                    deferredPrompt = null;
                    updateInstallState();
                });

                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', function () {
                        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {
                            return null;
                        });
                    });
                }

                installButtons.forEach(function (installButton) {
                    installButton.addEventListener('click', async function () {
                        if (!deferredPrompt) {
                            window.alert('Install prompt is not ready yet. Open this site in Chrome or Edge, interact with the page, then try again.');
                            return;
                        }

                        deferredPrompt.prompt();
                        await deferredPrompt.userChoice;
                        deferredPrompt = null;
                        updateInstallState();
                    });
                });
            });
        </script>
    @endif
</body>
</html>