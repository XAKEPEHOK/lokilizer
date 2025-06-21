<?php
/**
 * Created for LeadVertex 2.0.
 * Datetime: 19.10.2018 15:56
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace XAKEPEHOK\Lokilizer\Models\User\Components\HumanName;


use JsonSerializable;
use Stringable;

class HumanName implements JsonSerializable, Stringable
{

    private string $firstName = '';

    private string $lastName = '';

    public function __construct(string $firstName, string $lastName = '')
    {
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = trim($firstName);
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = trim($lastName);
    }

    public function __toString(): string
    {
        return implode(' ', [
            $this->firstName,
            $this->lastName,
        ]);
    }

    public function jsonSerialize(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
        ];
    }
}