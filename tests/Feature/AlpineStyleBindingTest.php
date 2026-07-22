<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Alpine applies a *string* :style with setAttribute('style', ...), which replaces the whole
 * attribute — so any static style on the same element is silently destroyed. Object syntax is
 * applied per-property and leaves the static styles alone.
 *
 * This is how both progress bars broke: the fill kept its width binding and lost height:100% and
 * background, so it tracked progress perfectly while being invisible. Nothing errors, nothing logs,
 * and the percentage text beside it keeps counting up — so the bar looks merely unstyled rather
 * than broken.
 */
class AlpineStyleBindingTest extends TestCase
{
    /** @return list<string> every Blade file under resources/views */
    private function bladeFiles(): array
    {
        $views = resource_path('views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($views));

        $found = [];

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }

    public function test_no_element_combines_a_static_style_with_a_string_style_binding(): void
    {
        $offenders = [];
        $views = resource_path('views');

        foreach ($this->bladeFiles() as $path) {
            $source = file_get_contents($path);

            // Whole opening tags, so a binding still counts when the attributes are wrapped
            // across several lines. Quoted values are matched as a unit rather than excluded
            // character by character, because an expression like `secondsLeft < 60` puts a bare
            // "<" inside an attribute and would otherwise cut the tag short.
            preg_match_all('/<[a-zA-Z][a-zA-Z0-9:-]*(?:"[^"]*"|\'[^\']*\'|[^"\'>])*>/s', $source, $tags);

            foreach ($tags[0] as $tag) {
                if (! preg_match('/(?<![:\w-])style\s*=\s*"/', $tag)) {
                    continue; // no static style to clobber
                }

                if (! preg_match('/:style\s*=\s*"([^"]*)"/', $tag, $m)) {
                    continue;
                }

                // What Alpine does depends on the value at runtime, so this approximates it: an
                // expression yielding an object has to contain a brace somewhere. "${" is dropped
                // first, otherwise a template literal building a CSS string would read as an object.
                // A ternary picking between two objects passes, which is the point — the test is
                // about the value's shape, not where the braces sit in the expression.
                if (str_contains(str_replace('${', '', $m[1]), '{')) {
                    continue;
                }

                $line = substr_count(substr($source, 0, strpos($source, $tag)), "\n") + 1;
                $offenders[] = str_replace($views.DIRECTORY_SEPARATOR, '', $path).':'.$line;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'these elements bind :style as a string, which wipes the static style attribute — use :style="{ ... }"',
        );
    }
}
