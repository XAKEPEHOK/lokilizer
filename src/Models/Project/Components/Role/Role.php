<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 00:18
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\Project\Components\Role;

enum Role: int
{

    case Admin = 1000;
    case Developer = 900;
    case Translator = 500;

    case Guest = 200;

    public function permissions(): array
    {
        switch ($this) {
            case self::Admin:
                return Permission::cases();
            case self::Developer:
                return array_filter(
                    Permission::cases(),
                    fn (Permission $permission) => !in_array($permission, [Permission::MANAGE_USERS, Permission::MANAGE_LLM]),
                );
            case self::Translator:
                return [
                    Permission::ALERT_MESSAGE,
                    Permission::MANAGE_GLOSSARY,
                    Permission::FILE_DOWNLOADS,
                    Permission::TRANSLATE,
                    Permission::BATCH_MODIFY,
                    Permission::BATCH_AI,
                    Permission::BACKUP_MAKE,
                ];
            case self::Guest:
                return [];
        }

        return [];
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    public function guard(Permission $permission): void
    {
        if (!$this->can($permission)) {
            throw new PermissionException("Permission '{$permission->name}' not allowed.");
        }
    }

}
