@extends("layouts.app")

@section("content")
<style>
    .install-wrapper { max-width: 680px; margin: 60px auto 80px; padding: 0 24px; }
    .install-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); overflow: hidden; }
    .install-header { background: linear-gradient(135deg, #0F172A, #1a2a4a); padding: 40px; text-align: center; }
    .install-header h1 { color: #fff; margin: 0 0 8px; font-family: Manrope, sans-serif; font-size: 1.5rem; }
    .install-header p { color: rgba(255,255,255,.7); margin: 0; font-size: .9rem; }
    .install-body { padding: 40px; }
    .install-step { margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid #e5e7eb; }
    .install-step:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
    .install-step h3 { font-size: 1rem; font-weight: 700; color: #0F172A; margin: 0 0 4px; font-family: Manrope, sans-serif; }
    .install-step .step-desc { font-size: .85rem; color: #666; margin: 0 0 16px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .9rem; transition: border .2s; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #DCA54A; box-shadow: 0 0 0 3px rgba(220,165,74,.15); }
    .form-group .error { color: #dc2626; font-size: .8rem; margin-top: 4px; }
    .btn-install { width: 100%; padding: 14px; background: #DCA54A; color: #0F172A; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .2s; }
    .btn-install:hover { background: #c9953e; }
    .btn-install:disabled { opacity: .5; cursor: not-allowed; }
    .alert-error { background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; font-size: .85rem; margin-bottom: 20px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    @media (prefers-color-scheme: dark) {
        .install-card { background: #1f2937; box-shadow: 0 4px 24px rgba(0,0,0,.3); }
        .install-step { border-bottom-color: #374151; }
        .install-step h3 { color: #f3f4f6; }
        .install-step .step-desc { color: #9ca3af; }
        .form-group label { color: #d1d5db; }
        .form-group input, .form-group textarea { background: #374151; border-color: #4b5563; color: #f3f4f6; }
        body { background: #111827; }
    }
</style>

<div class="install-wrapper">
    @if($errors->any())
        <div class="alert-error">{{ $errors->first() ?: $errors->first("install_error") }}</div>
    @endif

    <div class="install-card">
        <div class="install-header">
            <h1>🔧 System Installation</h1>
            <p>Configure your Tenant Trust installation</p>
        </div>
        <div class="install-body">
            <form method="POST" action="{{ route('install.run') }}" onsubmit="document.getElementById('install-btn').disabled=true;this.querySelector('.btn-text').textContent='Installing...';">
                @csrf

                <div class="install-step">
                    <h3>Step 1: Site Information</h3>
                    <p class="step-desc">Your company details shown throughout the system.</p>
                    <div class="form-group">
                        <label for="site_name">Site / Company Name</label>
                        <input type="text" id="site_name" name="site_name" value="{{ old('site_name', 'Tenant Trust') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="site_address">Address</label>
                        <textarea id="site_address" name="site_address" rows="2" required>{{ old('site_address', '474 McKim St Unit 3, Sudbury, ON P3C 2L6') }}</textarea>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="site_phone">Phone Number</label>
                            <input type="text" id="site_phone" name="site_phone" value="{{ old('site_phone', '249-523-3331') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="site_email">Email Address</label>
                            <input type="email" id="site_email" name="site_email" value="{{ old('site_email', 'info@tenanttrust.ca') }}" required>
                        </div>
                    </div>
                </div>

                <div class="install-step">
                    <h3>Step 2: Database</h3>
                    <p class="step-desc">Database is pre-configured in your .env file. Migrations will run automatically.</p>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;font-size:.85rem;color:#166534;">
                        ✅ Database connection detected. Tables will be created during installation.
                    </div>
                </div>

                <div class="install-step">
                    <h3>Step 3: Admin Account</h3>
                    <p class="step-desc">Create the super admin account for full system access.</p>
                    <div class="form-group">
                        <label for="admin_name">Full Name</label>
                        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name', 'Admin') }}" required>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="admin_email">Email Address</label>
                            <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email', 'info@tenanttrust.ca') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Password</label>
                            <input type="password" id="admin_password" name="admin_password" required minlength="8">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="admin_password_confirmation">Confirm Password</label>
                        <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" required minlength="8">
                    </div>
                </div>

                <button type="submit" id="install-btn" class="btn-install">
                    <span class="btn-text">🚀 Complete Installation</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
