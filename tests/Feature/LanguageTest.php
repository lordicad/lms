<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $overrides = []): User
    {
        $grade = Grade::factory()->level(3)->create();

        return User::factory()->student($grade->level)->create($overrides + [
            'password' => bcrypt('secret123'),
            'email' => 'murid@moe.edu.my',
        ]);
    }

    public function test_signing_in_starts_in_bahasa_melayu(): void
    {
        $student = $this->student();

        $this->post(route('login'), ['login' => $student->email, 'password' => 'secret123']);

        $this->assertSame('ms', app()->getLocale());
        $this->get(route('belajar.index'))->assertOk()->assertSee('Selamat datang');
    }

    /**
     * The session survives sign-in, so a visitor who flipped the toggle on the login screen would
     * otherwise carry English into the next person's account on a shared computer.
     */
    public function test_a_guests_choice_does_not_follow_someone_else_into_their_account(): void
    {
        $student = $this->student();

        $this->get(route('locale.switch', ['locale' => 'en']));
        $this->assertSame('en', session('locale'));

        $this->post(route('login'), ['login' => $student->email, 'password' => 'secret123']);

        $this->assertNull(session('locale'));
        $this->get(route('belajar.index'))->assertOk()->assertSee('Selamat datang');
    }

    /** An account that has chosen English keeps it — the reset is only for the guest's session. */
    public function test_an_account_that_prefers_english_still_gets_english(): void
    {
        $student = $this->student(['locale' => 'en']);

        $this->post(route('login'), ['login' => $student->email, 'password' => 'secret123']);

        $this->assertSame('en', session('locale'));
        $this->get(route('belajar.index'))->assertOk()->assertSee('Welcome');
    }

    public function test_the_toggle_translates_the_page_both_ways(): void
    {
        $teacher = User::factory()->teacher()->create();

        // Bahasa Melayu: the source strings.
        $this->actingAs($teacher)->get(route('locale.switch', ['locale' => 'ms']));
        $this->actingAs($teacher)->get(route('cikgu.dashboard'))
            ->assertOk()
            ->assertSee('Video Paling Ditonton')
            ->assertSee('Lulus / Gagal Kuiz');

        // English: the same page, fully translated.
        $this->actingAs($teacher)->get(route('locale.switch', ['locale' => 'en']));
        $this->actingAs($teacher)->get(route('cikgu.dashboard'))
            ->assertOk()
            ->assertSee('Most Viewed Video')
            ->assertSee('Quiz Pass / Fail')
            ->assertDontSee('Video Paling Ditonton');
    }

    /** The strings this audit found untranslated, pinned so they cannot regress. */
    public function test_the_previously_untranslated_strings_now_translate(): void
    {
        $pairs = [
            'Gemari' => 'Favourite',
            'Perkara' => 'Item',
            '10 Teratas' => 'Top 10',
            'Jumlah percubaan selesai' => 'Completed attempts',
            'Lulus / Gagal Kuiz' => 'Quiz Pass / Fail',
            'Simpanan Offline' => 'Offline',
            'Bab dikongsi oleh semua guru mengikut sukatan Kurikulum 2027.' => 'Chapters are shared by all teachers, following the Kurikulum 2027 syllabus.',
            'Kelas tidak sepadan dengan sekolah dan tahun yang dipilih.' => 'The class does not match the selected school and year.',
        ];

        foreach ($pairs as $ms => $en) {
            app()->setLocale('ms');
            $this->assertSame($ms, __($ms), "BM should render the source string: {$ms}");

            app()->setLocale('en');
            $this->assertSame($en, __($ms), "EN translation missing for: {$ms}");
        }
    }
}
