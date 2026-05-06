<?php

declare(strict_types=1);

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventApiTest extends WebTestCase
{
    public function testEventsEndpointReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v2/shop/events', [], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        self::assertJson((string) $client->getResponse()->getContent());

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('items', $data);
        self::assertGreaterThan(0, $data['total']);
    }

    public function testEventsEndpointFiltersByCity(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v2/shop/events?city=Krak%C3%B3w');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotEmpty($data['items'], 'Expected at least one Kraków event in fixtures');

        foreach ($data['items'] as $item) {
            self::assertEquals('Kraków', $item['city']['name'], "All items should have city 'Kraków'");
        }
    }

    public function testEventsEndpointHasCorsHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v2/shop/events');

        self::assertResponseIsSuccessful();
        self::assertSame('*', $client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testEventsEndpointFiltersByEventType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v2/shop/events?eventType=meetup');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        foreach ($data['items'] as $item) {
            self::assertEquals('meetup', $item['eventType']);
        }
    }
}
