<?php

declare(strict_types=1);

namespace App\Tests\App\Security;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\AppTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FirstConnectionTest extends AppTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    public function test_redirected_to_first_connection_if_no_user(): void
    {
        // Arrange

        // Act
        $this->client->request('GET', '');

        // Assert
        $this->assertRouteSame('app_security_first_connection');
    }

    public function test_can_complete_first_connection(): void
    {
        // Arrange

        // Act
        $this->client->request('GET', '/first-connection');
        $this->client->submitForm('Submit', [
            'user[username]' => 'Stitch',
            'user[email]' => 'stitch@koillection.com',
            'user[plainPassword][first]' => 'password1234',
            'user[plainPassword][second]' => 'password1234',
            'user[timezone]' => 'Pacific/Honolulu',
        ]);

        // Assert
        $this->assertRouteSame('app_collection_index');
    }

    public function test_cant_redo_first_connection(): void
    {
        // Arrange
        UserFactory::createOne()->object();

        // Act
        $this->client->request('GET', '/first-connection');

        // Assert
        $this->assertRouteSame('app_security_login');
    }
}
