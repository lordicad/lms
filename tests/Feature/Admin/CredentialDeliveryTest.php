<?php

namespace Tests\Feature\Admin;

use App\Mail\AccountCredentialsMail;
use App\Models\Grade;
use App\Models\User;
use App\Support\TemporaryPassword;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_an_admin_password_reset_sends_the_new_details_to_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'rohana@moe.gov.my']);
        $teacher->markPasswordChanged();

        $this->actingAs($this->admin())->put(route('admin.pengguna.update', $teacher), [
            'role' => User::ROLE_TEACHER,
            'name' => $teacher->name,
            'username' => $teacher->username,
            'email' => $teacher->email,
            'password' => 'reset-by-admin',
            'password_confirmation' => 'reset-by-admin',
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        Mail::assertSent(AccountCredentialsMail::class, fn (AccountCredentialsMail $mail) => $mail->hasTo('rohana@moe.gov.my')
                && $mail->plainPassword === 'reset-by-admin');
    }

    public function test_a_reset_on_a_student_offers_the_guardian_whatsapp_link_again(): void
    {
        $grade = Grade::factory()->level(3)->create();
        $student = User::factory()->student($grade->level)->create([
            'guardian_name' => 'Puan Salmah',
            'guardian_phone' => '012-345 6789',
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.pengguna.update', $student), [
            'role' => User::ROLE_STUDENT,
            'name' => $student->name,
            'username' => $student->username,
            'grade_level' => $grade->level,
            'guardian_name' => 'Puan Salmah',
            'guardian_phone' => '012-345 6789',
            'password' => 'reset-by-admin',
            'password_confirmation' => 'reset-by-admin',
            'is_active' => 1,
        ]);

        $link = $response->getSession()->get('wa_link');
        $this->assertStringStartsWith('https://wa.me/60123456789?text=', $link);
        $this->assertStringContainsString('reset-by-admin', urldecode($link));
    }

    public function test_an_edit_that_leaves_the_password_alone_sends_nothing(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'quiet@moe.gov.my']);

        $this->actingAs($this->admin())->put(route('admin.pengguna.update', $teacher), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Nama Baharu',
            'username' => $teacher->username,
            'email' => $teacher->email,
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        Mail::assertNothingSent();
        $this->assertSame('Nama Baharu', $teacher->fresh()->name);
    }

    public function test_a_password_is_generated_when_the_admin_does_not_type_one(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Auto',
            'username' => 'auto',
            'email' => 'auto@moe.gov.my',
            'auto_password' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        $generated = $response->getSession()->get('new_password');
        $this->assertNotEmpty($generated);

        // It is the real password: it signs in, and it is what was emailed.
        $teacher = User::where('username', 'auto')->firstOrFail();
        $this->assertTrue(Hash::check($generated, $teacher->password));
        $this->assertTrue($teacher->mustChangePassword());

        Mail::assertSent(AccountCredentialsMail::class, fn (AccountCredentialsMail $mail) => $mail->plainPassword === $generated);
    }

    public function test_a_typed_password_still_wins_when_auto_is_off(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Manual',
            'username' => 'manual',
            'email' => 'manual@moe.gov.my',
            'password' => 'chosen-by-admin',
            'password_confirmation' => 'chosen-by-admin',
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        $this->assertTrue(Hash::check('chosen-by-admin', User::where('username', 'manual')->firstOrFail()->password));
    }

    public function test_a_password_is_still_required_when_auto_is_off_and_none_is_typed(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Cikgu Kosong',
            'username' => 'kosong',
            'email' => 'kosong@moe.gov.my',
            'is_active' => 1,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['username' => 'kosong']);
    }

    public function test_auto_generating_on_edit_resets_and_sends_a_fresh_password(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'reset@moe.gov.my']);
        $teacher->markPasswordChanged();

        $response = $this->actingAs($this->admin())->put(route('admin.pengguna.update', $teacher), [
            'role' => User::ROLE_TEACHER,
            'name' => $teacher->name,
            'username' => $teacher->username,
            'email' => $teacher->email,
            'auto_password' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.pengguna'));

        $generated = $response->getSession()->get('new_password');
        $this->assertNotEmpty($generated);
        $this->assertTrue(Hash::check($generated, $teacher->fresh()->password));
        $this->assertTrue($teacher->fresh()->mustChangePassword());
    }

    public function test_generated_passwords_are_readable_and_unique(): void
    {
        $seen = [];

        for ($i = 0; $i < 50; $i++) {
            $password = TemporaryPassword::generate();
            // Pronounceable syllables + digits, no characters that read as each other.
            $this->assertMatchesRegularExpression('/^[bdghjkmnprsty aeiou]{6}-\d{4}$/', $password);
            $this->assertGreaterThanOrEqual(6, strlen($password));
            $seen[] = $password;
        }

        $this->assertGreaterThan(45, count(array_unique($seen)), 'generated passwords repeat too often');
    }

    public function test_a_teacher_is_told_to_sign_in_with_their_email_not_their_nickname(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_TEACHER,
            'name' => 'Rohana Osman',
            'username' => 'Cikgu Ana',        // a display nickname, not a login
            'email' => 'rohana@moe.gov.my',
            'auto_password' => 1,
            'is_active' => 1,
        ]);

        $teacher = User::where('email', 'rohana@moe.gov.my')->firstOrFail();
        $this->assertTrue($teacher->signsInWithEmail());
        $this->assertSame('rohana@moe.gov.my', $teacher->signInIdentifier());

        // The admin's own copy shows the email, not the nickname.
        $this->assertSame('rohana@moe.gov.my', $response->getSession()->get('new_username'));

        // ...and so does the email itself.
        Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) {
            $html = $mail->render();

            return str_contains($html, 'rohana@moe.gov.my') && str_contains($html, 'Cikgu Ana');
        });
    }

    public function test_a_student_still_signs_in_with_their_username(): void
    {
        $grade = Grade::factory()->level(3)->create();

        $response = $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_STUDENT,
            'name' => 'Nur Aisyah',
            'username' => 'aisyah',
            'grade_level' => $grade->level,
            'guardian_name' => 'Puan Salmah',
            'guardian_phone' => '012-345 6789',
            'auto_password' => 1,
            'is_active' => 1,
        ]);

        $student = User::where('username', 'aisyah')->firstOrFail();
        $this->assertFalse($student->signsInWithEmail());
        $this->assertSame('aisyah', $student->signInIdentifier());
        $this->assertSame('aisyah', $response->getSession()->get('new_username'));

        // The guardian's WhatsApp message carries the username, since the child has no email.
        $this->assertStringContainsString('aisyah', urldecode($response->getSession()->get('wa_link')));
    }

    /** A student types their username to sign in, so it must stay free of spaces. */
    public function test_a_student_username_may_not_contain_spaces(): void
    {
        $grade = Grade::factory()->level(3)->create();

        $this->actingAs($this->admin())->post(route('admin.pengguna.store'), [
            'role' => User::ROLE_STUDENT,
            'name' => 'Nur Aisyah',
            'username' => 'Nur Aisyah',
            'grade_level' => $grade->level,
            'auto_password' => 1,
            'is_active' => 1,
        ])->assertSessionHasErrors('username');
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
