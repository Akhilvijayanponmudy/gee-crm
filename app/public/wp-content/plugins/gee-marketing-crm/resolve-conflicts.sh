#!/bin/bash
# Merge Conflict Resolution Script
# This script resolves merge conflicts by accepting the feature branch version

cd "/Users/akhilvijayan/Local Sites/crm/app/public/wp-content/plugins/gee-marketing-crm"

echo "Step 1: Stashing local changes..."
git stash push -m "Stash before merge" ../../../../../logs/php/error.log 2>/dev/null || echo "No changes to stash"

echo "Step 2: Switching to main branch..."
git checkout main 2>&1 || {
    echo "Error: Could not checkout main branch"
    exit 1
}

echo "Step 3: Pulling latest main..."
git pull origin main 2>&1 || echo "Warning: Could not pull from origin"

echo "Step 4: Starting merge..."
git merge feature/update-segments --no-commit --no-ff 2>&1

if [ $? -eq 0 ]; then
    echo "Merge completed without conflicts!"
    git commit -m "Merge feature/update-segments into main"
    echo "✅ Merge successful! Push with: git push origin main"
    exit 0
fi

echo "Step 5: Resolving conflicts by accepting feature branch version..."

# List of conflicted files
CONFLICTED_FILES=(
    "assets/css/admin.css"
    "assets/js/admin.js"
    "gee-woo-crm.php"
    "includes/class-gee-woo-crm-activator.php"
    "includes/class-gee-woo-crm-admin.php"
    "includes/class-gee-woo-crm-ajax.php"
    "includes/models/class-gee-woo-crm-campaign.php"
    "includes/models/class-gee-woo-crm-contact.php"
    "includes/models/class-gee-woo-crm-segment.php"
    "includes/models/class-gee-woo-crm-tag.php"
    "includes/views/campaigns.php"
    "includes/views/contacts.php"
    "includes/views/email-templates.php"
    "includes/views/segments.php"
    "includes/views/settings.php"
    "includes/views/tags.php"
)

# Accept feature branch version for each file
for file in "${CONFLICTED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  Resolving: $file"
        git checkout --theirs "$file" 2>/dev/null
    fi
done

echo "Step 6: Staging resolved files..."
git add assets/ gee-woo-crm.php includes/ 2>&1

echo "Step 7: Completing merge..."
git commit -m "Merge feature/update-segments into main - resolved conflicts by accepting feature branch" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Merge completed successfully!"
    echo ""
    echo "Next steps:"
    echo "  1. Review the changes: git log --oneline -5"
    echo "  2. Test the plugin to ensure everything works"
    echo "  3. Push to remote: git push origin main"
else
    echo ""
    echo "⚠️  Merge commit failed. Check the status:"
    echo "  git status"
    echo "  git diff --cached"
fi

