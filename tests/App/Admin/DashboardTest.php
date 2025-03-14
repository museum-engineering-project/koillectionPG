<?php

declare(strict_types=1);

namespace App\Tests\App\Admin;

use App\Enum\RoleEnum;
use App\Tests\Factory\CollectionFactory;
use App\Tests\Factory\ItemFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\AppTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DashboardTest extends AppTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    public function test_admin_can_access_dashboard(): void
    {
        // Arrange
        $admin = UserFactory::createOne(['roles' => [RoleEnum::ROLE_ADMIN]])->object();
        $this->client->loginUser($admin);

        // Act
        $this->client->request('GET', '/admin');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function test_can_export_sql(): void
    {
        // Arrange
        $admin = UserFactory::createOne(['roles' => [RoleEnum::ROLE_ADMIN]])->object();
        $this->client->loginUser($admin);
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #2', 'collection' => $collection, 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #3', 'collection' => $collection, 'owner' => $admin])->object();

        // Act
        $this->client->request('GET', '/admin/export/sql');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_can_refresh_caches(): void
    {
        // Arrange
        $admin = UserFactory::createOne(['roles' => [RoleEnum::ROLE_ADMIN]])->object();
        $this->client->loginUser($admin);
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #2', 'collection' => $collection, 'owner' => $admin])->object();
        ItemFactory::createOne(['name' => 'Frieren #3', 'collection' => $collection, 'owner' => $admin])->object();

        // Act
        $this->client->request('GET', '/admin/refresh-caches');

        // Assert
        $this->assertResponseIsSuccessful();
    }
}
