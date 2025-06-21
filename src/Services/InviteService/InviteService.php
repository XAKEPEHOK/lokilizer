<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 17:21
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services\InviteService;

use PrinsFrank\Standards\Language\LanguageAlpha2;
use Redis;
use Throwable;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Role;
use XAKEPEHOK\Lokilizer\Services\InviteService\Components\Invite;

class InviteService
{

    public function __construct(
        private Redis $redis,
    )
    {
    }

    public function generate(Role $role, LanguageAlpha2 ...$languages): Invite
    {
        $projectId = Current::getProject()->id()->get();
        $invite = Invite::create(time() + 60 * 60, $role, ...$languages);
        $key = "project:{$projectId}:invite:{$invite->id}";
        $this->redis->setex($key, $invite->ttl(), json_encode($invite));
        return $invite;
    }

    public function revoke(Invite|string $inviteOrId): void
    {
        $inviteId = is_string($inviteOrId) ? $inviteOrId : $inviteOrId->id;
        $projectId = Current::getProject()->id()->get();
        $key = "project:{$projectId}:invite:{$inviteId}";
        $this->redis->del($key);
    }

    public function getInviteById(string $inviteId): ?Invite
    {
        $projectId = Current::getProject()->id()->get();
        $key = "project:{$projectId}:invite:{$inviteId}";
        $data = $this->redis->get($key);
        if (empty($data)) {
            return null;
        }

        try {
            return Invite::fromJson($data);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return Invite[]
     */
    public function getInvites(): array
    {
        $projectId = Current::getProject()->id()->get();
        $pattern = "project:{$projectId}:invite:*";
        $iterator = null;
        $keys = [];

        while (($keysPart = $this->redis->scan($iterator, $pattern)) !== false) {
            if (!empty($keysPart)) {
                $keys = array_merge($keys, $keysPart);
            }

            if ($iterator === 0) {
                break;
            }
        }

        $invites = array_map(
            function (string $key) {
                try {
                    return Invite::fromJson($this->redis->get($key));
                } catch (Throwable) {
                    return null;
                }
            },
            $keys
        );

        $invites = array_filter($invites);
        usort($invites, function (Invite $a, Invite $b) {
            $direction = $b->role->value <=> $a->role->value;
            if ($direction !== 0) {
                return $direction;
            }
            return $b->expire <=> $a->expire;
        });
        return $invites;
    }

}