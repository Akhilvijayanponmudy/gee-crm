#!/bin/bash
# Resolve merge conflicts for feature/update-segments -> main
# The feature branch has the latest code, so we'll keep those versions

cd "/Users/akhilvijayan/Local Sites/crm/app/public/wp-content/plugins/gee-marketing-crm"

echo "Resolving merge conflicts..."

# For modify/delete conflicts, we need to remove the file from main and restore from feature branch
# For content conflicts, we'll accept the feature branch version

echo "Step 1: Resolving modify/delete conflicts (removing main version, keeping feature branch version)..."

# Remove files that were deleted in feature branch but modified in main
# Then restore them from feature branch
CONFLICTED_DELETED_FILES=(
    "assets/css/admin.css"
    "assets/js/admin.js"
    "gee-woo-crm.php"
    "includes/class-gee-woo-crm-activator.php"
    "includes/class-gee-woo-crm-ajax.php"
    "includes/models/class-gee-woo-crm-campaign.php"
    "includes/models/class-gee-woo-crm-contact.php"
    "includes/models/class-gee-woo-crm-segment.php"
    "includes/models/class-gee-woo-crm-tag.php"
    "includes/views/campaigns.php"
    "includes/views/contacts.php"
    "includes/views/segments.php"
    "includes/views/settings.php"
    "includes/views/tags.php"
)

for file in "${CONFLICTED_DELETED_FILES[@]}"; do
    echo "  Resolving: $file"
    # Remove the file (from main/HEAD)
    git rm "$file" 2>/dev/null
    # Restore from feature branch
    git checkout feature/update-segments -- "$file" 2>/dev/null
done

echo "Step 2: Resolving content conflicts..."

# For content conflicts, accept feature branch version
git checkout --theirs includes/class-gee-woo-crm-admin.php 2>/dev/null
git checkout --theirs includes/views/email-templates.php 2>/dev/null

echo "Step 3: Staging all resolved files..."
git add assets/ gee-woo-crm.php includes/ 2>&1

echo ""
echo "✅ Conflicts resolved!"
echo ""
echo "Review the changes:"
echo "  git status"
echo ""
echo "If everything looks good, complete the merge:"
echo "  git commit -m 'Merge feature/update-segments into main - resolved conflicts'"
echo ""
echo "Then push:"
echo "  git push origin main"

