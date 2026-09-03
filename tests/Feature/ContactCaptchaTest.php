<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactCaptchaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contact_page_shows_captcha_question(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('Security check');
        $this->assertNotNull(session('contact_captcha_answer'));
    }

    public function test_wrong_captcha_is_rejected(): void
    {
        $this->get('/contact'); // establishes the session answer

        $response = $this->post('/contact', [
            'name' => 'Test Person',
            'email' => 'test@example.com',
            'message' => 'Hello',
            'captcha' => '999',
        ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_valid_captcha_submission_succeeds(): void
    {
        Mail::fake();
        $this->get('/contact');
        $answer = session('contact_captcha_answer');

        $response = $this->post('/contact', [
            'name' => 'Test Person',
            'email' => 'test@example.com',
            'message' => 'Hello there',
            'captcha' => $answer,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contact_submissions', ['email' => 'test@example.com']);
    }

    public function test_honeypot_submission_is_silently_dropped(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Spam Bot',
            'email' => 'spam@example.com',
            'message' => 'buy now',
            'captcha' => 'whatever',
            'company_website' => 'http://spam.example.com',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseCount('contact_submissions', 0);
    }
}
