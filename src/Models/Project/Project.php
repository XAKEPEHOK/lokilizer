<?php

namespace XAKEPEHOK\Lokilizer\Models\Project;

use DiBify\DiBify\Model\ModelBeforeCommitEventInterface;
use DiBify\DiBify\Pool\FloatPool;
use PrinsFrank\Standards\Language\LanguageAlpha2;
use XAKEPEHOK\Lokilizer\Components\Parsers\FileFormatter;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Models\Project\Components\EOLFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\PlaceholderFormat;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;
use XAKEPEHOK\Lokilizer\Models\Project\Components\UserRole;
use XAKEPEHOK\Lokilizer\Models\User\User;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;

class Project implements ModelInterface, ModelBeforeCommitEventInterface
{

    protected Id $id;

    protected string $name;

    /** @var UserRole[] */
    protected array $users = [];

    protected PlaceholderFormat $placeholders = PlaceholderFormat::JS;

    protected EOLFormat $eol = EOLFormat::N;

    protected FileFormatter $fileFormatter = FileFormatter::I18NEXT;

    protected LanguageAlpha2 $primaryLanguage = LanguageAlpha2::English;

    protected ?LanguageAlpha2 $secondaryLanguage = null;

    protected ?Reference $defaultLLM = null;

    protected FloatPool $balance;

    public function __construct(string $name, User $owner)
    {
        $this->id = new Id();
        $this->setUser(new UserRole($owner, Role::Admin));
        $this->setName($name);
        $this->balance = new FloatPool(0);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $name = trim($name);
        if (empty($name)) {
            $name = 'My project';
        }
        $this->name = $name;
    }

    /**
     * @return UserRole[]
     */
    public function getUsers(): array
    {
        Reference::preload(...array_map(
            fn(UserRole $userRole) => $userRole->user,
            $this->users
        ));

        uasort($this->users, function (UserRole $user1, UserRole $user2) {
            return $user2->role->value <=> $user1->role->value;
        });

        return array_values($this->users);
    }

    public function getUserRole(User $user): ?UserRole
    {
        foreach ($this->users as $userRole) {
            if ($userRole->user->isFor($user)) {
                return $userRole;
            }
        }
        return null;
    }

    public function setUser(UserRole $userRole): void
    {
        $this->users = array_filter(
            $this->users,
            fn(UserRole $role) => $role->user !== $userRole->user
        );
        $this->users[] = $userRole;
        $this->users = array_unique($this->users);
    }

    public function hasUser(User $user): bool
    {
        foreach ($this->users as $userRole) {
            if ($userRole->user->isFor($user)) {
                return true;
            }
        }
        return false;
    }

    public function removeUser(User $user): void
    {
        $this->users = array_filter(
            $this->users,
            fn(UserRole $userRole) => !$userRole->user->isFor($user)
        );
    }

    public function getPrimaryLanguage(): LanguageAlpha2
    {
        return $this->primaryLanguage;
    }

    public function setPrimaryLanguage(LanguageAlpha2 $primary): void
    {
        $this->primaryLanguage = $primary;
    }

    public function getSecondaryLanguage(): ?LanguageAlpha2
    {
        return $this->secondaryLanguage;
    }

    public function setSecondaryLanguage(?LanguageAlpha2 $secondary): void
    {
        $this->secondaryLanguage = $secondary;
    }

    public function getPlaceholdersFormat(): PlaceholderFormat
    {
        return $this->placeholders;
    }

    public function setPlaceholders(PlaceholderFormat $placeholders): void
    {
        $this->placeholders = $placeholders;
    }

    public function getEOLFormat(): EOLFormat
    {
        return $this->eol;
    }

    public function setEOLFormat(EOLFormat $eol): void
    {
        $this->eol = $eol;
    }

    public function getFileFormatter(): FileFormatter
    {
        return $this->fileFormatter;
    }

    public function setFileFormatter(FileFormatter $fileFormatter): void
    {
        $this->fileFormatter = $fileFormatter;
    }

    public function getDefaultLLM(): ?Reference
    {
        return $this->defaultLLM;
    }
    public function setDefaultLLM(?LLMEndpoint $defaultLLM): void
    {
        $this->defaultLLM = is_null($defaultLLM) ? null : Reference::to($defaultLLM);
    }

    public function balance(): FloatPool
    {
        return $this->balance;
    }

    public function onBeforeCommit(): void
    {
        if ($this->getPrimaryLanguage() === $this->getSecondaryLanguage()) {
            $this->secondaryLanguage = null;
        }
    }

    public static function getModelAlias(): string
    {
        return 'project';
    }
}