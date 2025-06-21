<?php

namespace XAKEPEHOK\Lokilizer\Services\LLM;

use DiBify\DiBify\Manager\ModelManager;
use DiBify\DiBify\Manager\Transaction;
use XAKEPEHOK\Lokilizer\Models\LLM\LLMEndpoint;
use XAKEPEHOK\Lokilizer\Services\LLM\Models\LLMResponse;
use Throwable;
use function Sentry\captureException;

readonly class LLMService
{
    public function __construct(
        private ModelManager $modelManager,
    )
    {
    }

    public function query(string $prompt, string $text, LLMEndpoint $model, string|array|null $format = null, int $failAttempts = 5): LLMResponse
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = $model->query($prompt, $text, $format);
                $this->calcPrice($response, $model);
                return $response;
            } catch (Throwable $e) {
                if ($attempt >= $failAttempts) {
                    throw $e;
                }
                sleep(1);
            }
        }
    }

    private function calcPrice(LLMResponse $response, LLMEndpoint $model): void
    {
        try {
            $model->getCost()->add($response->calcPrice());
            $this->modelManager->commit(new Transaction([$model]));
        } catch (Throwable $e) {
            captureException($e);
        }
    }
}