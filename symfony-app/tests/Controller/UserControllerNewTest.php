<?php

namespace App\Tests\Controller;

use App\Http\PhoenixUsersClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerNewTest extends WebTestCase
{
    public function testNewCreatesUserAndRedirects(): void
    {
        $browser = static::createClient();
        $browser->disableReboot();

        $mock = $this->createMock(PhoenixUsersClientInterface::class);

        $mock->expects(self::once())
            ->method('create')
            ->with(self::callback(function (array $payload): bool {
                return isset($payload['user'])
                    && ($payload['user']['first_name'] ?? null) === 'John'
                    && ($payload['user']['last_name'] ?? null) === 'Doe'
                    && ($payload['user']['gender'] ?? null) === 'male'
                    && ($payload['user']['birthdate'] ?? null) === '1990-01-01';
            }))
            ->willReturn([
                '_status' => 201,
                'data' => ['id' => 100],
            ]);

        $browser->getContainer()->set(PhoenixUsersClientInterface::class, $mock);

        $crawler = $browser->request('GET', '/users/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('#createBtn')->form();
        $form['user[first_name]'] = 'John';
        $form['user[last_name]']  = 'Doe';
        $form['user[gender]']     = 'male';
        $form['user[birthdate]']  = '1990-01-01';

        $browser->submit($form);

        self::assertResponseRedirects('/users');

        $browser->followRedirect();
        self::assertResponseIsSuccessful();
    }
}
