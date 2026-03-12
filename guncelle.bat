@echo off
setlocal enabledelayedexpansion
chcp 65001 > nul

:: Calisan dosyanin adini al
set SCRIPT_NAME=%~nx0

echo === 1. Dosya GitHub'dan Gizleniyor (.gitignore) ===
findstr /x /c:"%SCRIPT_NAME%" .gitignore >nul 2>&1
if errorlevel 1 (
    echo %SCRIPT_NAME%>> .gitignore
    echo %SCRIPT_NAME% .gitignore dosyasina eklendi.
)

echo === 2. Mevcut Versiyon Bulunuyor ===
set LATEST_TAG=
for /f "tokens=*" %%i in ('git describe --tags --abbrev^=0 2^>nul') do set LATEST_TAG=%%i

if "!LATEST_TAG!"=="" (
    set LATEST_TAG=v1.0
    echo Sistemde etiket bulunamadi, v1.0 baz aliniyor.
)

for /f "tokens=1,2 delims=." %%a in ("!LATEST_TAG!") do (
    set MAJOR=%%a
    set MINOR=%%b
)

if "!MINOR!"=="" set MINOR=0

set /a NEW_MINOR=!MINOR!+1
set NEW_TAG=!MAJOR!.!NEW_MINOR!

echo Mevcut Versiyon: !LATEST_TAG!
echo Yeni Versiyon:   !NEW_TAG!

echo === 3. Kodlar Ekleniyor ve Kaydediliyor ===
git add .
git commit -m "Otomatik Guncelleme: Versiyon !NEW_TAG!"

echo === 4. Yeni Versiyon Etiketi Olusturuluyor ===
git tag -a !NEW_TAG! -m "Versiyon !NEW_TAG! otomatik yayini"

echo === 5. GitHub'a Zorla Gonderiliyor (Force Push) ===
git push origin main --force
git push origin !NEW_TAG! --force

echo === ISLEM BASARIYLA TAMAMLANDI ===
pause