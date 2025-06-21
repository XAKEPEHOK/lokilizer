<?php
/**
 * Created for lv-app
 * Date: 03.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\User;


use DateTimeImmutable;
use DateTimeZone;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;
use PhpDto\EmailAddress\EmailAddress;
use XAKEPEHOK\Lokilizer\Models\User\Components\HumanName\HumanName;
use XAKEPEHOK\Lokilizer\Models\User\Components\Password\Password;
use XAKEPEHOK\Lokilizer\Models\User\Components\Theme;

class User implements ModelInterface
{

    protected Id $id;

    protected DateTimeImmutable $registeredAt;

    protected DateTimeImmutable $passwordChangedAt;

    protected ?DateTimeImmutable $lastVisitedAt;

    protected HumanName $name;

    protected EmailAddress $email;

    protected Password $password;

    protected DateTimeZone $timezone;

    protected Theme $theme;

    protected ?UserTOTP $TOTP = null;

    protected bool $banned = false;

    /**
     * Данное поле необходимо для возможности "отзыва" всех существующих JWT-токенов, а также при смене пароля
     * @var DateTimeImmutable
     */
    protected DateTimeImmutable $authResetAt;

    /**
     * User constructor.
     * @param HumanName $name
     * @param EmailAddress $email
     * @param Password $password
     */
    public function __construct(HumanName $name, EmailAddress $email, Password $password)
    {
        $this->id = new Id();
        $this->registeredAt = new DateTimeImmutable();
        $this->passwordChangedAt = new DateTimeImmutable();
        $this->lastVisitedAt = null;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->timezone = new DateTimeZone('UTC');
        $this->authResetAt = new DateTimeImmutable();
        $this->theme = Theme::Dark;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getRegisteredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function getLastVisitedAt(): ?DateTimeImmutable
    {
        return $this->lastVisitedAt;
    }

    public function getPasswordChangedAt(): DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setLastVisitNow(): void
    {
        $this->lastVisitedAt = new DateTimeImmutable();
    }

    public function getName(): HumanName
    {
        return $this->name;
    }

    public function setName(HumanName $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function setEmail(EmailAddress $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): Password
    {
        return $this->password;
    }

    public function setPassword(Password $password): void
    {
        $this->password = $password;
        $this->passwordChangedAt = new DateTimeImmutable();
        $this->resetAuth();
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function setTimezone(DateTimeZone $timezone): void
    {
        $this->timezone = $timezone;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function setTheme(Theme $theme): void
    {
        $this->theme = $theme;
    }

    public function getTOTP(): ?UserTOTP
    {
        return $this->TOTP;
    }

    public function setTOTP(?UserTOTP $TOTP): void
    {
        $this->TOTP = $TOTP;
    }

    public function getAuthResetAt(): DateTimeImmutable
    {
        return $this->authResetAt;
    }

    public function resetAuth(): void
    {
        $this->authResetAt = new DateTimeImmutable();
    }

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): void
    {
        $this->banned = $banned;
    }

    public static function getModelAlias(): string
    {
        return 'user';
    }
}