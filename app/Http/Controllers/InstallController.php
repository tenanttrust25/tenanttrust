<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    public function index()
    {
        // If already installed (has admin user), redirect
        if (User::where("role", "admin")->exists()) {
            return redirect("/")->with("info", "System is already installed.");
        }

        return view("install");
    }

    public function run(Request $request)
    {
        $request->validate([
            "site_name" => "required|max:255",
            "site_address" => "required|max:500",
            "site_phone" => "required|max:20",
            "site_email" => "required|email|max:255",
            "admin_name" => "required|max:255",
            "admin_email" => "required|email|max:255|unique:users,email",
            "admin_password" => "required|min:8|confirmed",
        ]);

        try {
            // Update .env with site info
            $this->updateEnv("APP_NAME", '"' . str_replace('"', '', $request->site_name) . '"');

            // Run migrations
            Artisan::call("migrate", ["--force" => true]);

            // Create admin user
            User::create([
                "name" => $request->admin_name,
                "email" => $request->admin_email,
                "password" => Hash::make($request->admin_password),
                "role" => "admin",
            ]);

            // Clear caches
            Artisan::call("optimize:clear");

            return redirect("/admin/login")->with("success", "Installation complete! Log in with your admin credentials.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(["install_error" => "Installation failed: " . $e->getMessage()]);
        }
    }

    private function updateEnv($key, $value)
    {
        $path = base_path(".env");
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if (strpos($content, $key) !== false) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}\n";
            }
            file_put_contents($path, $content);
        }
    }
}
