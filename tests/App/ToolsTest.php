<?php

declare(strict_types=1);

namespace App\Tests\App;

use App\Tests\Factory\CollectionFactory;
use App\Tests\Factory\ItemFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Tests\AppTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ToolsTest extends AppTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    public function test_can_see_tools(): void
    {
        // Arrange
        $user = UserFactory::createOne()->object();
        $this->client->loginUser($user);

        // Act
        $crawler = $this->client->request('GET', '/tools');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame('Tools', $crawler->filter('h1')->text());
    }

    public function test_can_export_printable_list(): void
    {
        // Arrange
        $user = UserFactory::createOne()->object();
        $this->client->loginUser($user);
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #2', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #3', 'collection' => $collection, 'owner' => $user])->object();

        // Act
        $crawler = $this->client->request('GET', '/tools/export/printable-list');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.print'));
    }

    public function test_can_export_csv(): void
    {
        // Arrange
        $user = UserFactory::createOne()->object();
        $this->client->loginUser($user);
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #2', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #3', 'collection' => $collection, 'owner' => $user])->object();

        // Act
        $this->client->request('GET', '/tools/export/csv');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_can_export_sql(): void
    {
        // Arrange
        $user = UserFactory::createOne()->object();
        $this->client->loginUser($user);
        $collection = CollectionFactory::createOne(['title' => 'Frieren', 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #2', 'collection' => $collection, 'owner' => $user])->object();
        ItemFactory::createOne(['name' => 'Frieren #3', 'collection' => $collection, 'owner' => $user])->object();

        // Act
        $this->client->request('GET', '/tools/export/sql');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
    }
}
