<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan-to-Notify | QR Attendance & Parent Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --brand:#0d6efd; --brand-accent:#4dabf7; --bg:#f5f7fb; }
        html,body { height:100%; }
        body { font-family: system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; background: var(--bg); color:#2d3436; margin:0; display:flex; flex-direction:column; overflow:hidden; }
        .nav-blur { backdrop-filter: blur(10px); background:rgba(255,255,255,0.85); border-bottom:1px solid rgba(0,0,0,.05); }
        .logo-box { width:56px; height:56px; border-radius:14px; background:linear-gradient(135deg,#e3f2ff,#ffffff); display:flex; align-items:center; justify-content:center; box-shadow:0 3px 10px rgba(0,0,0,.05); }
        .logo-box span { font-size:26px; font-weight:700; background:linear-gradient(90deg,var(--brand),var(--brand-accent)); -webkit-background-clip:text; color:transparent; }
        header.hero { position:relative; flex:1; display:flex; align-items:center; padding: calc(70px + 1rem) 0 1.25rem; /* top accounts for fixed nav */ overflow:hidden; }
        @media (max-width: 576px){ header.hero { padding: calc(60px + .75rem) 0 1rem; } }
        .gradient-circle { position:absolute; width:480px; height:480px; background:radial-gradient(circle at 30% 30%,rgba(13,110,253,.17),transparent 70%); top:-140px; left:-140px; pointer-events:none; }
        .gradient-circle.two { bottom:-160px; right:-160px; top:auto; left:auto; background:radial-gradient(circle at 70% 70%,rgba(77,171,247,.16),transparent 70%); }
        .hero h1 { font-weight:700; letter-spacing:-1px; font-size:clamp(1.9rem,3.6vw,2.9rem); }
        .tagline { font-size:clamp(.95rem,1.1vw,1.05rem); max-width:560px; }
        .cta-buttons .btn { padding:.75rem 1.3rem; border-radius:14px; font-weight:600; }
        .btn-glow { box-shadow:0 0 0 0 rgba(13,110,253,.5); animation:pulse 3.5s infinite; }
        @keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(13,110,253,.45);} 70% { box-shadow:0 0 0 18px rgba(13,110,253,0);} 100% { box-shadow:0 0 0 0 rgba(13,110,253,0);} }
        .badge-soft { background:rgba(13,110,253,.08); color:var(--brand); font-weight:600; border-radius:30px; padding:.4rem .75rem; font-size:.65rem; letter-spacing:.5px; }
        .logo-placeholder { width:120px; height:120px; border:3px dashed #b6c6d8; border-radius:24px; display:flex; align-items:center; justify-content:center; font-weight:600; color:#6c7a89; margin-bottom:.9rem; font-size:.7rem; background:#f8fbff; }
        .glass-panel { background:rgba(255,255,255,0.6); border:1px solid rgba(255,255,255,0.7); backdrop-filter:blur(14px); border-radius:20px; padding:1.15rem 1.15rem 1.2rem; box-shadow:0 8px 28px -12px rgba(31,70,122,.25); max-width:370px; }
        .login-panel input { border-radius:10px; padding:.65rem .85rem; }
        .mini-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:1rem; }
        .mini-item { background:#fff; border:1px solid #e5e9f1; border-radius:12px; padding:.6rem .55rem; display:flex; align-items:flex-start; gap:.5rem; font-size:.65rem; line-height:1.05rem; }
        .mini-item i { font-size:.9rem; color:var(--brand); }
        .footer-inline { position:fixed; left:0; bottom:0; width:100%; text-align:center; font-size:.65rem; color:#6c7a89; background:rgba(255,255,255,0.9); padding:.5rem 0; border-top:1px solid rgba(0,0,0,.05); z-index:1000; }
        @media (max-width: 992px){ .glass-panel { margin:2rem auto 0; } body { overflow:auto; } }
        /* Allow scroll on very small heights to prevent content cut-off */
        @media (max-height: 620px){ body { overflow:auto; } }
    </style>
</head>
<body>
<nav class="nav-blur py-1 fixed-top">
  <div class="container d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
          <div class="logo-box"><span>


          </span>
        </div>

      </div>
      <div class="d-flex align-items-center gap-1">
      </div>
  </div>
</nav>

<header class="hero">
    <div class="gradient-circle"></div>
    <div class="gradient-circle two"></div>
    <div class="container position-relative">
        <div class="row g-4 align-items-center flex-lg-row flex-column-reverse">
            <div class="col-lg-6 d-flex flex-column justify-content-center">
                <div class="logo-placeholder flex-column text-center text-lg-start">

                
                </div>
                <h1 class="mb-3">
            
                Scan-to-Notify <span class="text-primary">QR Attendance </span> with Parent Notification</h1>
                <p class="tagline text-secondary mb-3">Streamline attendance capture, eliminate manual errors, and keep families informed in real time—all within a single secure platform.</p>
                <div class="cta-buttons d-flex flex-wrap gap-2 mb-3">
                </div>
                <div class="d-flex align-items-center gap-2 small text-secondary flex-wrap">
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
            <div class="col-lg-6 d-flex justify-content-lg-end justify-content-center">
                <div class="glass-panel shadow-sm" id="login">
                    <div class="text-uppercase fw-semibold small mb-2 text-primary">Login</div>
                    <h6 class="fw-semibold mb-3">Administrator / Teacher Login</h6>
                    <form method="POST" action="{{ route('login') }}" class="login-panel">
                        @csrf
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
                    <div class="mt-3 small text-secondary">Need help? Contact your system administrator.</div>
                </div>
            </div>
        </div>
    </div>
</header>

<footer class="footer-inline">&copy; {{ date('Y') }} Scan-to-Notify • All rights reserved.</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>