@echo off
title eTIMS POS - Starting...
color 0A

echo.
echo  ============================================
echo   eTIMS POS - Kenya KRA Fiscalization
echo  ============================================
echo.
echo  Starting your POS system...
echo  Please wait, this takes about 10 seconds.
echo.

:: Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  ERROR: PHP is not installed or not in PATH.
    echo  Please install PHP from https://windows.php.net/download/
    echo  and add it to your system PATH.
    pause
    exit /b 1
)

:: Set the working directory to the POS folder
cd /d "%~dp0"

:: Start the queue worker in background
echo  Starting queue worker...
start "eTIMS Queue Worker" /min cmd /c "php artisan queue:work --queue=etims,default --tries=5 --sleep=3 2>> storage\logs\queue.log"

:: Wait 2 seconds for queue to start
timeout /t 2 /nobreak >nul

:: Start the web server
echo  Starting web server...
start "eTIMS Web Server" /min cmd /c "php artisan serve --port=8000 2>> storage\logs\server.log"

:: Wait 3 seconds for server to start
timeout /t 3 /nobreak >nul

:: Open the browser
echo  Opening POS in browser...
start http://localhost:8000/pos

echo.
echo  ============================================
echo   eTIMS POS is running!
echo  ============================================
echo.
echo  - POS Terminal: http://localhost:8000/pos
echo  - Dashboard:    http://localhost:8000/dashboard
echo.
echo  Login: admin@demo.co.ke / password
echo.
echo  KEEP THIS WINDOW OPEN while using the POS.
echo  Close it only when you are done for the day.
echo.
echo  Press any key to stop the POS server...
pause >nul

:: Cleanup - kill the servers when user presses a key
echo  Shutting down...
taskkill /f /im php.exe >nul 2>&1
echo  POS stopped. Goodbye!
timeout /t 2 /nobreak >nul
