<?php
/**
 * Created for sr-app
 * Date: 2025-01-13 23:06
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Localization;

use DateTimeImmutable;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelBeforeCommitEventInterface;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Pool\FloatPool;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Localization\Components\AbstractValue;

abstract class Record implements ModelInterface, ModelBeforeCommitEventInterface
{

    protected Id $id;

    protected Reference $project;
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;
    protected DateTimeImmutable $touchedAt;

    protected int $level;

    protected string $parent;

    protected string $key;

    /** @var AbstractValue[]  */
    protected array $values = [];
    protected string $comment = '';
    protected ?DateTimeImmutable $outdatedAt = null;

    protected int $position = 0;
    protected FloatPool $llmCost;

    public function __construct(
        string         $flatKey,
        AbstractValue  ...$values,
    )
    {
        $this->id = new Id(md5($flatKey));
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->touchedAt = new DateTimeImmutable();

        $pathArray = explode('.', $flatKey);
        $this->level = count($pathArray) - 1;
        $this->key = $flatKey;
        foreach ($values as $value) {
            $this->setValue($value);
        }
        array_pop($pathArray);
        $this->parent = implode('.', $pathArray);
        $this->llmCost = new FloatPool(0, 0);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getTouchedAt(): DateTimeImmutable
    {
        return $this->touchedAt;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return AbstractValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getPrimaryValue(): AbstractValue
    {
        $primary = Current::getProject()->getPrimaryLanguage();
        return $this->getValue($primary);
    }

    public function getSecondaryValue(): ?AbstractValue
    {
        $secondary = Current::getProject()->getSecondaryLanguage();
        if (!$secondary) {
            return null;
        }
        return $this->getValue($secondary);
    }

    /**
     * @param LanguageAlpha2 $language
     * @return AbstractValue|null
     */
    public function getValue(LanguageAlpha2 $language): ?AbstractValue
    {
        foreach ($this->values as $value) {
            if ($value->getLanguage() === $language) {
                return $value;
            }
        }

        return null;
    }

    public function setValue(AbstractValue $value): bool
    {
        $current = $this->getValue($value->getLanguage());
        $value->setWarnings($value->validate($this));

        if ($current && $current->isEquals($value)) {
            $current->setWarnings($current->validate($this));
            return false;
        }

        $exists = false;
        foreach ($this->values as $index => $existedValue) {
            if ($current === $existedValue) {
                $this->values[$index] = $value;
                if ($current->getWarnings() !== $value->getWarnings()) {
                    $value->verified = false;
                }
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->values[] = $value;
            if ($value->getWarnings() > 0) {
                $value->verified = false;
            }
        }

        $project = Current::getProject();
        $map = [
            LanguageAlpha2::English->value => -10,
            ($project->getSecondaryLanguage() ?? $project->getPrimaryLanguage())->value => -500,
            $project->getPrimaryLanguage()->value => -1000,
        ];

        usort($this->values, function (AbstractValue $a, AbstractValue $b) use ($map) {
            $scoreA = $map[$a->getLanguage()->value] ?? $a->getLanguage()->value;
            $scoreB = $map[$b->getLanguage()->value] ?? $b->getLanguage()->value;
            return strcmp($scoreA, $scoreB);
        });

        $this->updatedAt = new DateTimeImmutable();
        $this->outdatedAt = null;

        //Если это не главный язык, то на этом заканчиваем
        if ($value !== $this->getPrimaryValue()) {
            return true;
        }

        //Если это главный язык и в нем есть изменения, то мы должны убрать верификацию второстепенных языков
        foreach ($this->values as $existedValue) {
            if ($value === $existedValue) {
                continue;
            }

            $warnings = $existedValue->validate($this);
            $existedValue->setWarnings($warnings);
            $existedValue->verified = false;
        }

        $this->touchedAt = new DateTimeImmutable();

        return true;
    }

    public function hasValue(LanguageAlpha2 $language): bool
    {
        foreach ($this->values as $value) {
            if ($value->getLanguage() === $language) {
                return true;
            }
        }
        return false;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): bool
    {
        if ($comment !== $this->comment) {
            $this->comment = $comment;
            $this->updatedAt = new DateTimeImmutable();
            return true;
        }
        return false;
    }

    public function isOutdated(): bool
    {
        return $this->outdatedAt !== null;
    }

    public function getOutdatedAt(): ?DateTimeImmutable
    {
        return $this->outdatedAt;
    }

    public function setOutdated(bool $isOutdated): void
    {
        if ($this->outdatedAt !== null && $isOutdated) {
            return;
        }

        if ($isOutdated) {
            $this->outdatedAt = new DateTimeImmutable();
        } else {
            $this->outdatedAt = null;
        }
    }

    public function LLMCost(): FloatPool
    {
        return $this->llmCost;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): bool
    {
        if ($position === $this->position) {
            return false;
        }
        $this->position = $position;
        return true;
    }

    public function onBeforeCommit(): void
    {
        foreach ($this->values as $value) {
            if ($value->isEquals($value->getSuggested())) {
                $value->setSuggested(null);
            }
        }
    }

    public static function getModelAlias(): string
    {
        return 'record';
    }
}