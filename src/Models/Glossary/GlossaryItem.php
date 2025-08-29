<?php
/**
 * Created for lokilizer
 * Date: 2025-02-07 18:27
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary;

use JsonSerializable;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Stringable;
use Yiisoft\Arrays\ArrayHelper;

class GlossaryItem implements Stringable, JsonSerializable
{

    public float $similarity = 0;

    public readonly GlossaryPhrase $primary;
    public readonly string $description;

    /** @var GlossaryPhrase[] */
    protected array $translations;

    public function __construct(
        GlossaryPhrase $primary,
        string         $description,
        GlossaryPhrase ...$translations,
    )
    {
        $this->primary = $primary;
        $this->description = trim($description);
        $this->translations = array_values(ArrayHelper::index(
            array_filter(
                $translations,
                fn(GlossaryPhrase $translation) => $translation->language !== $primary->language && !empty($translation->phrase),
            ),
            fn (GlossaryPhrase $phrase) => $phrase->language->value
        ));
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function addTranslation(GlossaryPhrase $phrase): void
    {
        if (empty($phrase->phrase)) {
            return;
        }

        $this->translations[$phrase->language->value] = $phrase;
    }

    public function isComplete(?LanguageAlpha2 $language = null): bool
    {
        $phrase = $this->getByLanguage($language ?? $this->primary->language) ?? $this->primary;
        return !empty($phrase) && !empty($this->description);
    }

    public function getByLanguage(LanguageAlpha2 $language): ?GlossaryPhrase
    {
        if ($this->primary->language === $language) {
            return $this->primary;
        }

        foreach ($this->translations as $translation) {
            if ($translation->language === $language) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @return LanguageAlpha2[]
     */
    public function getLanguages(): array
    {
        return [
            $this->primary->language,
            ...array_map(
                fn(GlossaryPhrase $phrase) => $phrase->language,
                $this->translations
            ),
        ];
    }

    public function toString(LanguageAlpha2 ...$languages): string
    {
        if (!$this->isComplete()) {
            return '';
        }

        $languages = array_unique(array_filter($languages), SORT_REGULAR);
        if (empty($languages)) {
            $languages = $this->getLanguages();
        }

        $translations = [];
        foreach ($this->translations as $translation) {
            if (mb_strlen($translation->phrase) === 0) {
                continue;
            }

            if (!in_array($translation->language, $languages)) {
                continue;
            }

            $translations[strtoupper($translation->language->value)] = $translation->phrase;
        }

        $translationsString = '';
        if (!empty($translations)) {
            $translationsString = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $translationsString = ' (' . mb_substr($translationsString, 1, -1) . ')';
        }

        return $this->primary . $translationsString . ' - ' . $this->description;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): ?array
    {
        if (!$this->isComplete()) {
            return null;
        }

        $translations = [
            $this->primary->language->value => $this->primary->phrase,
        ];

        foreach ($this->translations as $translation) {
            $translations[$translation->language->value] = $translation->phrase;
        }

        return [
            'translations' => $translations,
            'description' => $this->description,
        ];
    }
}