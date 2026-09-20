<?php

namespace Tests\Feature;

use App\Services\TrustedProductDescription;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrustedProductDescriptionTest extends TestCase
{
    public function test_it_preserves_the_exact_live_description_markup(): void
    {
        $html = '<h2 style="text-align: center;"><strong>Description — Top Choice ✨</strong></h2><p></p>';

        $this->assertSame($html, app(TrustedProductDescription::class)->validate($html));
    }

    public function test_it_rejects_scripts_and_unapproved_attributes(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<p onclick="alert(1)">Description</p>',
            '<img src=x onerror="alert(1)">',
        ] as $html) {
            try {
                app(TrustedProductDescription::class)->validate($html);
                $this->fail('Unsafe description markup was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('description', $exception->errors());
            }
        }
    }
}
