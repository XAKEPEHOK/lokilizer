<?php
/**
 * Created for lokilizer
 * Date: 12.08.2024 01:29
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Apps\Http\Components;

use League\Plates\Engine;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;
use XAKEPEHOK\Lokilizer\Models\Project\Components\Role\PermissionException;
use function Sentry\captureException;

class ErrorHandler implements ErrorHandlerInterface
{

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Engine $renderer,
    )
    {
    }

    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        if ($logErrors) {
            captureException($exception);
        }

        if ($exception instanceof PermissionException) {
            return $response->write($this->renderer->render('errors/403', [
                'request' => $request,
                'exception' => $exception,
            ]));
        }

        return $response->write($this->renderer->render('errors/slim', [
            'request' => $request,
            'exception' => $exception,
            'displayErrorDetails' => $displayErrorDetails,
        ]));
    }
}