<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\RentalApplication;
use App\Services\EmailDomainGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmailDomainBanTest extends TestCase
{
    use DatabaseTransactions;

    public function test_own_domain_is_blocked(): void
    {
        $this->assertTrue(EmailDomainGuard::isBlocked('spammer@tenanttrust.ca'));
        $this->assertTrue(EmailDomainGuard::isBlocked('info@tenantrust.ca'));
        $this->assertTrue(EmailDomainGuard::isBlocked('person@mail.tenanttrust.ca'));
        $this->assertTrue(EmailDomainGuard::isBlocked('PERSON@TENANTTRUST.CA')); // case-insensitive
    }

    public function test_normal_domains_are_not_blocked(): void
    {
        $this->assertFalse(EmailDomainGuard::isBlocked('real.customer@gmail.com'));
        $this->assertFalse(EmailDomainGuard::isBlocked('someone@outlook.com'));
        $this->assertFalse(EmailDomainGuard::isBlocked('tenant@example.com'));
        $this->assertFalse(EmailDomainGuard::isBlocked(null));
    }

    public function test_contact_submission_using_own_domain_is_silently_dropped(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Spam Bot',
            'email' => 'info@tenanttrust.ca',
            'message' => 'buy our stuff',
            'captcha' => '999', // never even validated — guard drops first
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_rental_application_using_own_domain_is_silently_dropped(): void
    {
        $response = $this->post('/apply', [
            'first_name' => 'Spam',
            'last_name' => 'Bot',
            'email' => 'noreply@tenantrust.ca',
        ]);

        $response->assertRedirect(route('rental-application.thank-you'));
        $this->assertDatabaseCount('rental_applications', 0);
    }
}
