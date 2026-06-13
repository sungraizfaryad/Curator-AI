_Last updated: 2026-06-13._ Detail lives in CLAUDE.md (this folder) and cloud memory.

## Done
- v1.0.0 shipped to WordPress.org and pushed to GitHub (`main`).
- 10 abilities: 3 SEO meta/alt generators, refresh-content, 6 audits (stale, readability, missing-meta-alt, thin, broken-links, perf).
- SEO adapters for Yoast, Rank Math, native fallback (factory + `curai_seo_adapters` filter).
- Automation engine: rule engine, WP cron scheduler, listeners, job tracker (all rules off by default).
- REST API under `curator-ai/v1`; Gutenberg sidebar (vanilla JS); admin dashboard (Overview/Automation/Audit/Bulk/Settings).
- Declared `Requires Plugins: ai`; conditional setup notice + getting-started card.
- Renamed to "Curator AI — SEO & Site Care" / slug `curator-ai-seo-site-care` per WP.org review.
- Plain-language README.md + readme.txt; new icon/banner (no AI-tool resemblance); 6 admin screenshots uploaded (SVN r3568845).

## Decisions
- Slug rename did NOT touch internal prefixes (`curai_`/`CURAI_`) or REST namespace (`curator-ai/v1`). Mismatch is intentional, WP.org accepted it. Do not align them.
- Main file stays `curator-ai.php` even though slug differs.
- i18n loader intentionally empty (WP 4.6+ auto-loads WP.org translations).
- Native cron for scheduling in 1.0.0; `curai_scheduler` filter left open for Action Scheduler later.

## Next steps
Awaiting next instruction. Working tree clean on `main`, nothing pending. No TODO/FIXME markers in source.

## Key files
- includes/abilities/class-curai-ability-registrar.php
- readme.txt
- includes/compat/class-curai-ai-client-detector.php
- includes/class-curai-plugin.php
- includes/admin/views/overview.php
- includes/admin/views/settings.php
