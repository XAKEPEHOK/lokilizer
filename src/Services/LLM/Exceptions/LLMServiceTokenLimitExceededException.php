<?php

namespace XAKEPEHOK\Lokilizer\Services\LLM\Exceptions;

use Exception;
use XAKEPEHOK\Lokilizer\Components\PublicExceptionInterface;

class LLMServiceTokenLimitExceededException extends Exception implements PublicExceptionInterface
{

}