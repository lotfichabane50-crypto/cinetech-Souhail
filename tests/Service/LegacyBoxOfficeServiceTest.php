<?php

namespace App\Tests\Service;

use App\Service\LegacyBoxOfficeService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyBoxOfficeServiceTest extends TestCase
{
    private LegacyBoxOfficeService $service;

    protected function setUp(): void
    {
        $this->service = new LegacyBoxOfficeService();
    }

    public function testBadgeIsStandardWhenTagIsNull(): void
    {
        $label = $this->service->formatBadge(['title' => 'Sans tag', 'tag' => null]);

        self::assertSame('[STANDARD] Sans tag', $label);
    }

    public function testBadgeIsStandardWhenTagIsEmpty(): void
    {
        $label = $this->service->formatBadge(['title' => 'Sans tag', 'tag' => '  ']);

        self::assertSame('[STANDARD] Sans tag', $label);
    }

    public function testBadgeIsUppercasedWhenTagIsProvided(): void
    {
        $label = $this->service->formatBadge(['title' => 'Inception', 'tag' => 'imax']);

        self::assertSame('[IMAX] Inception', $label);
    }

    public function testAverageIgnoresNullRatings(): void
    {
        $average = $this->service->calculateAverageRating([5, null, 4]);

        self::assertSame(4.5, $average);
    }

    public function testAverageIsZeroWhenNoValidRating(): void
    {
        self::assertSame(0.0, $this->service->calculateAverageRating([]));
        self::assertSame(0.0, $this->service->calculateAverageRating([null, null]));
    }

    #[DataProvider('ticketPriceProvider')]
    public function testTicketPriceAppliesBusinessRules(
        float $basePrice,
        int $age,
        bool $isMember,
        float $expected,
    ): void {
        self::assertSame($expected, $this->service->calculateTicketPrice($basePrice, $age, $isMember));
    }

    /**
     * @return iterable<string, array{float, int, bool, float}>
     */
    public static function ticketPriceProvider(): iterable
    {
        yield 'adulte standard' => [12.0, 30, false, 12.0];
        yield 'mineur (-25%)' => [12.0, 17, false, 9.0];
        yield 'senior inclus à 65 ans (-25%)' => [12.0, 65, false, 9.0];
        yield '64 ans non remisé' => [12.0, 64, false, 12.0];
        yield 'abonné adulte (-20%)' => [12.0, 30, true, 9.6];
        yield 'mineur abonné (-25% puis -20%)' => [12.0, 16, true, 7.2];
        yield 'senior abonné (-25% puis -20%)' => [12.0, 70, true, 7.2];
    }
}
