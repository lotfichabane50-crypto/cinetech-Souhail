<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LegacyReportControllerTest extends WebTestCase
{
    public function testLegacyReportPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/legacy/rapport');

        self::assertResponseIsSuccessful();
    }

    public function testLegacyReportPageDisplaysExpectedResults(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/legacy/rapport');

        self::assertResponseIsSuccessful();

        $content = $crawler->text();

        // Badge : tag absent => STANDARD
        self::assertStringContainsString('[STANDARD] Anatomie d\'une chute', $content);

        // Moyenne : les notes null sont ignorées, aucun avis valide => 0
        self::assertStringContainsString('Moyenne : 0/5', $content);

        // Tarifs : 65 ans inclus remisé, cumul mineur + abonné
        self::assertStringContainsString('Senior (65 ans, non membre) : 9 €', $content);
        self::assertStringContainsString('Étudiant membre (16 ans, abonné) : 7.2 €', $content);
    }
}
