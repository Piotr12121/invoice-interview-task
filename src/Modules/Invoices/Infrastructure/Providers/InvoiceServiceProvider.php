<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Listeners\ResourceDeliveredEventListener;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Repositories\InvoiceProductLineRepositoryInterface;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Services\InvoiceCalculationService;
use Modules\Invoices\Domain\Services\InvoiceValidationService;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceProductLineRepository;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceRepository;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;

final class InvoiceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->scoped(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->scoped(InvoiceProductLineRepositoryInterface::class, EloquentInvoiceProductLineRepository::class);

        // Register domain services
        $this->app->singleton(InvoiceCalculationService::class);
        $this->app->singleton(InvoiceValidationService::class);

        // Register application services
        $this->app->scoped(InvoiceService::class);
        $this->app->scoped(ResourceDeliveredEventListener::class);
    }

    public function boot(): void
    {
        // Register event listeners
        Event::listen(ResourceDeliveredEvent::class, ResourceDeliveredEventListener::class);
    }

    /** @return array<class-string> */
    public function provides(): array
    {
        return [
            InvoiceRepositoryInterface::class,
            InvoiceProductLineRepositoryInterface::class,
            InvoiceCalculationService::class,
            InvoiceValidationService::class,
            InvoiceService::class,
            ResourceDeliveredEventListener::class,
        ];
    }
}