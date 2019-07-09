<?php
declare(strict_types=1);

namespace DealerInspire\LaravelPerformanceMonitor;

use Psr\Log\LoggerInterface;

class PerformanceMonitor
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var bool
     */
    protected $enableExecutionTimeCheck;

    /**
     * @var bool
     */
    protected $enableMemoryLimitCheck;

    /**
     * @var int
     */
    protected $executionTimeThreshold;

    /**
     * @var int
     */
    protected $memoryLimitThreshold;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;

        $this->enableExecutionTimeCheck = config('performancemonitor.enable_execution_time_check');
        $this->enableMemoryLimitCheck = config('performancemonitor.enable_memory_limit_check');

        $this->executionTimeThreshold = config('performancemonitor.execution_time_max_seconds');
        $this->memoryLimitThreshold = config('performancemonitor.memory_limit_max_memory_percent');
    }

    /**
     * Execute our performance monitoring checks
     *
     * @param float $startTime
     * @param float $endTime
     * @param int $memoryUsage
     * @param string $memoryLimit
     *
     * @return void
     */
    public function execute(float $startTime, float $endTime, int $memoryUsage, string $memoryLimit)
    {
        if ($this->enableExecutionTimeCheck) {
            $this->checkApplicationExecutionTime($startTime, $endTime);
        }

        if ($this->enableMemoryLimitCheck) {
            $this->checkMemoryThreshold($memoryUsage, $memoryLimit);
        }
    }

    /**
     * Checks if the application execution time is greater than the threshold
     *
     * @param float $startTime
     * @param float $endTime
     *
     * @return void
     */
    public function checkApplicationExecutionTime(float $startTime, float $endTime): void
    {
        $executionTime = ($endTime - $startTime);
        $maxExecutionTime = $this->executionTimeThreshold;
        if ($executionTime > $maxExecutionTime) {
            $this->log->error(sprintf('Long-running process detected. Script run time: %d seconds. Execution Warning Time Limit: %d seconds', $executionTime, $maxExecutionTime));
        }
    }

    /**
     * Checks if the application memory usage is greater than the threshold
     *
     * @param int $memoryUsage
     * @param string $memoryLimit
     *
     * @return void
     */
    public function checkMemoryThreshold(int $memoryUsage, string $memoryLimit): void
    {
        $maxUsagePercent = $this->memoryLimitThreshold;

        if (empty($memoryLimit)) {
            return;
        }

        $memoryLimitInBytes = $this->getLimitAsBytes($memoryLimit);
        $actualUsagePercent = ($memoryUsage / $memoryLimitInBytes) * 100;
        if ($actualUsagePercent >= $maxUsagePercent) {
            $this->log->error(
                sprintf(
                    'Memory usage spike detected. Used memory: %s bytes. Memory Warning Limit: %s bytes (%d%% of available memory)',
                    number_format($memoryUsage),
                    number_format($memoryLimitInBytes * $maxUsagePercent / 100),
                    $maxUsagePercent
                )
            );
        }
    }

    /**
     * Convert a given *byte string to bytes (ex) $limit of "1K" returns 1024)
     *
     * @param string $limit
     * @return int
     */
    protected function getLimitAsBytes(string $limit): int
    {
        $limitInt = (int)$limit;
        $unit = strtolower(str_replace($limitInt, '', $limit));

        switch ($unit) {
            case 'g':
                $limitInt *= (1024 * 1024 * 1024);
                break;
            case 'm':
                $limitInt *= (1024 * 1024);
                break;
            case 'k':
                $limitInt *= 1024;
                break;
        }

        return $limitInt;
    }
}
