<?php
/**
 * Created for lokilizer
 * Date: 2025-02-07 17:40
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use Stringable;

readonly class GlossaryPhrase implements Stringable
{

    public LanguageAlpha2 $language;
    public string $phrase;

    public function __construct(
        LanguageAlpha2 $language,
        string $phrase
    )
    {
        $this->language = $language;
        $this->phrase = trim($phrase);
    }

    public function __toString(): string
    {
        return $this->phrase;
    }
}