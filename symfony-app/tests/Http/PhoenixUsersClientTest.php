<?php

namespace App\Tests\Http;

use App\Http\PhoenixUsersClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PhoenixUsersClientTest extends TestCase
{
    public function testCreateSuccessReturnsData(): void
    {
        $mock = new MockHttpClient(function (string $method, string $url, array $options) {
            if ($method !== 'POST') {
                return new MockResponse('Wrong method', ['http_code' => 500]);
            }

            return new MockResponse(
                json_encode(['data' => ['id' => 123, 'first_name' => 'John']]),
                ['http_code' => 201, 'response_headers' => ['content-type: application/json']]
            );
        });

        $client = new PhoenixUsersClient($mock, 'http://phoenix:4000/api');

        $res = $client->create([
            'user' => [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'gender'     => 'male',
                'birthdate'  => '1990-01-01',
            ],
        ]);

        if (!isset($res['data'])) {
            self::fail('Unexpected response: ' . json_encode($res));
        }

        self::assertSame(123, $res['data']['id']);
    }

    public function testCreateSendsJsonAndAcceptHeader(): void
{
    $called = false;

    $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$called) {
        $called = true;

        self::assertSame('POST', $method);
        self::assertSame('http://phoenix:4000/api/users', $url);

        $headers = $options['headers'] ?? [];
        self::assertSame('application/json', $headers['Accept'] ?? null);

        $decoded = json_decode($options['body'] ?? '', true);
        self::assertArrayHasKey('user', $decoded);
        self::assertSame('John', $decoded['user']['first_name']);

        return new MockResponse(
            json_encode(['data' => ['id' => 1]]),
            ['http_code' => 201, 'response_headers' => ['content-type: application/json']]
        );
    });

    $client = new PhoenixUsersClient($mock, 'http://phoenix:4000/api');

    $client->create([
        'user' => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'gender'     => 'male',
            'birthdate'  => '1990-01-01',
        ],
    ]);

    self::assertTrue($called);
}



    public function testCreateValidationErrorReturnsStatusAndErrors(): void
    {
        $mock = new MockHttpClient(function () {
            return new MockResponse(
                json_encode(['errors' => ['first_name' => ['cannot be blank']]]),
                ['http_code' => 422, 'response_headers' => ['content-type: application/json']]
            );
        });

        $client = new PhoenixUsersClient($mock, 'http://phoenix:4000/api');

        $res = $client->create([
            'user' => [
                'first_name' => '',
                'last_name'  => 'Doe',
                'gender'     => 'male',
                'birthdate'  => '1990-01-01',
            ],
        ]);

        self::assertSame(422, $res['_status'] ?? null);
        self::assertArrayHasKey('errors', $res);
        self::assertArrayHasKey('first_name', $res['errors']);
    }

    public function testCreateNonJsonErrorReturnsRawInsteadOfThrowing(): void
    {
        $mock = new MockHttpClient(function () {
            return new MockResponse(
                '<html><body>Bad Request</body></html>',
                ['http_code' => 400, 'response_headers' => ['content-type: text/html; charset=utf-8']]
            );
        });

        $client = new PhoenixUsersClient($mock, 'http://phoenix:4000/api');

        $res = $client->create([
            'user' => [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'gender'     => 'male',
                'birthdate'  => '1990-01-01',
            ],
        ]);

        self::assertSame(400, $res['_status'] ?? null);
        self::assertArrayHasKey('_raw', $res);
        self::assertStringContainsString('Bad Request', $res['_raw']);
        self::assertArrayHasKey('_content_type', $res);
        self::assertStringContainsString('text/html', $res['_content_type']);
    }
}
