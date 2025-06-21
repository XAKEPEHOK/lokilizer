<?php
/**
 * Created for lokilizer
 * Date: 2025-02-06 22:47
 * @author: Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\Lokilizer\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TransformerService
{

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://transformer:8000/',
        ]);
    }

    /**
     * @param array<string, string> $strings
     * @param float $eps
     * @param int $minSamples
     * @param int|null $maxSamples
     * @return array
     * @throws GuzzleException
     */
    public function clustering(array $strings, float $eps = 0.2, int $minSamples = 2, ?int $maxSamples = null): array
    {
        $query = http_build_query(array_filter([
            'eps' => $eps,
            'min_samples' => $minSamples,
            'max_samples' => $maxSamples,
        ], fn(mixed $value) => !is_null($value)));


        $response = $this->client->post("/cluster?$query", [
            'json' => $strings,
        ]);

        return array_values(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * @param array<int, array{0: string, 1: string}> $pairs
     * @return float[]
     * @throws GuzzleException
     */
    public function compare(array $pairs): array
    {
        $response = $this->client->post("/compare", [
            'json' => array_values($pairs),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string[] $strings
     * @return array<string, array>
     * @throws GuzzleException
     */
    public function vectorize(array $strings): array
    {
        $response = $this->client->post("/vectorize", [
            'json' => array_values($strings),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array<string, array> $vectors
     * @param float $eps
     * @param int $minSamples
     * @param int|null $maxSamples
     * @return array
     * @throws GuzzleException
     */
    public function dbscan(array $vectors, float $eps = 0.2, int $minSamples = 2, ?int $maxSamples = null): array
    {
        $query = http_build_query(array_filter([
            'eps' => $eps,
            'min_samples' => $minSamples,
            'max_samples' => $maxSamples,
        ], fn(mixed $value) => !is_null($value)));


        $response = $this->client->post("/dbscan?$query", [
            'json' => $vectors,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

}