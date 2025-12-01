#!/bin/bash

# QR Attendance System - cPanel Deployment Setup Script
# This script helps you prepare your repository for cPanel deployment

echo "=========================================="
echo "QR Attendance - cPanel Deployment Setup"
echo "=========================================="
echo ""

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo "❌ Error: Not a git repository!"
    echo "Please run this script from the root of your project."
    exit 1
fi

echo "✓ Git repository detected"
echo ""

# Check for required files
echo "Checking required files..."
if [ ! -f .cpanel.yml ]; then
    echo "❌ .cpanel.yml not found!"
    exit 1
fi
echo "✓ .cpanel.yml found"

if [ ! -f .gitignore ]; then
    echo "❌ .gitignore not found!"
    exit 1
fi
echo "✓ .gitignore found"

if [ ! -f .env.example ]; then
    echo "❌ .env.example not found!"
    exit 1
fi
echo "✓ .env.example found"

echo ""
echo "=========================================="
echo "IMPORTANT: Update .cpanel.yml"
echo "=========================================="
echo ""
echo "Before proceeding, make sure you've updated"
echo "YOUR_CPANEL_USERNAME in .cpanel.yml"
echo ""
read -p "Have you updated YOUR_CPANEL_USERNAME? (y/n): " updated

if [ "$updated" != "y" ] && [ "$updated" != "Y" ]; then
    echo ""
    echo "Please update .cpanel.yml with your cPanel username first!"
    echo "Find and replace: YOUR_CPANEL_USERNAME"
    echo ""
    exit 1
fi

echo ""
echo "=========================================="
echo "Git Status"
echo "=========================================="
git status

echo ""
read -p "Add all files to git? (y/n): " add_files

if [ "$add_files" == "y" ] || [ "$add_files" == "Y" ]; then
    echo ""
    echo "Adding files to git..."
    git add .cpanel.yml .gitignore CPANEL_DEPLOYMENT_GUIDE.md
    echo "✓ Files added"
fi

echo ""
read -p "Commit changes? (y/n): " commit_changes

if [ "$commit_changes" == "y" ] || [ "$commit_changes" == "Y" ]; then
    echo ""
    read -p "Enter commit message [Add cPanel deployment configuration]: " commit_msg
    commit_msg=${commit_msg:-"Add cPanel deployment configuration"}
    
    git commit -m "$commit_msg"
    echo "✓ Changes committed"
fi

echo ""
read -p "Push to remote repository? (y/n): " push_changes

if [ "$push_changes" == "y" ] || [ "$push_changes" == "Y" ]; then
    echo ""
    echo "Pushing to origin master..."
    git push origin master
    echo "✓ Pushed to remote"
fi

echo ""
echo "=========================================="
echo "✓ Setup Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Log into your cPanel account"
echo "2. Go to Git™ Version Control"
echo "3. Create a new repository from GitHub"
echo "4. Clone: https://github.com/alfreddcm/Qrattendance.git"
echo "5. Deploy the repository"
echo ""
echo "For detailed instructions, read:"
echo "CPANEL_DEPLOYMENT_GUIDE.md"
echo ""
