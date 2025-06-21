<?php
/**
 * Created for lv-app
 * Date: 22.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Models\User\Db;


use DiBify\DiBify\Mappers\EnumMapper;
use DiBify\DiBify\Mappers\ValueObjectMapper;

use PhpDto\EmailAddress\EmailAddress;
use XAKEPEHOK\Lokilizer\Models\User\Components\HumanName\HumanName;
use XAKEPEHOK\Lokilizer\Models\User\Components\Password\Password;
use XAKEPEHOK\Lokilizer\Models\User\Components\Theme;
use XAKEPEHOK\Lokilizer\Models\User\User;
use DateTimeZone;
use DiBify\DiBify\Mappers\ArrayMapper;
use DiBify\DiBify\Mappers\BoolMapper;
use DiBify\DiBify\Mappers\CallableMapper;
use DiBify\DiBify\Mappers\DateTimeMapper;
use DiBify\DiBify\Mappers\IdMapper;
use DiBify\DiBify\Mappers\ModelMapper;
use DiBify\DiBify\Mappers\NullOrMapper;
use DiBify\DiBify\Mappers\ObjectMapper;
use DiBify\DiBify\Mappers\StringMapper;
use OTPHP\Factory;
use OTPHP\TOTPInterface;
use XAKEPEHOK\Lokilizer\Models\User\UserTOTP;

class UserMapper extends ModelMapper
{

    public function __construct()
    {
        parent::__construct(User::class, [
            'id' => new IdMapper(),
            'registeredAt' => DateTimeMapper::getInstanceImmutable(),
            'passwordChangedAt' => DateTimeMapper::getInstanceImmutable(),
            'lastVisitedAt' => new NullOrMapper(DateTimeMapper::getInstanceImmutable()),
            'name' => new ObjectMapper(HumanName::class, [
                'firstName' => StringMapper::getInstance(),
                'lastName' => StringMapper::getInstance(),
            ]),
            'email' => new ValueObjectMapper(EmailAddress::class, 'address', StringMapper::getInstance()),
            'password' => new ValueObjectMapper(Password::class, 'hash', StringMapper::getInstance()),
            'timezone' => new CallableMapper(
                serialize: fn(DateTimeZone $timeZone) => $timeZone->getName(),
                deserialize: fn(string $timezoneName) => new DateTimeZone($timezoneName),
            ),
            'theme' => new EnumMapper(Theme::class, StringMapper::getInstance()),
            'TOTP' => new NullOrMapper(new ObjectMapper(UserTOTP::class, [
                'totp' => new CallableMapper(
                    function (TOTPInterface $TOTP) { return $TOTP->getProvisioningUri(); },
                    function (string $uri) { return Factory::loadFromProvisioningUri($uri); }
                ),
                'recovery' => new ArrayMapper(StringMapper::getInstance()),
            ])),
            'authResetAt' => DateTimeMapper::getInstanceImmutable(),
            'banned' => BoolMapper::getInstance(),
        ]);
    }

    public function deserialize($data)
    {
        if (!isset($data->body['theme'])) {
            $data->body['theme'] = Theme::Dark->value;
        }

        return parent::deserialize($data);
    }

}