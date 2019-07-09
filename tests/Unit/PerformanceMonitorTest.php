<?php
declare(strict_types=1);

namespace DealerinspireLaravelPerformanceMonitor\Tests\Unit;

use Carbon\Carbon;
use DealerInspire\LaravelPerformanceMonitor\PerformanceMonitor;
use DealerInspire\LaravelPerformanceMonitor\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class PerformanceMonitorTest extends TestCase
{
    /**
     * @var MockInterface
     */
    protected $log;

    public function setUp(): void
    {
        parent::setUp();

        $this->log = \Mockery::mock(LoggerInterface::class);
    }

    public function testExecuteDoesNotCheckExecutionTimeWhenConfigValueNotSet()
    {
        Carbon::setTestNow('2019-01-01 00:00:00');

        $now = Carbon::now();
        $end = $now->copy()->addSeconds(901);

        $performanceMonitor = new PerformanceMonitor($this->log);

        Config::set('performancemonitor.enable_execution_time_check', false);
        Config::set('performancemonitor.execution_time_max_seconds', 0);

        $this->log->shouldNotReceive('error');
        $performanceMonitor->execute($now->timestamp, $end->timestamp, 0, '0M');
    }

    public function testExecuteLogsErrorWhenExecutionTimeThresholdReached()
    {
        Carbon::setTestNow('2019-01-01 00:00:00');

        $now = Carbon::now();
        $end = $now->copy()->addSeconds(901);

        $performanceMonitor = new PerformanceMonitor($this->log);

        Config::set('performancemonitor.enable_execution_time_check', true);
        Config::set('performancemonitor.execution_time_max_seconds', 900);

        $this->log->shouldReceive('error')
            ->once()
            ->with('Long-running process detected. Script run time: 901 seconds. Execution Warning Time Limit: 900 seconds');

        $performanceMonitor->execute($now->timestamp, $end->timestamp, 0, '0M');
    }

    public function testExecuteDoesNotCheckMemoryUsageWhenConfigValueNotSet()
    {
        Config::set('performancemonitor.enable_memory_limit_check', null);
        Config::set('performancemonitor.memory_limit_max_memory_percent', 0);

        $performanceMonitor = new PerformanceMonitor($this->log);
        $this->log->shouldReceive('error');
        $performanceMonitor->execute(0.0, 0.0, 100, '0B');
    }

    public function testExecuteLogsErrorWhenMemoryThresholdReached()
    {
        Config::set('performancemonitor.enable_memory_limit_check', true);
        Config::set('performancemonitor.memory_limit_max_memory_percent', 80);

        $performanceMonitor = new PerformanceMonitor($this->log);

        $this->log->shouldReceive('error')
            ->once()
            ->with('Memory usage spike detected. Used memory: 13,108 bytes. Memory Warning Limit: 13,107 bytes (80% of available memory)');

        $performanceMonitor->execute(0.0, 0.0, 13108, '16K');
    }
}
