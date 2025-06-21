<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 17:06
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services\InviteService\Components;

use DateTimeImmutable;
use DiBify\DiBify\Id\UuidGenerator;
use JsonSerializable;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;

readonly class Invite implements JsonSerializable
{

    public string $id;
    public int $expire;
    public Role $role;
    public array $languages;

    private function __construct(
        int $timeout,
        Role $role,
        string $id,
        LanguageAlpha2 ...$languages,
    )
    {
        $this->id = $id;
        $this->role = $role;
        $this->languages = $languages;
        $this->expire = $timeout;
    }

    public function isValid(): bool
    {
        return $this->expire > time();
    }

    public function ttl(): int
    {
        return max(0, $this->expire - time());
    }

    public function expireAt(): DateTimeImmutable
    {
        return new DateTimeImmutable("@{$this->expire}");
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'expire' => $this->expire,
            'role' => $this->role,
            'languages' => $this->languages,
        ];
    }

    public static function create(int $timeout, Role $role, LanguageAlpha2 ...$languages): self
    {
        return new self($timeout, $role, UuidGenerator::generate(), ...$languages);
    }

    public static function fromJson(string $json): Invite
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        return new self(
            $data['expire'],
            Role::from($data['role']),
            $data['id'],
            ...array_map(
                fn(string $lang) => LanguageAlpha2::from($lang),
                $data['languages']
            ),
        );
    }
}