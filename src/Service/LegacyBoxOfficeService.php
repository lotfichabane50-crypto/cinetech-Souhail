<?php

declare(strict_types=1);

namespace App\Service;

final class LegacyBoxOfficeService
{
    public function formatBadge(array $entry): string
    {
        $tag = $entry['tag'] ?? null;

        if (!\is_string($tag) || trim($tag) === '') {
            return '[STANDARD] ' . $entry['title'];
        }

        return '[' . strtoupper(trim($tag)) . '] ' . $entry['title'];
    }

    /**
     * @param array<int, int|float|null> $ratings
     */
    public function calculateAverageRating(array $ratings): float
    {
        $validRatings = array_filter(
            $ratings,
            static fn (int|float|null $rating): bool => $rating !== null
        );

        if ($validRatings === []) {
            return 0.0;
        }

        return round(array_sum($validRatings) / count($validRatings), 1);
    }

    public function calculateTicketPrice(float $basePrice, int $age, bool $isMember): float
    {
        $price = $basePrice;

        if ($age < 18 || $age >= 65) {
            $price *= 0.75;
        }

        if ($isMember) {
            $price *= 0.80;
        }

        return round($price, 2);
    }
}
