<?php
declare(strict_types=1);

namespace DealerInspire\LaravelPerformanceMonitor;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class PerformanceMonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/config/performancemonitor.php' => config_path('performancemonitor.php'),
            ]
        );

        $this->app->terminating(function(){
            (new PerformanceMonitor($this->app->make(LoggerInterface::class)))->execute();
        });
    }
}
