<?php

namespace App\Http;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PhoenixUsersClient implements PhoenixUsersClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
    ) {}

    public function list(array $query = []): array
    {
        return $this->request('GET', '/users', ['query' => $query]);
    }

    public function get(int $id): array
    {
        return $this->request('GET', "/users/{$id}");
    }

    public function create(array $payload): array
    {
        return $this->request('POST', '/users', ['json' => $payload]);
    }

    public function update(int $id, array $payload): array
    {
        return $this->request('PUT', "/users/{$id}", ['json' => $payload]);
    }

    public function delete(int $id): array
    {
        return $this->request('DELETE', "/users/{$id}");
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $options['headers']['Accept'] = 'application/json';

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $status = $response->getStatusCode();

            if ($status === 204) {
                return ['_status' => 204];
            }

            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? '';

            $raw = $response->getContent(false);

            if (trim($raw) === '') {
                return $status >= 400
                    ? ['_status' => $status, '_error' => 'Empty response body from API']
                    : ['_status' => $status];
            }

            if (!str_contains($contentType, 'application/json')) {
                return [
                    '_status' => $status,
                    '_error' => 'Non-JSON response from API',
                    '_content_type' => $contentType,
                    '_raw' => $raw,
                ];
            }

            try {
                $data = $response->toArray(false);
            } catch (JsonException $e) {
                return [
                    '_status' => $status,
                    '_error' => 'Invalid JSON returned by API',
                    '_content_type' => $contentType,
                    '_raw' => $raw,
                ];
            }

            if ($status >= 400) {
                return ['_status' => $status] + (is_array($data) ? $data : ['_error' => $data]);
            }

            return is_array($data) ? $data : ['data' => $data];

        } catch (TransportExceptionInterface $e) {
            return ['_status' => 0, '_error' => 'Transport error: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            return ['_status' => 0, '_error' => 'Client error: ' . $e->getMessage()];
        }
    }
}
