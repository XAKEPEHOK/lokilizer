<?php

namespace XAKEPEHOK\Lokilizer\Services;

use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use DiBify\DiBify\Id\UuidGenerator;
use Exception;
use Redis;
use RedisException;
use XAKEPEHOK\Lokilizer\Models\User\Db\UserRepo;
use XAKEPEHOK\Lokilizer\Models\User\User;

readonly class TokenService
{

    public function __construct(
        private Redis $redis,
        private UserRepo $userRepo,
    )
    {
    }

    public function getCookieToken(User $user): SetCookie
    {
        $token = UuidGenerator::generate();
        $key = "token:cookie:{$token}";
        $this->redis->hMSet($key, [
            'id' => $user->id()->get(),
            'resetAt' => $user->getAuthResetAt()->getTimestamp(),
        ]);

        $durationInDays = $_ENV['PROJECT_LOGIN_DAYS'];

        $this->redis->expire($key, 60 * 60 * 24 * $durationInDays);

        return SetCookie::create('uuid', $token)
            ->withDomain($_ENV['PROJECT_DOMAIN'])
            ->withHttpOnly()
            ->withSecure()
            ->withSameSite(SameSite::strict())
            ->withExpires(time() + 60 * 60 * 24 * $durationInDays)
            ->withPath("/");
    }

    /**
     * @param string $token
     * @return User|null
     * @throws Exception
     */
    public function parseCookieToken(string $token): ?User
    {
        $key = "token:cookie:{$token}";
        $data = $this->redis->hGetAll($key);
        if (empty($data)) {
            return null;
        }
        $data = array_merge(['resetAt' => 0], $data);

        /** @var User|null $user */
        $user = $this->userRepo->findById($data['id']);
        if (!$user) {
            return null;
        }

        if ($user->getAuthResetAt()->getTimestamp() > $data['resetAt']) {
            return null;
        }

        return $user;
    }

}