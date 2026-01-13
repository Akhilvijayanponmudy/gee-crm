# Merge Conflict Resolution Guide

## Current Situation
- **Branch**: `feature/update-segments` 
- **Target**: `main`
- **Status**: Merge conflicts detected in 15+ files

## Step-by-Step Resolution

### Step 1: Stash Local Changes
```bash
cd /Users/akhilvijayan/Local\ Sites/crm/app/public/wp-content/plugins/gee-marketing-crm
git stash push -m "Stash before merge" ../../../../../logs/php/error.log
```

### Step 2: Switch to Main Branch
```bash
git checkout main
git pull origin main
```

### Step 3: Attempt Merge
```bash
git merge feature/update-segments
```

### Step 4: Resolve Conflicts

The following files have conflicts:
- `assets/css/admin.css`
- `assets/js/admin.js`
- `gee-woo-crm.php`
- `includes/class-gee-woo-crm-activator.php`
- `includes/class-gee-woo-crm-admin.php`
- `includes/class-gee-woo-crm-ajax.php`
- `includes/models/class-gee-woo-crm-campaign.php`
- `includes/models/class-gee-woo-crm-contact.php`
- `includes/models/class-gee-woo-crm-segment.php`
- `includes/models/class-gee-woo-crm-tag.php`
- `includes/views/campaigns.php`
- `includes/views/contacts.php`
- `includes/views/email-templates.php`
- `includes/views/segments.php`
- `includes/views/settings.php`
- `includes/views/tags.php`

### Option A: Accept Feature Branch (Recommended)
If `feature/update-segments` has all the latest changes:

```bash
# Accept feature branch version for all conflicted files
git checkout --theirs assets/css/admin.css
git checkout --theirs assets/js/admin.js
git checkout --theirs gee-woo-crm.php
git checkout --theirs includes/class-gee-woo-crm-activator.php
git checkout --theirs includes/class-gee-woo-crm-admin.php
git checkout --theirs includes/class-gee-woo-crm-ajax.php
git checkout --theirs includes/models/class-gee-woo-crm-campaign.php
git checkout --theirs includes/models/class-gee-woo-crm-contact.php
git checkout --theirs includes/models/class-gee-woo-crm-segment.php
git checkout --theirs includes/models/class-gee-woo-crm-tag.php
git checkout --theirs includes/views/campaigns.php
git checkout --theirs includes/views/contacts.php
git checkout --theirs includes/views/email-templates.php
git checkout --theirs includes/views/segments.php
git checkout --theirs includes/views/settings.php
git checkout --theirs includes/views/tags.php

# Stage all resolved files
git add assets/css/admin.css assets/js/admin.js gee-woo-crm.php includes/

# Complete the merge
git commit -m "Merge feature/update-segments into main - resolved conflicts"
```

### Option B: Manual Resolution
For each conflicted file:
1. Open the file in your editor
2. Look for conflict markers:
   - `<<<<<<< HEAD` (main branch)
   - `=======` (separator)
   - `>>>>>>> feature/update-segments` (feature branch)
3. Choose which version to keep or merge both
4. Remove the conflict markers
5. Save the file
6. Stage: `git add <filename>`

### Step 5: Complete Merge
```bash
git commit -m "Merge feature/update-segments into main"
```

### Step 6: Push to Remote
```bash
git push origin main
```

## Quick Script (Option A - Accept Feature Branch)

Save this as `resolve-conflicts.sh` and run it:

```bash
#!/bin/bash
cd /Users/akhilvijayan/Local\ Sites/crm/app/public/wp-content/plugins/gee-marketing-crm

# Stash local changes
git stash push -m "Stash before merge" ../../../../../logs/php/error.log

# Switch to main
git checkout main
git pull origin main

# Start merge
git merge feature/update-segments

# Accept feature branch for all conflicts
git checkout --theirs assets/css/admin.css
git checkout --theirs assets/js/admin.js
git checkout --theirs gee-woo-crm.php
git checkout --theirs includes/class-gee-woo-crm-activator.php
git checkout --theirs includes/class-gee-woo-crm-admin.php
git checkout --theirs includes/class-gee-woo-crm-ajax.php
git checkout --theirs includes/models/class-gee-woo-crm-campaign.php
git checkout --theirs includes/models/class-gee-woo-crm-contact.php
git checkout --theirs includes/models/class-gee-woo-crm-segment.php
git checkout --theirs includes/models/class-gee-woo-crm-tag.php
git checkout --theirs includes/views/campaigns.php
git checkout --theirs includes/views/contacts.php
git checkout --theirs includes/views/email-templates.php
git checkout --theirs includes/views/segments.php
git checkout --theirs includes/views/settings.php
git checkout --theirs includes/views/tags.php

# Stage all files
git add assets/ gee-woo-crm.php includes/

# Complete merge
git commit -m "Merge feature/update-segments into main - resolved conflicts by accepting feature branch"

echo "Merge complete! Review changes and push with: git push origin main"
```

## Notes
- The feature branch appears to have the latest code with all recent fixes
- All files in the feature branch should be accepted
- After merging, test the plugin to ensure everything works

