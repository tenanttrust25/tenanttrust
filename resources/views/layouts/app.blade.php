<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tenant Trust - Professional Property Management Services">
    <title>@yield('title', 'Tenant Trust - Property Management')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Work Sans', sans-serif;
            color: #4A4A4A;
            background-color: #FFFFFF;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Manrope', sans-serif;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; }

        /* Header / Navigation */
        .site-header {
            position: absolute;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 20px 0;
            background: transparent;
        }
        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .site-logo {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: #DCA54A;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .site-logo span { color: #FFFFFF; }
        .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
        .nav-links a {
            font-family: 'Manrope', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f6f6f6;
            transition: color 0.3s ease;
            position: relative;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0;
            width: 0; height: 2px;
            background: #DCA54A;
            transition: width 0.3s ease;
        }
        .nav-links a:hover { color: #DCA54A; }
        .nav-links a.btn-nav {
            background: #DCA54A;
            color: #0F172A !important;
            padding: 8px 22px;
            border-radius: 6px;
            font-weight: 700;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .nav-links a.btn-nav:hover { background: #c9953e; }
        .nav-links a.btn-nav::after { display: none; }
        .nav-links a:hover::after { width: 100%; }
        .nav-links a.active { color: #DCA54A; }
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 4px;
        }
        .hamburger span {
            width: 28px; height: 3px;
            background: #f6f6f6;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 6px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -6px); }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 14px 32px;
            font-family: 'Manrope', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-radius: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn-gold {
            background: #DCA54A;
            color: #0F172A;
        }
        .btn-gold:hover {
            background: #D09A40;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 165, 74, 0.4);
        }
        .btn-outline {
            background: transparent;
            color: #DCA54A;
            border: 2px solid #DCA54A;
        }
        .btn-outline:hover {
            background: #DCA54A;
            color: #0F172A;
            transform: translateY(-2px);
        }
        .btn-white {
            background: #FFFFFF;
            color: #0F172A;
        }
        .btn-white:hover {
            background: #F0E6C5;
            transform: translateY(-2px);
        }
        .btn-dark {
            background: #0F172A;
            color: #FFFFFF;
        }
        .btn-dark:hover {
            background: #1e293b;
            transform: translateY(-2px);
        }

        /* Sections */
        .section-title {
            font-size: 2rem;
            color: #0F172A;
            text-align: center;
            margin-bottom: 12px;
        }
        .section-subtitle {
            text-align: center;
            color: #4A4A4A;
            max-width: 600px;
            margin: 0 auto 48px;
            font-size: 1.05rem;
        }
        .gold-accent {
            width: 60px; height: 3px;
            background: #DCA54A;
            margin: 0 auto 16px;
        }

        /* Property Cards */
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 32px;
        }
        .property-card {
            background: #FFFFFF;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: all 0.4s ease;
            border: 1px solid #eee;
        }
        .property-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }
        .property-card-image {
            height: 240px;
            background: #F0E6C5;
            position: relative;
            overflow: hidden;
        }
        .property-card-image img {
            width: 100%; height: 100%;
            object-fit: contain;
        }
        .property-card-image .placeholder {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            font-size: 3rem; color: #DCA54A;
        }
        .property-card-badge {
            position: absolute;
            top: 16px; left: 16px;
            background: #DCA54A;
            color: #0F172A;
            padding: 6px 14px;
            border-radius: 4px;
            font-family: 'Manrope', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .property-card-content {
            padding: 24px;
        }
        .property-card-content h3 {
            font-size: 1.2rem;
            color: #0F172A;
            margin-bottom: 4px;
        }
        .property-card-address {
            font-size: 0.9rem;
            color: #4A4A4A;
            margin-bottom: 12px;
        }
        .property-card-details {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            color: #666;
        }
        .property-card-details span { display: flex; align-items: center; gap: 4px; }
        .property-card-price {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: #DCA54A;
        }

        /* Footer */
        .site-footer {
            background: #0F172A;
            color: #FFFFFF;
            padding: 64px 0 32px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }
        .footer-brand {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: #DCA54A;
            margin-bottom: 16px;
            display: block;
        }
        .footer-about {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.8;
        }
        .footer-heading {
            font-family: 'Manrope', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #DCA54A;
            margin-bottom: 20px;
        }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a {
            color: #FFFFFF;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        .footer-links a:hover { color: #DCA54A; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 24px;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }

        /* Hero */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .hero-bg img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.6) 100%);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
            color: #FFFFFF;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            line-height: 1.15;
            margin-bottom: 20px;
            color: #FFFFFF;
        }
        .hero-content h1 span { color: #DCA54A; }
        .hero-content p {
            font-size: 1.2rem;
            color: #e2e8f0;
            margin-bottom: 36px;
            line-height: 1.8;
        }
        .hero-buttons { display: flex; gap: 16px; flex-wrap: wrap; }

        /* Section padding */
        .section { padding: 80px 0; }
        .section-light { background: #FAF5E5; }
        .section-dark { background: #0F172A; }
        .section-dark .section-title { color: #FFFFFF; }
        .section-dark .section-subtitle { color: #94a3b8; }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%);
            padding: 80px 0;
            text-align: center;
            color: #FFFFFF;
        }
        .cta-section h2 {
            font-size: 2.4rem;
            margin-bottom: 16px;
        }
        .cta-section h2 span { color: #DCA54A; }
        .cta-section p {
            font-size: 1.1rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 32px;
        }

        /* Contact Form */
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
        }
        .contact-form { }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-family: 'Manrope', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0F172A;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-family: 'Work Sans', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #FFFFFF;
        }
        .form-control:focus {
            outline: none;
            border-color: #DCA54A;
        }
        textarea.form-control { min-height: 140px; resize: vertical; }
        .contact-info h3 {
            font-size: 1.4rem;
            color: #0F172A;
            margin-bottom: 24px;
        }
        .contact-info-item {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .contact-info-icon {
            width: 48px; height: 48px;
            background: #F0E6C5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .contact-info-text h4 {
            font-family: 'Manrope', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #0F172A;
            margin-bottom: 4px;
        }
        .contact-info-text p { font-size: 0.95rem; color: #4A4A4A; }

        /* Alert messages */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid #fecaca;
        }

        /* About page */
        .about-hero {
            background: #0F172A;
            color: #FFFFFF;
            padding: 180px 0 80px;
        }
        .about-hero h1 { font-size: 3rem; margin-bottom: 16px; }
        .about-hero h1 span { color: #DCA54A; }
        .about-hero p { color: #94a3b8; font-size: 1.1rem; max-width: 700px; }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
        }
        .about-text h2 { font-size: 1.8rem; color: #0F172A; margin-bottom: 20px; }
        .about-text p { margin-bottom: 16px; line-height: 1.8; }
        .about-image {
            border-radius: 12px;
            overflow: hidden;
            background: #F0E6C5;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #DCA54A;
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }
        .value-card {
            text-align: center;
            padding: 40px 24px;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .value-card-icon {
            width: 72px; height: 72px;
            background: #F0E6C5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
        }
        .value-card h3 {
            font-size: 1.1rem;
            color: #0F172A;
            margin-bottom: 12px;
        }
        .value-card p { font-size: 0.9rem; color: #4A4A4A; line-height: 1.7; }

        /* Property Detail */
        .property-detail-hero {
            background: #0F172A;
            padding: 160px 0 60px;
            color: #FFFFFF;
        }
        .property-detail-hero h1 { font-size: 2.4rem; margin-bottom: 8px; }
        .property-detail-hero .location { color: #94a3b8; font-size: 1rem; }
        .property-detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 48px;
        }
        .property-main-image {
            width: 100%;
            height: 480px;
            background: #F0E6C5;
            border-radius: 12px;
            overflow: hidden;
        }
        .property-main-image img { width: 100%; height: 100%; object-fit: contain; }
        .property-main-image .placeholder {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            font-size: 5rem; color: #DCA54A;
        }
        .property-specs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 32px 0;
        }
        .property-spec {
            text-align: center;
            padding: 20px 12px;
            background: #FAF5E5;
            border-radius: 8px;
        }
        .property-spec-value {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: #0F172A;
        }
        .property-spec-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #4A4A4A;
            margin-top: 4px;
        }
        .property-sidebar {
            background: #FAF5E5;
            padding: 32px;
            border-radius: 12px;
            position: sticky;
            top: 100px;
        }
        .property-sidebar-price {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            font-size: 2rem;
            color: #DCA54A;
            margin-bottom: 8px;
        }
        .property-sidebar-label {
            font-size: 0.85rem;
            color: #4A4A4A;
            margin-bottom: 24px;
        }
        .property-sidebar .btn { width: 100%; text-align: center; margin-bottom: 12px; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 48px;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px; height: 40px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .pagination a {
            background: #FFFFFF;
            color: #0F172A;
            border: 1px solid #e2e8f0;
        }
        .pagination a:hover {
            background: #DCA54A;
            color: #0F172A;
            border-color: #DCA54A;
        }
        .pagination .active span {
            background: #DCA54A;
            color: #0F172A;
            border: 1px solid #DCA54A;
        }

        /* Filters */
        .filters-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .filters-bar select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-family: 'Work Sans', sans-serif;
            font-size: 0.9rem;
            background: #FFFFFF;
            min-width: 200px;
        }
        .filters-bar select:focus { outline: none; border-color: #DCA54A; }
        .filters-bar .btn { padding: 12px 24px; }

        /* Error / Success states */
        .form-error { color: #dc2626; font-size: 0.8rem; margin-top: 4px; }

        /* Page content from CMS */
        .page-content h1, .page-content h2, .page-content h3 {
            color: #0F172A;
            margin: 32px 0 16px;
        }
        .page-content h1 { font-size: 2rem; }
        .page-content h2 { font-size: 1.6rem; }
        .page-content h3 { font-size: 1.3rem; }
        .page-content p { margin-bottom: 16px; line-height: 1.8; }
        .page-content ul, .page-content ol { margin: 16px 0; padding-left: 24px; }
        .page-content li { margin-bottom: 8px; }

        /* Page header for CMS pages */
        .page-header {
            background: #0F172A;
            padding: 160px 0 60px;
            color: #FFFFFF;
        }
        .page-header h1 { font-size: 2.8rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .nav-links {
                display: none;
                position: absolute;
                top: 100%; left: 0; right: 0;
                background: #0F172A;
                flex-direction: column;
                padding: 24px;
                gap: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            .nav-links.open { display: flex; }
            .hero-content h1 { font-size: 2.2rem; }
            .hero-buttons { flex-direction: column; }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .contact-section {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .property-detail-grid {
                grid-template-columns: 1fr;
            }
            .property-specs {
                grid-template-columns: repeat(2, 1fr);
            }
            .about-grid {
                grid-template-columns: 1fr;
            }
            .values-grid {
                grid-template-columns: 1fr;
            }
            .property-grid {
                grid-template-columns: 1fr;
            }
            .about-hero h1 { font-size: 2rem; }
            .page-header h1 { font-size: 2rem; }
            .section-title { font-size: 1.6rem; }
            .cta-section h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <a href="/" class="site-logo"><span>Tenant</span> Trust</a>
            <button class="hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="/" @if(request()->is('/')) class="active" @endif>Home</a></li>
                <li><a href="/properties" @if(request()->is('properties*')) class="active" @endif>Properties</a></li>
                <li><a href="/about" @if(request()->is('about')) class="active" @endif>About</a></li>
                <li><a href="/portals" class="btn-nav">Portals</a></li>
                <li><a href="/apply" @if(request()->is('apply*')) class="active" @endif>Apply</a></li>
                <li><a href="/contact" @if(request()->is('contact')) class="active" @endif>Contact</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">Tenant Trust</div>
                    <p class="footer-about">Professional property management services you can trust. We connect quality tenants with exceptional properties across Canada.</p>
                </div>
                <div>
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="/">Home</a></li>
                        <li><a href="/properties">Properties</a></li>
                        <li><a href="/about">About Us</a></li>
                        <li><a href="/portals" class="btn-nav">Portals</a></li>
                <li><a href="/contact">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-heading">Contact</h4>
                    <ul class="footer-links">
                        <li><a href="/portals" class="btn-nav">Portals</a></li>
                <li><a href="/contact">Get In Touch</a></li>
                        <li><a href="mailto:info@tenanttrust.ca">info@tenanttrust.ca</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; {{ date('Y') }} Tenant Trust. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('navLinks');
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', function() {
                this.classList.toggle('active');
                navLinks.classList.toggle('open');
            });
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navLinks.classList.remove('open');
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
