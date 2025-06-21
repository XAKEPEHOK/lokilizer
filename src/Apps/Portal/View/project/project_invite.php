<?php

use XAKEPEHOK\Lokilizer\Apps\Portal\Components\RouteUri;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\Permission;
use XAKEPEHOK\Lokilizer\Models\Project\Project;
use XAKEPEHOK\Lokilizer\Models\User\User;
use League\Plates\Template\Template;
use Slim\Http\ServerRequest;
use XAKEPEHOK\Lokilizer\Services\InviteService\Components\Invite;

/** @var Template $this */
/** @var ServerRequest $request */
/** @var RouteUri $route */
/** @var Project $project */
/** @var Invite $invite */

/** @var User $user */
$user = $request->getAttribute('user');

$this->layout('account_layout', ['request' => $request, 'title' => $project->getName()]) ?>

<div class="row">
    <div class="col mx-auto">
        <form method="post" class="vstack gap-3 mt-3">
            <button type="submit" name="join" value="1" class="btn btn-success btn-lg">
                Join project
            </button>
            <div>
                Expire: <?= $this->insert('widgets/_timeago', ['datetime' => $invite->expireAt()]) ?>
            </div>
        </form>
    </div>
</div>