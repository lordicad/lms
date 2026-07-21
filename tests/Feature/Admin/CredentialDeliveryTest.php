<?php

namespace Tests\Feature\Admin;

use App\Mail\AccountCredentialsMail;
use App\Models\Grade;
use App\Models\User;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CredentialDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_new_teacher_is_emailed_their_own_sign_in_details(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Rohana',
            'username' => 'rohana',
            'email' => 'rohana@moe.gov.my',
            'password' => 'handed-over',
            'password_confirmation' => 'handed-over',
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) {
            return $mail->hasTo('rohana@moe.gov.my')
                && $mail->plainPassword === 'handed-over'
                && $mail->guardianName === null;
        });
    }

    public function test_a_new_students_details_go_to_the_guardian_email_and_offer_whatsapp(): void
    {
        $grade = Grade::factory()->level(3)->create();

        $response = $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_STUDENT,
            'name' => 'Nur Aisyah',
            'username' => 'aisyah',
            'grade_level' => $grade->level,
            'guardian_name' => 'Puan Salmah',
            'guardian_email' => 'salmah@example.com',
            'guardian_phone' => '012-345 6789',
            'password' => 'handed-over',
            'password_confirmation' => 'handed-over',
            'is_active' => 1,
        ]);

        // Email goes to the guardian, addressed to them rather than to the child.
        Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) {
            return $mail->hasTo('salmah@example.com') && $mail->guardianName === 'Puan Salmah';
        });

        // ...and a ready-to-send WhatsApp link is offered for the same guardian.
        $link = $response->getSession()->get('wa_link');
        $this->assertStringStartsWith('https://wa.me/60123456789?text=', $link);
        $this->assertStringContainsString('aisyah', urldecode($link));
        $this->assertStringContainsString('handed-over', urldecode($link));
    }

    public function test_a_student_without_a_guardian_email_still_gets_a_whatsapp_link(): void
    {
        $grade = Grade::factory()->level(3)->create();

        $response = $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_STUDENT,
            'name' => 'Harith Danial',
            'username' => 'harith',
            'grade_level' => $grade->level,
            'guardian_phone' => '+60 19-876 5432',
            'password' => 'handed-over',
            'password_confirmation' => 'handed-over',
            'is_active' => 1,
        ]);

        Mail::assertNothingSent();
        $this->assertStringStartsWith('https://wa.me/60198765432?text=', $response->getSession()->get('wa_link'));
    }

    /** A saved account must not be undone just because the mail server refused it. */
    public function test_the_account_survives_a_mail_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Gagal',
            'username' => 'gagal',
            'email' => 'gagal@moe.gov.my',
            'password' => 'handed-over',
            'password_confirmation' => 'handed-over',
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        $this->assertDatabaseHas('users', ['username' => 'gagal']);
    }

    #[DataProvider('phoneNumbers')]
    public function test_phone_numbers_normalise_to_the_international_form(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, WhatsAppLink::normalise($input));
    }

    public static function phoneNumbers(): array
    {
        return [
            'local with punctuation' => ['012-345 6789', '60123456789'],
            'local plain' => ['0123456789', '60123456789'],
            'already international' => ['+60 12-345 6789', '60123456789'],
            'international no plus' => ['60123456789', '60123456789'],
            'too short to dial' => ['12345', null],
            'empty' => ['', null],
            'missing' => [null, null],
        ];
    }
}
