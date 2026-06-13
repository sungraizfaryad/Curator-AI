# Curator AI — working notes for this folder

Read this first when you open this plugin. It saves you from re-reading source to orient.

## 1. What this plugin is

- **WP.org name:** Curator AI — SEO & Site Care
- **Folder:** `curator-ai` (this dir)
- **WP.org slug / text domain:** `curator-ai-seo-site-care`
- **PHP prefixes:** functions/options `curai_`, classes/constants `CURAI_`, REST namespace `curator-ai/v1`
- **Main file:** `curator-ai.php` (not named after the slug — see mines)
- **GitHub:** https://github.com/sungraizfaryad/Curator-AI
- **Live page:** https://wordpress.org/plugins/curator-ai-seo-site-care/

AI-powered site care for WordPress 7.0. It generates SEO meta titles, meta descriptions, and image alt text, refreshes stale posts, and runs site audits (readability, missing meta, thin content, broken links, PageSpeed). Everything is a registered ability on the WP 7.0 Abilities API, called through the AI Client API. The editor sidebar, admin dashboard, and REST API all share those abilities. The four AI writing features require the official `ai` plugin; audits and the date-only refresh need nothing extra.

## 2. Repo layout

This folder IS the canonical git repo. `.git/` lives here, remote `origin` points at GitHub. There is no separate canonical copy at `~/Local Sites/plugins/curator-ai/` — do not create one. This Local site (`media-usage-inspector`) is also the dev/test install, so editing here is editing the repo.

The WP.org build is deployed via SVN using the shared script at `~/Local Sites/plugins/deploy.sh` (guide: `~/Local Sites/plugins/DEPLOY.md`). Dev files (`composer.json`, `phpcs.xml`, `phpunit.xml`, `tests/`, `vendor/`) are stripped from SVN trunk and the tag after deploy. No build zip is produced into a fixed path; the SVN working copy is checked out fresh under `/tmp` at deploy time.

## 3. Don't trip these mines

- **Slug rename left three names out of sync, on purpose.** The plugin was renamed to satisfy WP.org review, so the WP.org slug/text-domain is `curator-ai-seo-site-care` while the folder is `curator-ai`, the main file is `curator-ai.php`, internal prefixes stayed `curai_`/`CURAI_`, and the REST namespace stayed `curator-ai/v1`. This mismatch is intentional and WP.org accepted it. Do NOT "tidy" it by renaming prefixes, the namespace, or the file.
- **`deploy.sh` assumes the main file matches the slug.** It defaults `default_mainfile="$PLUGINSLUG.php"`, which would look for `curator-ai-seo-site-care.php`. Our main file is `curator-ai.php`. Override the main-file prompt when deploying.
- **Admin menu URL changed with the rename.** Top-level page slug is now `admin.php?page=curator-ai-seo-site-care` (was `curator-ai`). Submenus are still `curator-ai-automation`, `curator-ai-audit`, `curator-ai-bulk`, `curator-ai-settings`.
- **Ability categories must register before abilities.** `class-curai-ability-registrar.php` hooks `register_categories` on `wp_abilities_api_categories_init` and `register_all` on `wp_abilities_api_init`. Registering an ability before its category exists fails silently (fix 53ecda6). Keep that order.
- **AI provider detection uses the builder, not a naive class check.** Detection goes through `is_supported_for_text_generation` on the AI Client builder (fix 66c968f). Don't swap it for a `class_exists` guess.
- **i18n loader is intentionally empty.** `class-curai-i18n.php` does not call `load_plugin_textdomain()`; WP 4.6+ auto-loads translations for WP.org-hosted plugins. The empty method is deliberate (Plugin Check fix e35c758). Don't "restore" the call.
- **`skip_if_exists` is not evaluated in the rule engine.** `class-curai-rule-engine.php` leaves that check to the listener, because it needs the current stored value. If you add a new dispatch path, you must run the skip check yourself.
- **SVN page renders from the stable tag's readme, not trunk.** When you change `readme.txt`, commit it to BOTH `trunk/` and `tags/<stable>/` or the public page won't update.
- **`Requires Plugins: ai` blocks activation.** Curator AI will not activate unless the official `ai` plugin is active. If the plugin looks "gone" on the test site, check that `ai` is active first.

## 4. How to develop here (Local by Flywheel)

WP-CLI against this Local site. PHP path and run-id are dynamic, so resolve them each session:

```bash
PHP="/Users/sungraizfaryad/Library/Application Support/Local/lightning-services/php-8.4.18+1/bin/darwin-arm64/bin/php"
WP="/opt/homebrew/Cellar/wp-cli/2.12.0/bin/wp"
RUN="/Users/sungraizfaryad/Library/Application Support/Local/run"
SITE_RID() { grep -rl "$1" "$RUN"/*/conf 2>/dev/null | sed -E "s#$RUN/([^/]+)/.*#\1#" | sort -u | head -1; }
SOCK="$RUN/$(SITE_RID media-usage-inspector)/mysql/mysqld.sock"
$PHP -d mysqli.default_socket="$SOCK" -d pdo_mysql.default_socket="$SOCK" \
    $WP --path="/Users/sungraizfaryad/Local Sites/media-usage-inspector/app/public" plugin list
```

If the `php-8.4.18+1` directory no longer exists, list `…/lightning-services/` and pick the current `php-*` build.

Browser testing (Playwright) auto-login as admin, no password, http only:

`http://media-usage-inspector.local/wp-admin/?localwp_auto_login=1`

Ability permission callbacks need a logged-in user, so under `wp eval`/CLI call `wp_set_current_user( 1 )` first or audits return `WP_Error`.

## 5. Architecture map

Bootstrap
- `curator-ai.php` — header, constants, environment gate, activation/deactivation hooks
- `includes/class-curai-plugin.php` — singleton orchestrator, wires everything
- `includes/class-curai-activator.php` / `class-curai-deactivator.php` — create tables + defaults / clear transients + scheduled jobs
- `includes/class-curai-i18n.php` — text-domain hook stub (intentionally empty, see mines)
- `includes/compat/class-curai-environment-check.php` — blocks load on too-old WP/PHP
- `includes/compat/class-curai-ai-client-detector.php` — detects AI Client availability, renders the conditional setup notice

Abilities (`includes/abilities/`)
- `class-curai-ability-registrar.php` — registers all categories then abilities
- `trait-curai-ability-helpers.php` — shared helpers for ability classes
- `class-curai-ability-meta-title.php` / `-meta-description.php` / `-alt-text.php` — AI SEO/alt generation
- `class-curai-ability-refresh-content.php` — refresh post (date_only / context / rewrite)
- `class-curai-ability-audit-stale.php` / `-readability.php` / `-missing-meta-alt.php` / `-thin-content.php` / `-audit-broken-links.php` / `-audit-perf.php` — the six audits

AI (`includes/ai/`)
- `class-curai-ai-bridge.php` — wraps WP 7.0 AI Client for ability calls
- `class-curai-prompt-builder.php` — prompt + system instruction pairs per ability
- `class-curai-cost-guard.php` — tracks monthly tokens + USD, enforces the cap

Audit (`includes/audit/`)
- `class-curai-audit-store.php` — persists findings to `curai_audit_results`
- `class-curai-link-checker.php` — extracts URLs and HEAD-checks reachability
- `class-curai-pagespeed-client.php` — thin Google PageSpeed Insights v5 client
- `class-curai-readability-calc.php` — pure-PHP Flesch-Kincaid + sentence stats

Automation (`includes/automation/`)
- `interface-curai-scheduler.php` — scheduler contract (swappable via `curai_scheduler` filter)
- `class-curai-wp-cron-scheduler.php` — WP cron implementation
- `class-curai-rule-engine.php` — evaluates rules in the `curai_automation_rules` option
- `class-curai-automation.php` — action listeners that dispatch ability runs
- `class-curai-job-runner.php` — cron callbacks that execute scheduled abilities (retry backoff 30s/5m/30m, max 3)
- `class-curai-job-tracker.php` — CRUD for the `curai_jobs` table

SEO adapters (`includes/seo-adapters/`)
- `interface-curai-seo-adapter.php` — adapter contract
- `class-curai-seo-adapter-factory.php` — resolves active adapter, `curai_seo_adapters` filter, `curai_seo_adapter_override` option
- `class-curai-yoast-seo-adapter.php` / `-rank-math-seo-adapter.php` / `-native-seo-adapter.php` — Yoast, Rank Math, native fallback

REST (`includes/rest/`, namespace `curator-ai/v1`)
- `class-curai-rest-controller.php` — registers routes
- `class-curai-rest-abilities.php` / `-audit.php` / `-settings.php` / `-status.php` — the four endpoints (each capability-checked)

Admin + editor
- `includes/admin/class-curai-admin.php` — menu registration + asset enqueue
- `includes/admin/class-curai-admin-actions.php` — nonce-protected `admin_post_*` handlers (cap check then `check_admin_referer` then `$_POST`)
- `includes/admin/views/` — `overview.php`, `settings.php`, etc.
- `includes/editor/class-curai-editor-assets.php` — enqueues the Block Editor sidebar bundle (`assets/editor/sidebar.js`, vanilla JS, no build step)

## 6. Tests

PHPUnit 9.6 + Brain Monkey, no WP install required (stubs live in `tests/stubs/`). Run:

```bash
composer test        # alias for: vendor/bin/phpunit
```

## 7. Build & ship

No `.distignore`/`Makefile`/`build-zip.sh` in this folder. WP.org deploy uses the shared SVN script:

```bash
~/Local Sites/plugins/deploy.sh      # prompts for slug, paths, main file
```

Full procedure (trunk/tag/assets, the stable-tag readme gotcha, recovery) is in `~/Local Sites/plugins/DEPLOY.md`. When prompted: slug `curator-ai-seo-site-care`, plugin dir is THIS folder, and override the main-file default to `curator-ai.php` (see mines). Strip `composer.json`, `phpcs.xml`, `phpunit.xml`, `tests/`, `vendor/` from trunk and the tag after copying.

## 8. When in doubt

- Per-session status and recent decisions: `progress.md` in this folder.
- Durable cross-session facts: cloud memory at `~/.claude/projects/-Users-sungraizfaryad-Local-Sites-media-usage-inspector/memory/`, filtered by `project_curator_ai_*` and `reference_curator_ai_*` (plus shared `reference_wp_plugin_svn_deploy_guide`).
- `wp-admin/` and `wp-includes/` are WordPress core. Never edit them.
