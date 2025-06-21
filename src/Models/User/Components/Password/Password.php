<?php
/**
 * Created for lv-app
 * Date: 03.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\User\Components\Password;


class Password
{

    /** @var string */
    private $hash;

    /**
     * Password constructor.
     * @param string $password
     * @throws PasswordException
     */
    public function __construct(string $password)
    {
        if (strlen($password) < 8) {
            throw new PasswordException('Password length should be great than 8 chars');
        }
        $this->hash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function verify(string $password): bool
    {
        return password_verify($password, $this->hash);
    }

}