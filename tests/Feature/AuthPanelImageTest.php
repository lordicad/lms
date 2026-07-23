<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The login panel's brand artwork points at a real file.
 *
 * A CSS url() to a missing image fails silently — the login page still returns 200 and just shows
 * the fallback green — so the only way to catch a moved or renamed file is to assert the page names
 * it and the file is on disk. The panel is shared, so the login page stands in for register and
 * first-password too.
 */
class AuthPanelImageTest extends TestCase
{
    public function test_the_login_panel_references_the_brand_image_and_it_exists(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('images/AuthPic.png');

        $this->assertFileExists(public_path('images/AuthPic.png'));
    }
}
