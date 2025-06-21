<?php
/**
 * Created for lokilizer
 * Date: 2025-02-14 21:50
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Glossary;

use XAKEPEHOK\Lokilizer\Models\Exceptions\GlossaryKeyPrefixException;

class SpecialGlossary extends Glossary
{

    protected string $keyPrefix;

    public function __construct(
        string $keyPrefix,
        string $summary = '',
        GlossaryItem ...$items
    )
    {
        $this->setKeyPrefix($keyPrefix);
        parent::__construct($summary, ...$items);
    }

    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    public function setKeyPrefix(string $keyPrefix): void
    {
        $keyPrefix = trim($keyPrefix);
        if (strlen($keyPrefix) == 0) {
            throw new GlossaryKeyPrefixException('Glossary key prefix cannot be empty');
        }
        $this->keyPrefix = trim($keyPrefix);
    }

    public function isForKey(string $key): bool
    {
        return str_starts_with($key, $this->getKeyPrefix());
    }

}