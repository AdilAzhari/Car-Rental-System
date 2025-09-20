<?php

/**
 * Comprehensive Test Suite Runner
 *
 * This script runs the complete test suite for the Car Rental System
 * covering all architectural patterns and components.
 */

class TestSuiteRunner
{
    private array $testGroups = [
        'unit' => [
            'description' => 'Unit Tests - Models, DTOs, Services',
            'path' => 'tests/Unit',
            'parallel' => true,
            'timeout' => 60
        ],
        'integration' => [
            'description' => 'Integration Tests - Architectural Patterns',
            'path' => 'tests/Integration',
            'parallel' => false,
            'timeout' => 120
        ],
        'feature' => [
            'description' => 'Feature Tests - API Endpoints',
            'path' => 'tests/Feature',
            'parallel' => true,
            'timeout' => 180
        ],
        'frontend' => [
            'description' => 'Frontend Tests - Vue.js Components',
            'path' => 'tests/Frontend',
            'parallel' => true,
            'timeout' => 120
        ],
        'browser' => [
            'description' => 'Browser Tests - E2E User Flows',
            'path' => 'tests/Browser',
            'parallel' => false,
            'timeout' => 300
        ],
        'performance' => [
            'description' => 'Performance Tests - Load & Speed',
            'path' => 'tests/Performance',
            'parallel' => false,
            'timeout' => 600
        ]
    ];

    private array $results = [];

    public function runFullSuite(array $options = []): void
    {
        $this->displayHeader();

        $groups = $options['groups'] ?? array_keys($this->testGroups);
        $parallel = $options['parallel'] ?? false;

        foreach ($groups as $group) {
            if (!isset($this->testGroups[$group])) {
                $this->output("❌ Unknown test group: {$group}", 'error');
                continue;
            }

            $this->runTestGroup($group, $options);
        }

        $this->displaySummary();
    }

    private function runTestGroup(string $group, array $options = []): void
    {
        $config = $this->testGroups[$group];
        $this->output("\n🧪 Running {$config['description']}", 'info');
        $this->output(str_repeat('=', 60), 'dim');

        $startTime = microtime(true);

        $commands = $this->buildTestCommands($group, $config, $options);

        foreach ($commands as $description => $command) {
            $this->output("\n📋 {$description}");
            $this->output("🔧 Command: {$command}", 'dim');

            $result = $this->executeCommand($command, $config['timeout']);
            $this->results[$group][$description] = $result;

            if ($result['success']) {
                $this->output("✅ Passed ({$result['duration']}s)", 'success');
            } else {
                $this->output("❌ Failed ({$result['duration']}s)", 'error');
                $this->output($result['output'], 'error');

                if ($options['stop_on_failure'] ?? false) {
                    $this->output("\n🛑 Stopping due to failure", 'error');
                    return;
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->output("\n⏱️  Group completed in {$duration}s", 'info');
    }

    private function buildTestCommands(string $group, array $config, array $options): array
    {
        $commands = [];

        switch ($group) {
            case 'unit':
                $commands['Model Tests'] = 'php artisan test tests/Unit/Models --parallel';
                $commands['DTO Tests'] = 'php artisan test tests/Unit/DTOs --parallel';
                $commands['Repository Tests'] = 'php artisan test tests/Unit/Repositories --parallel';
                $commands['Service Tests'] = 'php artisan test tests/Unit/Services --parallel';
                $commands['Event Tests'] = 'php artisan test tests/Unit/Events --parallel';
                $commands['Exception Tests'] = 'php artisan test tests/Unit/Exceptions --parallel';
                break;

            case 'integration':
                $commands['Architectural Integration'] = 'php artisan test tests/Integration/ArchitecturalIntegrationTest.php';
                $commands['Component Integration'] = 'php artisan test tests/Integration --filter="Integration"';
                break;

            case 'feature':
                $commands['API Authentication'] = 'php artisan test tests/Feature/Auth --parallel';
                $commands['Booking API'] = 'php artisan test tests/Feature/Api/BookingControllerTest.php';
                $commands['Enhanced Booking API'] = 'php artisan test tests/Feature/Api/Enhanced/BookingControllerEnhancedTest.php';
                $commands['Car API Enhanced'] = 'php artisan test tests/Feature/Api/CarControllerTest.php';
                $commands['Payment Management'] = 'php artisan test tests/Feature/PaymentManagementTest.php';
                $commands['Vehicle Management'] = 'php artisan test tests/Feature/VehicleManagementTest.php';
                $commands['Admin Panel Tests'] = 'php artisan test tests/Feature/Admin --parallel';
                $commands['Web Controllers'] = 'php artisan test tests/Feature/Web --parallel';
                break;

            case 'browser':
                if ($options['headless'] ?? true) {
                    $commands['Enhanced Booking Flow'] = 'php artisan dusk tests/Browser/Enhanced/BookingFlowBrowserTest.php --headless';
                    $commands['Complete Application Flow'] = 'php artisan dusk tests/Browser/CompleteApplicationFlowTest.php --headless';
                    $commands['Vehicle Management UI'] = 'php artisan dusk tests/Browser/VehicleManagementBrowserTest.php --headless';
                    $commands['User Authentication'] = 'php artisan dusk tests/Browser/UserAuthenticationTest.php --headless';
                    $commands['Admin Dashboard'] = 'php artisan dusk tests/Browser/AdminDashboardTest.php --headless';
                    $commands['Arabic Translation'] = 'php artisan dusk tests/Browser/ArabicTranslationTest.php --headless';
                } else {
                    $commands['Enhanced Booking Flow'] = 'php artisan dusk tests/Browser/Enhanced/BookingFlowBrowserTest.php';
                    $commands['Complete Application Flow'] = 'php artisan dusk tests/Browser/CompleteApplicationFlowTest.php';
                    $commands['Vehicle Management UI'] = 'php artisan dusk tests/Browser/VehicleManagementBrowserTest.php';
                    $commands['User Authentication'] = 'php artisan dusk tests/Browser/UserAuthenticationTest.php';
                }
                break;

            case 'frontend':
                $commands['Vue Component Tests'] = 'npm test tests/Frontend/Components';
                $commands['Vue Page Tests'] = 'npm test tests/Frontend/Pages';
                $commands['Vue Integration Tests'] = 'npm test tests/Frontend/Integration';
                break;

            case 'performance':
                $commands['Load Testing'] = 'php artisan test tests/Performance/PerformanceTest.php';
                $commands['Memory Usage'] = 'php artisan test tests/Performance --filter="Memory"';
                $commands['Database Performance'] = 'php artisan test tests/Performance --filter="Database"';
                break;
        }

        return $commands;
    }

    private function executeCommand(string $command, int $timeout): array
    {
        $startTime = microtime(true);
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes,
            getcwd(),
            null
        );

        if (!is_resource($process)) {
            return [
                'success' => false,
                'output' => 'Failed to start process',
                'duration' => 0
            ];
        }

        fclose($pipes[0]);

        $output = '';
        $error = '';

        while (proc_get_status($process)['running']) {
            $output .= fgets($pipes[1], 4096);
            $error .= fgets($pipes[2], 4096);

            if (microtime(true) - $startTime > $timeout) {
                proc_terminate($process);
                return [
                    'success' => false,
                    'output' => "Test timeout after {$timeout} seconds",
                    'duration' => $timeout
                ];
            }
        }

        $exitCode = proc_close($process);
        $duration = round(microtime(true) - $startTime, 2);

        return [
            'success' => $exitCode === 0,
            'output' => $output . $error,
            'duration' => $duration
        ];
    }

    private function displayHeader(): void
    {
        $this->output("
╔══════════════════════════════════════════════════════════════╗
║                  CAR RENTAL SYSTEM TEST SUITE               ║
║                      Comprehensive Testing                  ║
╚══════════════════════════════════════════════════════════════╝
", 'header');

        $this->output("🎯 Test Coverage Areas:", 'info');
        $this->output("   • Unit Tests (Models, DTOs, Repositories, Services, Events)");
        $this->output("   • Integration Tests (Architectural Patterns)");
        $this->output("   • Feature Tests (API Endpoints, Web Controllers)");
        $this->output("   • Frontend Tests (Vue.js Components, Pages)");
        $this->output("   • Browser Tests (E2E User Flows, Complete Application Flow)");
        $this->output("   • Performance Tests (Load & Speed, Database Performance)");
        $this->output("");
    }

    private function displaySummary(): void
    {
        $this->output("\n" . str_repeat('=', 60), 'dim');
        $this->output("📊 TEST SUITE SUMMARY", 'header');
        $this->output(str_repeat('=', 60), 'dim');

        $totalTests = 0;
        $totalPassed = 0;
        $totalDuration = 0;

        foreach ($this->results as $group => $tests) {
            $groupPassed = 0;
            $groupTotal = count($tests);
            $groupDuration = 0;

            foreach ($tests as $test => $result) {
                if ($result['success']) {
                    $groupPassed++;
                }
                $groupDuration += $result['duration'];
            }

            $totalTests += $groupTotal;
            $totalPassed += $groupPassed;
            $totalDuration += $groupDuration;

            $status = $groupPassed === $groupTotal ? '✅' : '❌';
            $percentage = $groupTotal > 0 ? round(($groupPassed / $groupTotal) * 100, 1) : 0;

            $this->output(sprintf(
                "%s %s: %d/%d passed (%.1f%%) in %.2fs",
                $status,
                ucfirst($group),
                $groupPassed,
                $groupTotal,
                $percentage,
                $groupDuration
            ));
        }

        $this->output(str_repeat('-', 60), 'dim');

        $overallPercentage = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 0;
        $overallStatus = $totalPassed === $totalTests ? '🎉' : '⚠️';

        $this->output(sprintf(
            "%s OVERALL: %d/%d tests passed (%.1f%%) in %.2fs",
            $overallStatus,
            $totalPassed,
            $totalTests,
            $overallPercentage,
            $totalDuration
        ), $totalPassed === $totalTests ? 'success' : 'error');

        if ($totalPassed === $totalTests) {
            $this->output("\n🏆 All tests passed! Your codebase is solid! 🚀", 'success');
        } else {
            $this->output("\n🔧 Some tests failed. Please review and fix issues.", 'error');
        }
    }

    private function output(string $message, string $type = 'default'): void
    {
        $colors = [
            'header' => "\033[1;36m",    // Bright cyan
            'info' => "\033[1;34m",      // Bright blue
            'success' => "\033[1;32m",   // Bright green
            'error' => "\033[1;31m",     // Bright red
            'warning' => "\033[1;33m",   // Bright yellow
            'dim' => "\033[0;37m",       // Gray
            'default' => "\033[0m"       // Reset
        ];

        $color = $colors[$type] ?? $colors['default'];
        $reset = $colors['default'];

        echo $color . $message . $reset . "\n";
    }
}

// CLI Interface
if (php_sapi_name() === 'cli') {
    $options = [];
    $groups = [];

    // Parse command line arguments
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];

        switch ($arg) {
            case '--parallel':
                $options['parallel'] = true;
                break;
            case '--stop-on-failure':
                $options['stop_on_failure'] = true;
                break;
            case '--headless':
                $options['headless'] = true;
                break;
            case '--unit':
                $groups[] = 'unit';
                break;
            case '--integration':
                $groups[] = 'integration';
                break;
            case '--feature':
                $groups[] = 'feature';
                break;
            case '--frontend':
                $groups[] = 'frontend';
                break;
            case '--browser':
                $groups[] = 'browser';
                break;
            case '--performance':
                $groups[] = 'performance';
                break;
            case '--help':
                echo "Usage: php run-test-suite.php [OPTIONS]\n\n";
                echo "Options:\n";
                echo "  --unit           Run only unit tests\n";
                echo "  --integration    Run only integration tests\n";
                echo "  --feature        Run only feature tests\n";
                echo "  --frontend       Run only frontend tests\n";
                echo "  --browser        Run only browser tests\n";
                echo "  --performance    Run only performance tests\n";
                echo "  --parallel       Run tests in parallel where possible\n";
                echo "  --stop-on-failure Stop on first failure\n";
                echo "  --headless       Run browser tests in headless mode\n";
                echo "  --help           Show this help message\n";
                exit(0);
        }
    }

    if (!empty($groups)) {
        $options['groups'] = $groups;
    }

    $runner = new TestSuiteRunner();
    $runner->runFullSuite($options);
}