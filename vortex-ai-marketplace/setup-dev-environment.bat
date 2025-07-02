@echo off
echo ================================
echo VORTEX AI Marketplace Dev Setup
echo ================================
echo.

REM Create directories
mkdir "%USERPROFILE%\vortex-dev" 2>nul
mkdir "%USERPROFILE%\vortex-dev\php" 2>nul
mkdir "%USERPROFILE%\vortex-dev\composer" 2>nul

echo [1/4] Downloading PHP 8.2...
powershell -Command "Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.2.25-Win32-vs16-x64.zip' -OutFile '%USERPROFILE%\vortex-dev\php.zip'"

echo [2/4] Extracting PHP...
powershell -Command "Expand-Archive -Path '%USERPROFILE%\vortex-dev\php.zip' -DestinationPath '%USERPROFILE%\vortex-dev\php' -Force"

echo [3/4] Downloading Composer...
powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/composer.phar' -OutFile '%USERPROFILE%\vortex-dev\composer\composer.phar'"

echo [4/4] Creating launcher scripts...
echo @echo off > "%USERPROFILE%\vortex-dev\php.bat"
echo "%USERPROFILE%\vortex-dev\php\php.exe" %%* >> "%USERPROFILE%\vortex-dev\php.bat"

echo @echo off > "%USERPROFILE%\vortex-dev\composer.bat"
echo "%USERPROFILE%\vortex-dev\php\php.exe" "%USERPROFILE%\vortex-dev\composer\composer.phar" %%* >> "%USERPROFILE%\vortex-dev\composer.bat"

echo.
echo ================================
echo Setup Complete!
echo ================================
echo.
echo To use PHP: %USERPROFILE%\vortex-dev\php.bat --version
echo To use Composer: %USERPROFILE%\vortex-dev\composer.bat --version
echo.
echo Add to your PATH or run from full path:
echo %USERPROFILE%\vortex-dev
echo.
pause 