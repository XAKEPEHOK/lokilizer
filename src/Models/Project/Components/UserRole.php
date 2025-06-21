<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 00:51
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components;

use DiBify\DiBify\Model\Reference;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use Stringable;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\PermissionException;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;
use XAKEPEHOK\Lokilizer\Models\User\User;

readonly class UserRole implements Stringable
{

    public Reference $user;
    public Role $role;
    public array $languages;

    public function __construct(
        User $user,
        Role $role,
        LanguageAlpha2 ...$languages
    )
    {
        $this->user = Reference::to($user);
        $this->role = $role;
        $this->languages = $languages;
    }

    public function getUser(): User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->user->getModel();
    }

    public function can(?Permission $permission, ?LanguageAlpha2 $language = null): bool
    {
        if ($language) {
            if (!in_array($language, $this->languages) && !$this->role->can(Permission::MANAGE_LANGUAGES)) {
                return false;
            }

            if ($permission === null) {
                return true;
            }
        }

        return $this->role->can($permission);
    }

    /**
     * @param Permission|null $permission
     * @param LanguageAlpha2|null $language
     * @return void
     * @throws PermissionException
     */
    public function guard(?Permission $permission, ?LanguageAlpha2 $language = null): void
    {
        if ($language) {
            if (!in_array($language, $this->languages) && !$this->role->can(Permission::MANAGE_LANGUAGES)) {
                throw new PermissionException("No permission for language: {$language->name}");
            }

            if ($permission === null) {
                return;
            }
        }

        $this->role->guard($permission);
    }

    public function __toString(): string
    {
        return $this->user->id()->get();
    }
}