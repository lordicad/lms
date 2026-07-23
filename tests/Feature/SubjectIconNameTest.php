<?php

namespace Tests\Feature;

use App\Models\Subject;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Subject::iconName() maps each subject's slug to a vector icon in the shared set, so the UI can
 * drop the stored emoji. The cases that matter are the ones that would otherwise slip: sign
 * language must not be a language book, and an unknown subject must fall back rather than error.
 */
class SubjectIconNameTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function slugs(): array
    {
        return [
            'sign language is a hand, not a book' => ['bahasa-isyarat-malaysia', 'hand'],
            'a language is the language glyph' => ['bahasa-tamil-sjk', 'language'],
            'maths' => ['matematik', 'calculator'],
            'islamic education' => ['pendidikan-islam', 'moon'],
            'science' => ['sains', 'flask'],
            'history' => ['sejarah', 'history'],
            'PE' => ['pendidikan-jasmani-dan-pendidikan-kesihatan', 'run'],
            'digital tech' => ['teknologi-dan-digital', 'laptop'],
            'unknown falls back to a book' => ['some-new-subject', 'book'],
        ];
    }

    #[DataProvider('slugs')]
    public function test_the_slug_maps_to_the_expected_icon(string $slug, string $expected): void
    {
        $subject = new Subject(['slug' => $slug]);

        $this->assertSame($expected, $subject->iconName());
    }

    /** Every icon the mapping can return must exist in the shared component. */
    public function test_every_mapped_icon_exists_in_the_set(): void
    {
        $iconSource = file_get_contents(resource_path('views/components/icon.blade.php'));

        // The real production slugs, so the mapping is exercised across every subject rather than
        // whatever a possibly-empty test database holds.
        $slugs = [
            'bahasa-melayu', 'bahasa-inggeris', 'bahasa-cina-sjk', 'bahasa-tamil-sjk', 'matematik',
            'pendidikan-islam', 'pendidikan-moral', 'alam-dan-manusia-pembelajaran-bersepadu',
            'eksplorasi-seni-dan-dunia-pembelajaran-bersepadu', 'eksplorasi-sains-dan-teknologi-pembelajaran-bersepadu',
            'sejarah', 'sains', 'pendidikan-jasmani', 'pendidikan-jasmani-dan-pendidikan-kesihatan',
            'pendidikan-seni-visual', 'pendidikan-muzik', 'teknologi-dan-digital',
            'pendidikan-asas-individu-ketidakupayaan-penglihatan', 'bahasa-isyarat-malaysia',
            'pengurusan-kehidupan-masalah-pembelajaran', 'bahasa-cina-sk', 'bahasa-tamil-sk',
            'bahasa-iban', 'bahasa-kadazandusun', 'bahasa-semai', 'bahasa-arab', 'pembentukan-karakter',
        ];

        foreach ($slugs as $slug) {
            $name = (new Subject(['slug' => $slug]))->iconName();
            $this->assertStringContainsString("'{$name}' =>", $iconSource, "the icon set has no '{$name}' for {$slug}");
        }
    }
}
