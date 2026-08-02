@extends("layouts.app")

@section("content")
    <div class="page-header" style="background: linear-gradient(135deg, #0F172A 0%, #1a2a4a 100%); padding: 100px 0 60px; text-align: center;">
        <h1 style="color: #fff; margin: 0;">Portals</h1>
        <p style="color: rgba(255,255,255,.7); margin-top: 12px; font-size: 1.1rem;">Select your portal to get started</p>
    </div>

    <div class="portals-grid" style="max-width: 1200px; margin: -40px auto 80px; padding: 0 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">

            <a href="/admin/login" class="portal-card" style="text-decoration: none; display: block;">
                <div class="portal-card-inner" style="background: #fff; border-radius: 16px; padding: 36px 28px; box-shadow: 0 4px 24px rgba(0,0,0,.08); text-align: center; transition: all .3s;">
                    <div class="portal-icon portal-icon-dark" style="width: 64px; height: 64px; background: #0F172A; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 1.8rem;">&#9881;&#65039;</div>
                    <h3 class="portal-title" style="color: #0F172A; margin: 0 0 6px; font-family: Manrope, sans-serif; text-transform: uppercase; font-size: 1.1rem;">Admin</h3>
                    <p class="portal-desc" style="color: #666; margin: 0; font-size: .85rem;">Full system administration and management</p>
                </div>
            </a>

            <a href="/owner/login" class="portal-card" style="text-decoration: none; display: block;">
                <div class="portal-card-inner" style="background: #fff; border-radius: 16px; padding: 36px 28px; box-shadow: 0 4px 24px rgba(0,0,0,.08); text-align: center; transition: all .3s;">
                    <div class="portal-icon portal-icon-gold" style="width: 64px; height: 64px; background: #DCA54A; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 1.8rem;">&#128100;</div>
                    <h3 class="portal-title" style="color: #0F172A; margin: 0 0 6px; font-family: Manrope, sans-serif; text-transform: uppercase; font-size: 1.1rem;">Client</h3>
                    <p class="portal-desc" style="color: #666; margin: 0; font-size: .85rem;">View portfolio performance, documents and reports</p>
                </div>
            </a>

            <a href="/tenant/login" class="portal-card" style="text-decoration: none; display: block;">
                <div class="portal-card-inner" style="background: #fff; border-radius: 16px; padding: 36px 28px; box-shadow: 0 4px 24px rgba(0,0,0,.08); text-align: center; transition: all .3s;">
                    <div class="portal-icon portal-icon-gold" style="width: 64px; height: 64px; background: #DCA54A; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 1.8rem;">&#127968;</div>
                    <h3 class="portal-title" style="color: #0F172A; margin: 0 0 6px; font-family: Manrope, sans-serif; text-transform: uppercase; font-size: 1.1rem;">Tenant</h3>
                    <p class="portal-desc" style="color: #666; margin: 0; font-size: .85rem;">View invoices, submit maintenance requests, and more</p>
                </div>
            </a>

        </div>
    </div>

    <style>
        @media (prefers-color-scheme: dark) {
            .portal-card-inner {
                background: #1f2937 !important;
                box-shadow: 0 4px 24px rgba(0,0,0,.3) !important;
            }
            .portal-title {
                color: #f3f4f6 !important;
            }
            .portal-desc {
                color: #9ca3af !important;
            }
            html {
                background-color: #111827;
            }
            .page-header {
                background: linear-gradient(135deg, #0F172A 0%, #111827 100%) !important;
            }
        }
    </style>
@endsection
