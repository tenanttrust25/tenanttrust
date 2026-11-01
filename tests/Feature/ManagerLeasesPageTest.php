<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagerLeasesPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The User model does not implement FilamentUser, so Filament's auth
        // middleware only permits access when app.env is "local" (matches prod).
        config(['app.env' => 'local']);
    }

    private function admin(): User
    {
        $admin = new User();
        $admin->name = 'Test Admin';
        $admin->email = 'test-admin-' . uniqid() . '@tenanttrust.ca';
        $admin->role = 'admin';
        $admin->password = bcrypt('secret');
        $admin->save();

        return $admin;
    }

    public function test_manager_leases_index_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/manager/leases')
            ->assertOk();
    }

    public function test_manager_lease_create_page_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/manager/leases/create')
            ->assertOk();
    }
}
