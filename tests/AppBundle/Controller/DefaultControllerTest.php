<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Water gauge level', $crawler->filter('title')->text());

        $crawler = $client->request('GET', '/en/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Water gauge level', $crawler->filter('title')->text());

        $crawler = $client->request('GET', '/de/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Wasserstand Zisterne', $crawler->filter('title')->text());
    }
}
