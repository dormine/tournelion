<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeployControllerTest extends WebTestCase
{
    public function testMigrateWithoutTokenReturnsNotFound(): void
    {
        $client = static::createClient();

        $client->request('POST', '/_deploy/migrate', server: ['HTTP_X_DEPLOY_TOKEN' => 'mauvais']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testMigrateWithTokenRunsMigrations(): void
    {
        $client = static::createClient();

        $client->request('POST', '/_deploy/migrate', server: ['HTTP_X_DEPLOY_TOKEN' => 'test-deploy-token']);

        self::assertResponseIsSuccessful();
    }
}
