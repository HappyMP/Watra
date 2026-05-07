<?php

declare(strict_types=1);

namespace App\Twig\Component\Admin;

use App\Service\DashboardMetricsService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('WatraDashboardMetrics', template: 'admin/dashboard/metrics.html.twig')]
final class DashboardMetricsComponent
{
    public function __construct(
        private readonly DashboardMetricsService $metricsService,
    ) {
    }

    #[ExposeInTemplate]
    public function getPublishedEvents(): int
    {
        return $this->metricsService->getPublishedEventsCount();
    }

    #[ExposeInTemplate]
    public function getBookings7d(): int
    {
        return $this->metricsService->getBookingsLast7Days();
    }

    #[ExposeInTemplate]
    public function getNewCustomers7d(): int
    {
        return $this->metricsService->getNewCustomersLast7Days();
    }

    #[ExposeInTemplate]
    public function getAvgOccupancy(): int
    {
        return $this->metricsService->getAvgOccupancy();
    }

    /**
     * @return array<int, array{name: string, occupancyPct: int}>
     */
    #[ExposeInTemplate]
    public function getUpcomingOccupancy(): array
    {
        return $this->metricsService->getUpcomingOccupancy(8);
    }
}
