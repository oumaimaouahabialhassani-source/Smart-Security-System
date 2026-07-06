@echo off
rem Starts MySQL (if not already running) + the Laravel dev server.
rem Double-click this file, or run: .\serve.bat

set PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set MYSQLD=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe
set MYCNF=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini

rem Start MySQL only if port 3306 is not already in use
netstat -an | findstr /C:":3306 " | findstr LISTENING >nul
if errorlevel 1 (
    echo Starting MySQL...
    start "" /B "%MYSQLD%" --defaults-file="%MYCNF%"
    timeout /t 5 /nobreak >nul
) else (
    echo MySQL already running.
)

echo Starting Laravel dev server on http://127.0.0.1:8000 ...
"%PHP%" artisan serve --port=8000
