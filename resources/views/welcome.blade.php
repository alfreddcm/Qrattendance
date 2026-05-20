<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan-to-Notify | QR Attendance & Parent Notification</title>
    <meta name="theme-color" content="#0d6efd">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/icon-192.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --brand:#0d6efd; --brand-accent:#4dabf7; --bg:#f5f7fb; }
        html,body { height:100%; }
        body { font-family: system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background: var(--bg); color:#2d3436; margin:0; display:flex; flex-direction:column; overflow:hidden; }
        .nav-blur { backdrop-filter: blur(10px); background:rgba(255,255,255,0.85); border-bottom:1px solid rgba(0,0,0,.05); }
        .logo-box { width:56px; height:56px; border-radius:14px; background:linear-gradient(135deg,#e3f2ff,#ffffff); display:flex; align-items:center; justify-content:center; box-shadow:0 3px 10px rgba(0,0,0,.05); }
        .logo-box span { font-size:26px; font-weight:700; background:linear-gradient(90deg,var(--brand),var(--brand-accent)); -webkit-background-clip:text; color:transparent; }
        .logo-box img { width:40px; height:40px; object-fit:contain; }
        header.hero { position:relative; flex:1; display:flex; align-items:center; padding: calc(70px + 1rem) 0 4rem; overflow:hidden; }
        .gradient-circle { position:absolute; width:480px; height:480px; background:radial-gradient(circle at 30% 30%,rgba(13,110,253,.17),transparent 70%); top:-140px; left:-140px; pointer-events:none; }
        .gradient-circle.two { bottom:-160px; right:-160px; top:auto; left:auto; background:radial-gradient(circle at 70% 70%,rgba(77,171,247,.16),transparent 70%); }
        .hero h1 { font-weight:700; letter-spacing:-1px; font-size:clamp(1.5rem,5vw,2.9rem); line-height:1.2; }
        .tagline { font-size:clamp(.9rem,2.5vw,1.05rem); max-width:560px; line-height:1.5; }
        .cta-buttons .btn { padding:.75rem 1.3rem; border-radius:14px; font-weight:600; }
        .btn-glow { box-shadow:0 0 0 0 rgba(13,110,253,.5); animation:pulse 3.5s infinite; }
        @keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(13,110,253,.45);} 70% { box-shadow:0 0 0 18px rgba(13,110,253,0);} 100% { box-shadow:0 0 0 0 rgba(13,110,253,0);} }
        .badge-soft { background:rgba(13,110,253,.08); color:var(--brand); font-weight:600; border-radius:30px; padding:.4rem .75rem; font-size:.65rem; letter-spacing:.5px; white-space:nowrap; }
        .glass-panel { background:rgba(255,255,255,0.6); border:1px solid rgba(255,255,255,0.7); backdrop-filter:blur(14px); border-radius:20px; padding:1.5rem; box-shadow:0 8px 28px -12px rgba(31,70,122,.25); max-width:370px; width:100%; }
        .login-panel input { border-radius:10px; padding:.65rem .85rem; font-size:.95rem; }
        .mini-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:1rem; }
        .mini-item { background:#fff; border:1px solid #e5e9f1; border-radius:12px; padding:.7rem .6rem; display:flex; align-items:flex-start; gap:.5rem; font-size:.7rem; line-height:1.3; }
        .mini-item i { font-size:1rem; color:var(--brand); flex-shrink:0; }
        .footer-inline { position:fixed; left:0; bottom:0; width:100%; text-align:center; font-size:.65rem; color:#6c7a89; background:rgba(255,255,255,0.9); padding:.5rem 0; border-top:1px solid rgba(0,0,0,.05); z-index:1000; }
        .hero-logo { width:120px; height:120px; object-fit:contain; }
        
        /* Mobile Responsive Styles */
        @media (max-width: 992px) { 
            .glass-panel { margin:0 auto; max-width:100%; }
            body { overflow:auto; }
            header.hero { padding: calc(70px + 1rem) 0 5rem; min-height:auto; }
            .login-pane { margin-bottom:.5rem; }
        }
        
        @media (max-width: 768px) {
            .nav-blur .container { padding:0 1rem; }
            .logo-box { width:48px; height:48px; }
            .logo-box img { width:34px; height:34px; }
            header.hero { padding: calc(65px + 1rem) 0 4.5rem; }
            .hero h1 { font-size:1.75rem; text-align:center; }
            .tagline { text-align:center; font-size:1rem; }
            .hero-logo { width:100px; height:100px; }
            .glass-panel { padding:1.25rem; margin:1.5rem auto 0; }
            .mini-grid { gap:.45rem; margin-top:.75rem; }
            .mini-item { padding:.55rem .5rem; font-size:.68rem; }
            .mini-item i { font-size:.9rem; }
            .badge-soft { font-size:.6rem; padding:.35rem .65rem; }
            .footer-inline { position:static; }
        }
        
        @media (max-width: 576px) {
            header.hero { padding: calc(60px + .75rem) 0 4rem; }
            .hero h1 { font-size:1.5rem; margin-bottom:.75rem !important; }
            .tagline { font-size:.95rem; margin-bottom:.75rem !important; }
            .hero-logo { width:80px; height:80px; margin-bottom:.5rem; }
            .glass-panel { padding:1rem; border-radius:16px; }
            .glass-panel h6 { font-size:.95rem; }
            .login-panel input { padding:.6rem .75rem; font-size:.9rem; }
            .login-panel .btn { padding:.65rem 1rem; font-size:.9rem; }
            .mini-grid { grid-template-columns:1fr; gap:.4rem; }
            .mini-item { padding:.6rem .55rem; }
            .badge-soft { font-size:.58rem; padding:.3rem .6rem; }
            .footer-inline { font-size:.6rem; padding:.4rem 0; }
            .gradient-circle { width:300px; height:300px; top:-100px; left:-100px; }
            .gradient-circle.two { width:300px; height:300px; bottom:-120px; right:-120px; }
            .marketing-pane .tagline,
            .marketing-pane .mini-grid,
            .marketing-pane .badge-soft-row {
                display:none !important;
            }
            .marketing-pane .hero-logo {
                width:64px;
                height:64px;
            }
            .marketing-pane h1 {
                font-size:1.05rem;
                margin-bottom:.25rem !important;
            }
            .marketing-pane .mb-3.mt-4 {
                margin-top:.25rem !important;
                margin-bottom:.4rem !important;
            }
            .glass-panel {
                margin-top:0 !important;
            }
        }
        
        @media (max-width: 400px) {
            .hero h1 { font-size:1.35rem; }
            .tagline { font-size:.88rem; }
            .hero-logo { width:70px; height:70px; }
            .glass-panel { padding:.9rem; }
            .mini-item { font-size:.65rem; }
        }
        
        @media (max-height: 700px) {
            body { overflow:auto; }
            header.hero { padding: calc(65px + .5rem) 0 3.5rem; }
        }
        
        @media (max-height: 620px) { 
            body { overflow:auto; }
            header.hero { padding: calc(60px + .5rem) 0 3rem; }
            .hero-logo { width:70px; height:70px; }
        }
    </style>
</head>
<body>
<nav class="nav-blur py-1 fixed-top">
  <div class="container d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
      </div>
      <div class="d-flex align-items-center gap-1">
      </div>
  </div>
</nav>

<header class="hero">
    <div class="gradient-circle"></div>
    <div class="gradient-circle two"></div>
    <div class="container position-relative">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center marketing-pane">
                <div class="d-flex justify-content-center justify-content-lg-start mb-3 mt-4">
                    <img src="{{ url('/public-storage/branding/icon.png') }}" alt="Scan-to-Notify Logo" class="hero-logo">
                </div>
                <h1 class="mb-3 text-center text-lg-start">

                Scan-to-Notify <span class="text-primary">QR Attendance </span> with Parent Notification</h1>
                <p class="tagline text-secondary mb-3 text-center text-lg-start">Streamline attendance capture, eliminate manual errors, and keep families informed in real time—all within a single secure platform.</p>
                <div class="cta-buttons d-flex flex-wrap gap-2 mb-3">
                </div>
                <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-2 small text-secondary flex-wrap badge-soft-row">
                    <div class="badge-soft"><i class="fa fa-bolt me-1"></i> Real-Time</div>
                    <div class="badge-soft"><i class="fa fa-bell me-1"></i> Notifications</div>
                    <div class="badge-soft"><i class="fa fa-lock me-1"></i> Secure</div>
                    <div class="badge-soft"><i class="fa fa-chart-line me-1"></i> Insightful</div>
                </div>
                <div class="mini-grid">
                    <div class="mini-item"><i class="fa fa-qrcode"></i><span>Fast QR scanning & logging</span></div>
                    <div class="mini-item"><i class="fa fa-envelope"></i><span>Automatic parent alerts</span></div>
                    <div class="mini-item"><i class="fa fa-database"></i><span>Central attendance archive</span></div>
                    <div class="mini-item"><i class="fa fa-user-shield"></i><span>Role-based access</span></div>
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2 d-flex justify-content-lg-end justify-content-center login-pane">
                <div class="glass-panel shadow-sm" id="login">
                    <div class="text-uppercase fw-semibold small mb-2 text-primary" id="loginModeLabel">Login</div>
                    <h6 class="fw-semibold mb-3" id="loginTitle">Administrator / Teacher Login</h6>
                    <form method="POST" action="{{ route('login') }}" class="login-panel">
                        @csrf
                        <input type="hidden" name="app_mode" id="app_mode" value="{{ request('app_mode', '') }}">
                        <div class="mb-2">
                            <label class="form-label small text-uppercase fw-semibold mb-1">Username</label>
                            <input type="text" name="username" class="form-control" required autofocus placeholder="Enter username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-semibold mb-1">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>
                        @if($errors->has('login'))
                            <div class="alert alert-danger py-2 small mb-3">{{ $errors->first('login') }}</div>
                        @endif
                        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit"><i class="fa fa-unlock-keyhole me-2"></i> Sign In</button>
                    </form>

                    <!-- Student Login Info -->
                    <div class="mt-3 p-2 bg-light rounded small" id="studentLoginInfo" style="border-left: 3px solid #17a2b8;">
                        <strong class="d-block mb-2" style="color: #17a2b8;">
                            <i class="fas fa-graduation-cap me-1"></i> Student Login
                        </strong>
                        <div class="mb-1">
                            <span class="text-muted">Username:</span>
                            <span class="fw-semibold">Your LRN / Student ID</span>
                        </div>
                        <div>
                            <span class="text-muted">Password:</span>
                            <span class="fw-semibold">Your LRN / Student ID</span>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Students can use the same form above. Change password after first login.
                        </small>
                    </div>

                    <div class="mt-3 small text-secondary">Need help? Contact your system administrator.</div>

                </div>
            </div>
        </div>
    </div>
</header>

<footer class="footer-inline">&copy; {{ date('Y') }} Scan-to-Notify • All rights reserved.</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const appModeInput = document.getElementById('app_mode');
        const loginModeLabel = document.getElementById('loginModeLabel');
        const loginTitle = document.getElementById('loginTitle');
        const studentLoginInfo = document.getElementById('studentLoginInfo');

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                try {
                    const swUrl = '{{ asset("sw.js") }}';
                    const baseEl = document.querySelector('base');
                    let scopePath = baseEl ? baseEl.getAttribute('href') : (new URL(swUrl).pathname.replace(/sw\.js$/, ''));
                    if (!scopePath.endsWith('/')) scopePath = scopePath + '/';
                    navigator.serviceWorker.register(swUrl, { scope: scopePath }).catch(function () { return null; });
                } catch (e) {
                    // ignore registration errors
                }
            });
        }


        if (isStandalone) {
            if (appModeInput) {
                appModeInput.value = 'student-pwa';
            }

            if (loginModeLabel) {
                loginModeLabel.textContent = 'Student Login';
            }

            if (loginTitle) {
                loginTitle.textContent = 'Student Login';
            }

            if (studentLoginInfo) {
                studentLoginInfo.classList.add('border-primary');
            }
        }

        const urlMode = new URLSearchParams(window.location.search).get('app_mode');
        if (urlMode === 'student-pwa' && appModeInput) {
            appModeInput.value = 'student-pwa';
        }
    });
</script>
</body>
</html>