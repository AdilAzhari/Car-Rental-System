@echo off
echo ========================================
echo   Car Rental System - Comprehensive Testing Suite
echo ========================================
echo.

:: Create coverage directory
if not exist "coverage" mkdir coverage

echo [1/8] Running Unit Tests...
call vendor\bin\pest tests\Unit --coverage --coverage-html=coverage\html\unit --coverage-clover=coverage\unit-clover.xml
if %errorlevel% neq 0 (
    echo Unit tests failed!
    exit /b 1
)
echo Unit tests completed successfully!
echo.

echo [2/8] Running Feature Tests...
call vendor\bin\pest tests\Feature --coverage --coverage-html=coverage\html\feature --coverage-clover=coverage\feature-clover.xml
if %errorlevel% neq 0 (
    echo Feature tests failed!
    exit /b 1
)
echo Feature tests completed successfully!
echo.

echo [3/8] Running Architecture Tests...
call vendor\bin\pest tests\Arch
if %errorlevel% neq 0 (
    echo Architecture tests failed!
    exit /b 1
)
echo Architecture tests completed successfully!
echo.

echo [4/8] Running Performance Tests...
call vendor\bin\pest tests\Performance
if %errorlevel% neq 0 (
    echo Performance tests failed!
    exit /b 1
)
echo Performance tests completed successfully!
echo.

echo [5/8] Running Browser Tests (Pest v4)...
call vendor\bin\pest tests\Browser
if %errorlevel% neq 0 (
    echo Browser tests failed!
    exit /b 1
)
echo Browser tests completed successfully!
echo.

echo [6/8] Generating Overall Code Coverage Report...
call vendor\bin\phpunit --coverage-html=coverage\html\overall --coverage-clover=coverage\overall-clover.xml --coverage-text=coverage\coverage-summary.txt
if %errorlevel% neq 0 (
    echo Code coverage generation failed!
    exit /b 1
)
echo Code coverage report generated successfully!
echo.

echo [7/8] Running Static Analysis (if PHPStan is available)...
if exist vendor\bin\phpstan (
    call vendor\bin\phpstan analyse app --level=8 --error-format=table > coverage\phpstan-report.txt
    echo Static analysis completed!
) else (
    echo PHPStan not available, skipping static analysis...
)
echo.

echo [8/8] Generating Test Summary...
echo Testing Summary > coverage\test-summary.txt
echo =============== >> coverage\test-summary.txt
echo Test Date: %date% %time% >> coverage\test-summary.txt
echo. >> coverage\test-summary.txt

:: Display coverage summary if available
if exist coverage\coverage-summary.txt (
    echo Code Coverage Summary: >> coverage\test-summary.txt
    type coverage\coverage-summary.txt >> coverage\test-summary.txt
)

echo.
echo ========================================
echo   All Tests Completed Successfully!
echo ========================================
echo.
echo Test results and coverage reports are available in the 'coverage' directory:
echo - coverage\html\overall\index.html - Overall code coverage
echo - coverage\html\unit\index.html - Unit test coverage
echo - coverage\html\feature\index.html - Feature test coverage
echo - coverage\test-summary.txt - Test summary
echo.
echo Open coverage\html\overall\index.html in your browser to view the coverage report.
echo.

pause