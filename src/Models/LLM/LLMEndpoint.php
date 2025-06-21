<?php

namespace XAKEPEHOK\Lokilizer\Models\LLM;

use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Pool\FloatPool;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use OpenAI;
use XAKEPEHOK\Lokilizer\Services\LLM\Models\LLMResponse;

class LLMEndpoint implements ModelInterface
{

    protected Id $id;
    protected string $name;
    protected string $uri;
    protected string $token;
    protected string $model;
    protected LLMPricing $pricing;
    protected int $timeout;
    protected string $proxy = '';

    protected FloatPool $cost;

    private OpenAI\Client $client;

    public function __construct(
        string $name,
        string $uri,
        string $token,
        string $model,
        LLMPricing $pricing,
        int $timeout = 120,
    )
    {
        $this->id = new Id();
        $this->name = $name;
        $this->uri = $uri;
        $this->token = $token;
        $this->model = $model;
        $this->pricing = $pricing;
        $this->timeout = $timeout;
        $this->cost = new FloatPool(0);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getPricing(): LLMPricing
    {
        return $this->pricing;
    }

    public function setPricing(LLMPricing $pricing): void
    {
        $this->pricing = $pricing;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = max(min($timeout, 60 * 60 * 24), 1);
    }

    public function getProxy(): string
    {
        return $this->proxy;
    }

    public function setProxy(string $proxy): void
    {
        $this->proxy = $proxy;
    }

    public function getCost(): FloatPool
    {
        return $this->cost;
    }

    public function query(string $prompt, string $text, string|array|null $format = null): LLMResponse
    {
        $responseFormat = [];
        $response = $this->getClient()->chat()->create([
            'model' => $this->model,
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
        ]);

        return new LLMResponse(
            text: $response->choices[0]->message->content,
            pricing: $this->pricing,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
        );
    }

    private function getClient(): OpenAI\Client
    {
        if (!isset($this->client)) {
            $guzzleOptions = [
                RequestOptions::SYNCHRONOUS => true,
                RequestOptions::CONNECT_TIMEOUT => min(5, $this->timeout),
                RequestOptions::TIMEOUT => $this->timeout,
            ];

            if (!empty($this->proxy)) {
                $guzzleOptions[RequestOptions::PROXY] = $this->proxy;
            }

            $this->client = OpenAI::factory()
                ->withBaseUri($this->uri)
                ->withApiKey($this->token)
                ->withOrganization(null)
                ->withHttpClient(new Client($guzzleOptions))
                ->make();
        }

        return $this->client;
    }

    public static function getModelAlias(): string
    {
        return 'LLM';
    }
}