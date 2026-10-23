<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class TrustedProductDescription
{
    private const ALLOWED_TAGS = ['br', 'h2', 'h3', 'li', 'p', 'strong', 'ul'];

    public function validate(string $html): string
    {
        if (str_contains($html, '<!--')) {
            $this->invalid();
        }

        preg_match_all('/<\/?([a-z0-9]+)\b([^>]*)>/i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            $attributes = trim($match[2]);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->invalid();
            }

            if (str_starts_with($match[0], '</') || $attributes === '') {
                continue;
            }

            if ($attributes !== 'style="text-align: center;"') {
                $this->invalid();
            }
        }

        return $html;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'description' => 'The description contains unsupported HTML.',
        ]);
    }
}
