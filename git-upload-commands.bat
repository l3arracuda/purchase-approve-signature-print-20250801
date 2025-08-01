@echo off
echo ========================================
echo  GitHub Upload Commands for Romar PO
echo ========================================
echo.

echo Step 1: Initialize Git Repository
git init

echo.
echo Step 2: Add GitHub Remote
git remote add origin https://github.com/l3arracuda/purchase-approve-signature-print-20250801.git

echo.
echo Step 3: Create .gitignore file
echo Creating .gitignore...

echo.
echo Step 4: Add all files to staging
git add .

echo.
echo Step 5: Create initial commit
git commit -m "Initial commit: Laravel Purchase Order Approval System with Digital Signatures"

echo.
echo Step 6: Push to GitHub (you will need to authenticate)
echo Note: You may need to enter your GitHub username and personal access token
git branch -M main
git push -u origin main

echo.
echo ========================================
echo ADDITIONAL GIT COMMANDS
echo ========================================
echo.

echo Step 7: Check current status
git status

echo.
echo Step 8: Add changes to staging
git add .

echo.
echo Step 9: Commit changes to current branch
set /p commit_message="Enter commit message: "
git commit -m "%commit_message%"

echo.
echo Step 10: Push to current branch
git push origin HEAD

echo.
echo ========================================
echo Upload completed!
echo Repository URL: https://github.com/l3arracuda/purchase-approve-signature-print-20250801.git
echo Current Branch: 
git branch --show-current
echo ========================================
pause
