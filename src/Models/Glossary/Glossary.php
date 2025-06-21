<?php
/**
 * Created for lokilizer
 * Date: 2025-02-07 17:40
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Pool\FloatPool;
use JsonSerializable;
use PrinsFrank\Standards\Language\LanguageAlpha2;

abstract class Glossary implements ModelInterface, JsonSerializable
{

    protected Id $id;

    protected string $summary;

    /** @var GlossaryItem[]  */
    protected array $items;

    protected FloatPool $llmCost;

    public function __construct(
        string $summary = '',
        GlossaryItem ...$items
    )
    {
        $this->id = new Id();
        $this->summary = $summary;
        $this->setItems(...$items);
        $this->llmCost = new FloatPool(0, 0);
    }

    public function getSummary(): ?string
    {
        if (empty($this->summary)) {
            return null;
        }
        return $this->summary;
    }

    public function setSummary(string $summary): void
    {
        $this->summary = trim($summary);
    }

    public function getItemByPrimaryPhrase(string $phrase): ?GlossaryItem
    {
        foreach ($this->items as $item) {
            if ($item->primary->phrase === $phrase) {
                return $item;
            }
        }
        return null;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(GlossaryItem ...$items): void
    {
        $this->items = array_values(array_filter(
            $items,
            fn(GlossaryItem $item) => !empty($item->primary->phrase)
        ));
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getLanguages(): array
    {
        $languages = [];
        foreach ($this->items as $item) {
            $languages = array_merge($languages, $item->getLanguages());
        }
        return array_unique($languages, SORT_REGULAR);
    }

    public function isComplete(): bool
    {
        $hasSummary = $this->getSummary() !== null;
        $hasGlossaryItems = false;
        foreach ($this->items as $item) {
            $hasGlossaryItems = $hasGlossaryItems || $item->isComplete();
        }
        return $hasGlossaryItems || $hasSummary;
    }

    public function LLMCost(): FloatPool
    {
        return $this->llmCost;
    }

    public function jsonSerialize(): ?array
    {
        $result = [
            'summary' => $this->getSummary(),
            'glossary' => array_filter($this->getItems()),
        ];
        return array_filter($result);
    }

    public static function getModelAlias(): string
    {
        return 'glossary';
    }
}