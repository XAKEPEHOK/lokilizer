<?php

namespace XAKEPEHOK\Lokilizer\Components;

use DiBify\DiBify\Helpers\IdHelper;
use DiBify\DiBify\Helpers\ModelHelper;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;
use Yiisoft\Arrays\ArrayHelper;

class Current
{

    private static Project $project;

    private static User $user;

    private static array $llmEndpoints = [];

    public static function getProject(): Project
    {
        return self::$project;
    }

    public static function setProject(Project $project): void
    {
        self::$project = $project;
    }

    public static function getUser(): User
    {
        return self::$user;
    }

    public static function setUser(User $user): void
    {
        self::$user = $user;
    }

    public static function hasUser(): bool
    {
        return isset(self::$user);
    }

    /**
     * @return LLMEndpoint[]
     */
    public static function getLLMEndpoints(): array
    {
        return self::$llmEndpoints;
    }

    public static function setLLMEndpoints(LLMEndpoint ...$endpoints): void
    {
        self::$llmEndpoints = ModelHelper::indexById(...$endpoints);
    }

    public static function can(?Permission $permission, ?LanguageAlpha2 $language = null): bool
    {
        $userRole = Current::$project->getUserRole(Current::$user);
        return $userRole->can($permission, $language) ?? false;
    }

    public static function guard(?Permission $permission, ?LanguageAlpha2 $language = null): void
    {
        $userRole = Current::$project->getUserRole(Current::$user);
        $userRole->guard($permission, $language);
    }


}