<?php

namespace App\Tests\Controller;

use App\Http\PhoenixUsersClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerIndexTest extends WebTestCase
{
    public function testIndexRenders(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $mock = $this->createMock(PhoenixUsersClientInterface::class);
        $mock->method('list')->willReturn(['data' => []]);

        static::getContainer()->set(PhoenixUsersClientInterface::class, $mock);

        $client->request('GET', '/users');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Users');
    }
}
