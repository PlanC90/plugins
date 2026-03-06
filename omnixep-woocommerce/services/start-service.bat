@echo off
echo Starting OmniXEP Auto-Fee Service...
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed!
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

REM Install dependencies if needed
if not exist "node_modules" (
    echo Installing dependencies...
    npm install
    echo.
)

REM Start the service
echo Starting service on port 3001...
echo Press Ctrl+C to stop
echo.
npm start

pause
