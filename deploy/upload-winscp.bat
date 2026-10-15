@echo off
rem ===========================================================================
rem Armory Falter - Paket per SFTP zu One.com hochladen (Windows, WinSCP)
rem
rem Voraussetzung: WinSCP installiert (winscp.com im Standardpfad oder im PATH).
rem Zugangsdaten: deploy\.deploy.env (Vorlage deploy\.deploy.env.example),
rem diese Datei wird nie committet und nie ins ZIP gepackt.
rem
rem Aufruf (im Projektordner):   deploy\upload-winscp.bat [Pfad\zum\paket.zip]
rem Nur Installer loeschen:      deploy\upload-winscp.bat --remove-installer
rem
rem Es wird nichts geloescht; .env, Uploads, Logs bleiben auf dem Server erhalten.
rem ===========================================================================
setlocal EnableExtensions EnableDelayedExpansion
set "ROOT=%~dp0.."
set "CONF=%~dp0.deploy.env"
if not exist "%CONF%" ( echo Fehlt: %CONF% & exit /b 1 )
for /f "usebackq eol=# tokens=1,* delims==" %%A in ("%CONF%") do set "%%A=%%~B"
if "%SFTP_PORT%"=="" set "SFTP_PORT=22"

set "WINSCP=winscp.com"
if exist "%ProgramFiles(x86)%\WinSCP\WinSCP.com" set "WINSCP=%ProgramFiles(x86)%\WinSCP\WinSCP.com"
if exist "%ProgramFiles%\WinSCP\WinSCP.com" set "WINSCP=%ProgramFiles%\WinSCP\WinSCP.com"

if "%SFTP_HOSTKEY%"=="" (
  echo WARNUNG: SFTP_HOSTKEY ist leer - der Server-Schluessel wird nicht geprueft.
  set "HOSTKEY=-hostkey=*"
) else (
  set "HOSTKEY=-hostkey=""%SFTP_HOSTKEY%"""
)
set "OPEN=open sftp://%SFTP_USER%@%SFTP_HOST%:%SFTP_PORT%/ -password=""%SFTP_PASS%"" !HOSTKEY!"

if "%~1"=="--remove-installer" (
  "%WINSCP%" /ini=nul /command "!OPEN!" "rm ""%REMOTE_DIR%/public/install.php""" "exit"
  exit /b %ERRORLEVEL%
)

set "ZIP=%~1"
if "%ZIP%"=="" for /f "delims=" %%F in ('dir /b /o-d "%ROOT%\dist\armory-falter-onecom-*.zip" 2^>nul') do if not defined ZIP set "ZIP=%ROOT%\dist\%%F"
if not exist "%ZIP%" ( echo Kein Paket gefunden - zuerst deploy/build-onecom.sh ausfuehren & exit /b 1 )

set "PKG=%TEMP%\af-upload-%RANDOM%"
powershell -NoProfile -Command "Expand-Archive -LiteralPath '%ZIP%' -DestinationPath '%PKG%' -Force" || exit /b 1
echo Lade %ZIP% nach %SFTP_HOST%/%REMOTE_DIR% hoch ...

"%WINSCP%" /ini=nul /log="%TEMP%\af-upload.log" /command ^
  "!OPEN!" ^
  "option confirm off" ^
  "option batch continue" ^
  "mkdir ""%REMOTE_DIR%""" ^
  "option batch abort" ^
  "put -transfer=binary -nopermissions -nopreservetime -filemask=""|.env"" ""%PKG%\*"" ""%REMOTE_DIR%/""" ^
  "exit"
set "RC=%ERRORLEVEL%"
rmdir /s /q "%PKG%"
if not "%RC%"=="0" ( echo Upload fehlgeschlagen, siehe %TEMP%\af-upload.log & exit /b %RC% )
echo.
echo Upload fertig. Jetzt https://^<domain^>/install.php aufrufen,
echo danach: deploy\upload-winscp.bat --remove-installer
endlocal
