# Quick Merge Conflict Resolution Commands

Run these commands in your terminal to resolve all conflicts:

```bash
cd "/Users/akhilvijayan/Local Sites/crm/app/public/wp-content/plugins/gee-marketing-crm"

# Step 1: Remove files from main and restore from feature branch (modify/delete conflicts)
git rm assets/css/admin.css
git checkout feature/update-segments -- assets/css/admin.css

git rm assets/js/admin.js
git checkout feature/update-segments -- assets/js/admin.js

git rm gee-woo-crm.php
git checkout feature/update-segments -- gee-woo-crm.php

git rm includes/class-gee-woo-crm-activator.php
git checkout feature/update-segments -- includes/class-gee-woo-crm-activator.php

git rm includes/class-gee-woo-crm-ajax.php
git checkout feature/update-segments -- includes/class-gee-woo-crm-ajax.php

git rm includes/models/class-gee-woo-crm-campaign.php
git checkout feature/update-segments -- includes/models/class-gee-woo-crm-campaign.php

git rm includes/models/class-gee-woo-crm-contact.php
git checkout feature/update-segments -- includes/models/class-gee-woo-crm-contact.php

git rm includes/models/class-gee-woo-crm-segment.php
git checkout feature/update-segments -- includes/models/class-gee-woo-crm-segment.php

git rm includes/models/class-gee-woo-crm-tag.php
git checkout feature/update-segments -- includes/models/class-gee-woo-crm-tag.php

git rm includes/views/campaigns.php
git checkout feature/update-segments -- includes/views/campaigns.php

git rm includes/views/contacts.php
git checkout feature/update-segments -- includes/views/contacts.php

git rm includes/views/segments.php
git checkout feature/update-segments -- includes/views/segments.php

git rm includes/views/settings.php
git checkout feature/update-segments -- includes/views/settings.php

git rm includes/views/tags.php
git checkout feature/update-segments -- includes/views/tags.php

# Step 2: Resolve content conflicts (accept feature branch version)
git checkout --theirs includes/class-gee-woo-crm-admin.php
git checkout --theirs includes/views/email-templates.php

# Step 3: Stage all resolved files
git add assets/ gee-woo-crm.php includes/

# Step 4: Complete the merge
git commit -m "Merge feature/update-segments into main - resolved conflicts by keeping feature branch versions"

# Step 5: Push to remote
git push origin main
```

## Or use the automated script:

```bash
./resolve-merge-conflicts.sh
git add -A
git commit -m "Merge feature/update-segments into main - resolved conflicts"
git push origin main
```

