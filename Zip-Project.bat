@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0zip-project.ps1"
if errorlevel 1 pause
