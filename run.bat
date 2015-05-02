@echo off

REM set working directory to .bat location (required when ran as administrator)
@setlocal enableextensions
@cd /d "%~dp0"

pingmonitor.py
pause