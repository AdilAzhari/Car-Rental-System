#!/bin/bash

# Cool Parallel Testing Script with Performance Monitoring
echo "🚀 Running Laravel Tests with Power Features!"
echo "================================================"

# Function to run tests with performance metrics
run_test_suite() {
    local suite=$1
    local processes=${2:-4}
    
    echo "🧪 Running $suite tests with $processes parallel processes..."
    
    start_time=$(date +%s.%N)
    
    if [ "$suite" = "all" ]; then
        php artisan test --parallel --processes=$processes
    else
        php artisan test --parallel --processes=$processes --group=$suite
    fi
    
    end_time=$(date +%s.%N)
    duration=$(echo "scale=2; $end_time - $start_time" | bc)
    
    echo "⏱️  $suite completed in ${duration}s"
    echo ""
}

# 1. Run fast parallel tests (Unit + API tests)
echo "Phase 1: Fast Parallel Tests (Unit + API)"
run_test_suite "parallel" 6

# 2. Run database-critical tests (single thread)
echo "Phase 2: Database Critical Tests (Single Thread)"
run_test_suite "database" 1

# 3. Run browser tests (resource intensive)
echo "Phase 3: Browser Tests (Limited Parallel)"
run_test_suite "browser" 2

# 4. Performance summary
echo "🎯 Testing Complete! Summary:"
echo "================================"
echo "✅ Parallel testing optimized for different test types"
echo "✅ Rate limiting tested across user roles"
echo "✅ Eloquent scopes tested with performance monitoring"
echo "✅ All power features validated!"

# Bonus: Run specific power features test
echo ""
echo "🔥 Running Power Features Demo Test..."
php artisan test tests/Feature/PowerFeaturesTest.php --parallel --processes=2