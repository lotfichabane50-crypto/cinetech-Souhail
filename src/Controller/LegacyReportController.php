<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LegacyBoxOfficeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyReportController extends AbstractController
{
    #[Route('/legacy/rapport', name: 'app_legacy_report', methods: ['GET'])]
    public function index(LegacyBoxOfficeService $service): Response
    {
        $entries = [
            ['title' => 'Inception', 'tag' => 'imax', 'ratings' => [5, 4, 5]],
            ['title' => 'Parasite', 'tag' => 'vo', 'ratings' => [5, null, 4]],
            ['title' => "Anatomie d'une chute", 'tag' => null, 'ratings' => []],
        ];

        $report = [];
        foreach ($entries as $entry) {
            $report[] = [
                'label' => $service->formatBadge($entry),
                'average' => $service->calculateAverageRating($entry['ratings']),
            ];
        }

        $prices = [
            'Adulte standard (30 ans)' => $service->calculateTicketPrice(12.0, 30, false),
            'Senior (65 ans, non membre)' => $service->calculateTicketPrice(12.0, 65, false),
            'Étudiant membre (16 ans, abonné)' => $service->calculateTicketPrice(12.0, 16, true),
        ];

        return $this->render('legacy_report/index.html.twig', [
            'report' => $report,
            'prices' => $prices,
        ]);
    }
}
