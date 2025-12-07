# PHP 8.4 Port Progress

**Project:** Vanilla Forums fork for PHP 8.4 compatibility
**Started:** 2025-12-07
**Branch:** `php8.4-port`
**Strategy:** Pain-first method - install on 8.4 immediately and fix all errors

---

## Error #1: Composer Dependencies - Nette Utils PHP 8.4 Incompatibility

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Location:** Composer dependency resolution

### Error Message
```
Problem 1
  - nette/utils is locked to version v3.2.10 and an update of this package was not requested.
  - nette/utils v3.2.10 requires php >=7.2 <8.4 -> your php version (8.4.11) does not satisfy that requirement.

Problem 2
  - nette/robot-loader is locked to version v3.4.2 and an update of this package was not requested.
  - nette/robot-loader v3.4.2 requires nette/utils ^3.0 -> satisfiable by nette/utils[v3.2.10].
  - nette/utils v3.2.10 requires php >=7.2 <8.4 -> your php version (8.4.11) does not satisfy that requirement.
```

### Analysis
- **Locked version:** `nette/utils` v3.2.10 has maximum PHP version constraint `<8.4`
- **Dependency chain:** `nette/robot-loader` v3.4.2 depends on `nette/utils ^3.0`
- **Root cause:** `composer.lock` has outdated package versions from 2018-era development

### Fix Attempted
Need to run `composer update` to get newer versions of Nette packages that support PHP 8.4.

### Commands to Run
```bash
export COMPOSER_ALLOW_SUPERUSER=1
composer update nette/utils nette/robot-loader
```

### Resolution
- Ran `composer update nette/utils nette/robot-loader`
- Composer **downgraded** `nette/utils` from v3.2.10 → v3.1.6 (older version supports PHP 8.4)
- Installed 146 packages total
- 3 packages are abandoned (container-interop, vanilla/legacy-oauth, webmozart/path-util)
- 1 security vulnerability detected (need to audit)

### Notes
- This is a dependency issue, not actual Vanilla code yet
- Nette is a Czech framework used for some Vanilla utilities
- Composer picked an older Nette version that's more compatible - interesting approach!

---

## Error #2: PHP 8.4 Implicit Nullable Parameters (DEPRECATION WARNINGS)

**Date:** 2025-12-07
**Status:** 🟡 DOCUMENTED - Need to fix
**Location:** Multiple files across Garden framework

### Error Messages
```
PHP Deprecated:  Gdn::setContainer(): Implicitly marking parameter $container as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/core/class.gdn.php on line 687

PHP Deprecated:  Vanilla\AddonManager::lookupAsset(): Implicitly marking parameter $addon as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Vanilla/AddonManager.php on line 930

PHP Deprecated:  Garden\Web\PageControllerRoute::__construct(): Implicitly marking parameter $container as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Garden/Web/PageControllerRoute.php on line 27

PHP Deprecated:  Garden\Web\ResourceRoute::__construct(): Implicitly marking parameter $container as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Garden/Web/ResourceRoute.php on line 66

PHP Deprecated:  Garden\Web\ResourceRoute::__construct(): Implicitly marking parameter $classLocator as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Garden/Web/ResourceRoute.php on line 66

PHP Deprecated:  Gdn_Request::requestMethod(): Implicitly marking parameter $method as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/core/class.request.php on line 554

PHP Deprecated:  Vanilla\Logger::addLogger(): Implicitly marking parameter $filter as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Vanilla/Logger.php on line 168

PHP Deprecated:  Vanilla\Utility\ArrayUtils::mergeRecursive(): Implicitly marking parameter $numeric as nullable is deprecated, the explicit nullable type must be used instead in /var/www/html/library/Vanilla/Utility/ArrayUtils.php on line 391
```

### Analysis
- **Root cause:** PHP 8.4 removed support for implicit nullable parameters with default `null` values
- **What changed:** Old code: `function foo($bar = null)` → New code: `function foo(?$bar = null)`
- **Impact:** Currently deprecation warnings (won't break site), but will become fatal errors in PHP 9.0
- **Files affected:** 8+ files in the Garden framework core

### Affected Files (Initial Scan)
1. [library/core/class.gdn.php:687](library/core/class.gdn.php#L687) - `Gdn::setContainer()`
2. [library/Vanilla/AddonManager.php:930](library/Vanilla/AddonManager.php#L930) - `AddonManager::lookupAsset()`
3. [library/Garden/Web/PageControllerRoute.php:27](library/Garden/Web/PageControllerRoute.php#L27) - `PageControllerRoute::__construct()`
4. [library/Garden/Web/ResourceRoute.php:66](library/Garden/Web/ResourceRoute.php#L66) - `ResourceRoute::__construct()` (2 parameters)
5. [library/core/class.request.php:554](library/core/class.request.php#L554) - `Gdn_Request::requestMethod()`
6. [library/Vanilla/Logger.php:168](library/Vanilla/Logger.php#L168) - `Logger::addLogger()`
7. [library/Vanilla/Utility/ArrayUtils.php:391](library/Vanilla/Utility/ArrayUtils.php#L391) - `ArrayUtils::mergeRecursive()`

### Fix Strategy
Need to find ALL instances of `= null` parameters without explicit `?` type hint and add them:

```bash
# Search pattern for implicit nullable params
grep -r "function.*\$[a-zA-Z_]* = null" library/ applications/
```

Then convert each occurrence from:
```php
function setContainer($container = null)
```

To:
```php
function setContainer(?Gdn_Container $container = null)
```

### Notes
- This is the FIRST real PHP 8.4 compatibility issue in actual Vanilla code
- Deprecation warnings only - site may still work but logs will be spammed
- Need systematic search-and-replace across entire codebase
- Expected 100+ occurrences based on 2008-era PHP coding style

---

## Setup Issues Encountered (Infrastructure)

### Issue #3: Missing .htaccess File (404 Errors)

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Symptom:** All URLs showed 404 Not Found, site redirected to `/dashboard/setup` but page didn't exist

**Root Cause:**
- `.htaccess` file was gitignored and not present in the repository
- Apache couldn't rewrite URLs without it
- Pretty URLs like `/dashboard/setup` failed because they're not real files

**Solution:**
Created `.htaccess` with URL rewriting rules:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```

**What This Does:**
1. Checks if requested URL is NOT a real directory (`!-d`)
2. Checks if requested URL is NOT a real file (`!-f`)
3. If both true, sends request to `index.php` which processes the URL

**File Location:** `/var/www/html/.htaccess`

---

### Issue #4: File Permission Errors (403 Forbidden / Write Failures)

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Symptom:**
- Initial: `403 Forbidden - unable to read htaccess file`
- Later: `Unable to write to config file when saving`

**Root Cause:**
- Files created by root user, but Apache runs as `www-data`
- Apache couldn't read `.htaccess` (owned by root)
- Vanilla couldn't write to `conf/config.php` during setup

**Solution:**
Fixed all file ownership and permissions:
```bash
chown -R www-data:www-data /var/www/html/
find /var/www/html/ -type d -exec chmod 755 {} \;
find /var/www/html/ -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/html/cache /var/www/html/conf /var/www/html/uploads
```

**Permission Breakdown:**
- **Directories:** 755 (rwxr-xr-x) - Owner can write, everyone can read/browse
- **Files:** 644 (rw-r--r--) - Owner can write, everyone can read
- **Writable dirs** (cache/conf/uploads): 775 (rwxrwxr-x) - Owner+group can write

**Why This Matters:**
- Apache runs as user `www-data`
- If files owned by `root`, Apache can't read/write them
- Cache and config directories MUST be writable for Vanilla to function

---

### Issue #5: Database Connection Errors

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Symptom:** `php_network_getaddresses: getaddrinfo for database failed`

**Root Cause:**
- Default config tried to connect to hostname `database` (Docker-style)
- No such hostname exists on standalone server
- Database credentials were missing

**Solution:**
Created `/var/www/html/conf/config.php` with correct credentials:
```php
$Configuration['Database']['Host'] = 'localhost';
$Configuration['Database']['Name'] = 'vanilla';
$Configuration['Database']['User'] = 'vanilla_user';
$Configuration['Database']['Password'] = 'As044200';
```

**Database Setup** (for reference):
```sql
CREATE DATABASE vanilla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vanilla_user'@'localhost' IDENTIFIED BY 'As044200';
GRANT ALL PRIVILEGES ON vanilla.* TO 'vanilla_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### Issue #6: Missing Vanilla Application (Class Not Found)

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Symptom:** `Class Vanilla\Forum\Draft\ScheduledDraftService does not exist`

**Root Cause:**
- Vanilla application (forum functionality) was not enabled in config
- Only Dashboard and Conversations apps were enabled by default
- Classes in `/applications/vanilla/` couldn't be loaded

**Solution:**
Added to `config.php`:
```php
$Configuration['EnabledApplications']['Vanilla'] = 'vanilla';
```

**What This Does:**
- Enables the forum application (discussions, comments, categories)
- Loads classes from `/applications/vanilla/` directory
- Vanilla Forums has 3 main applications:
  - **Dashboard:** Admin interface
  - **Conversations:** Private messages
  - **Vanilla:** Forum discussions (main app)

---

### Issue #7: Incompatible Plugins (QnA, Ideation)

**Date:** 2025-12-07
**Status:** ✅ FIXED (Disabled)
**Symptom:** `Class Vanilla\Forums\Modules\DiscussionTabFactory not found`

**Root Cause:**
- QnA (Questions & Answers) plugin expects classes that don't exist in Vanilla 2025
- Built for older Vanilla 2018 version
- Plugin tries to load during bootstrap and crashes

**Solution:**
Disabled incompatible plugins in `config.php`:
```php
$Configuration['EnabledPlugins']['QnA'] = false;
$Configuration['EnabledPlugins']['ideation'] = false;
```

**Notes:**
- These plugins will need to be updated for PHP 8.4 compatibility later
- For now, focusing on getting core Vanilla working
- Can re-enable and fix plugins one-by-one after core is stable

---

### Issue #8: Cache Corruption

**Date:** 2025-12-07
**Status:** ✅ FIXED
**Symptom:** Old plugin settings persisted even after disabling

**Root Cause:**
- Vanilla caches addon/plugin configurations in `/cache/` directory
- Cached data showed QnA as enabled even after we disabled it
- Cache wasn't automatically invalidated when config changed

**Solution:**
```bash
rm -rf /var/www/html/cache/*
```

**When to Clear Cache:**
- After changing enabled plugins/applications
- After updating configuration
- When seeing weird "class not found" errors
- After major code changes

**Cache Directory:**
- Location: `/var/www/html/cache/`
- Must be writable by www-data (775 permissions)
- Automatically recreated by Vanilla when needed

---

## Current Status: Site Running! 🎉

**As of:** 2025-12-07
**URL:** http://10.0.1.4/
**Status:** ✅ Successfully loading, showing "Site not installed" page

### What's Working:
- ✅ Apache + PHP 8.4 serving requests
- ✅ URL rewriting (.htaccess working)
- ✅ Database connection established
- ✅ Composer dependencies installed
- ✅ Core Vanilla applications enabled
- ✅ File permissions correct

### What's Next:
- 🔄 Enable PHP error display on website (for debugging)
- 🔄 Run Vanilla installation
- 🔄 Fix PHP 8.4 deprecation warnings
- 🔄 Test forum functionality

---

## Statistics

- **Total Issues Found:** 8 (1 dependency, 1 code, 6 infrastructure)
- **Issues Fixed:** 7
- **Blockers Remaining:** 0
- **Deprecation Warnings:** 8+ (implicit nullable params)
- **Package Warnings:** 3 (abandoned packages)
- **Site Status:** ✅ Running on PHP 8.4!

---

## Next Steps

1. ✅ ~~Update Nette dependencies to PHP 8.4-compatible versions~~
2. ✅ ~~Complete `composer install`~~
3. ✅ ~~Visit site and capture first PHP 8.4 errors~~
4. **NOW:** Commit initial progress to GitHub (`php8.4-port` branch)
5. Fix implicit nullable parameter deprecations systematically
6. Check security vulnerability: `composer audit`
7. Continue testing and fixing errors
