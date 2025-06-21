<?php

namespace XAKEPEHOK\Lokilizer\Apps\Portal\Middleware;

use League\Plates\Engine;
use XAKEPEHOK\Lokilizer\Components\Current;
use XAKEPEHOK\Lokilizer\Models\User\User;
use XAKEPEHOK\Lokilizer\Services\TokenService;
use Dflydev\FigCookies\Cookies;
use DiBify\DiBify\Manager\ModelManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Http\ServerRequest;

class AuthMiddleware extends RenderMiddleware
{

    public function __construct(
        private readonly TokenService $tokenService,
        private readonly ModelManager $modelManager,
        Engine                        $engine,
    )
    {
        parent::__construct($engine);
    }

    public function __invoke(ServerRequest $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uuid = Cookies::fromRequest($request)->get('uuid')?->getValue() ?? '';
        $user = $this->tokenService->parseCookieToken($uuid);
        $exception403 = new HttpException($request, 'Not authorized', 403);

        if ($user === null) {
            return $this->render($request, 'home/home_index', []);
        }

        date_default_timezone_set($user->getTimezone()->getName());

        /** @var User $user */
        $user = $this->modelManager->refreshOne($user);

        Current::setUser($user);

        return $handler->handle($request->withAttribute('user', $user));
    }

}