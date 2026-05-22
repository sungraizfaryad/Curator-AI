=== Curator AI ===
Contributors: sungraizfaryad
Tags: ai, seo, content, audit, accessibility
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered site care: SEO meta + alt text generation, content freshness, site audits. Native WP 7.0 AI Client.

== Description ==

Curator AI is an AI-powered site care plugin that helps WordPress administrators maintain SEO health, refresh stale content, and audit site quality. It integrates with the WordPress 7.0 AI Client + Abilities API, so any AI provider you configure in Settings → Connectors works automatically.

**Three pillars:**

* **SEO optimization** — generate meta titles, meta descriptions, and image alt text; sync to Yoast SEO or Rank Math
* **Content freshness** — detect stale posts and refresh dates or rewrite outdated content
* **Site health audit** — readability scoring, missing meta/alt detection, broken link checks, thin content flags, Lighthouse-style perf via PageSpeed Insights

All AI calls route through `wp_ai_client_prompt()`. The plugin never stores or transmits its own API keys.

== External services ==

* WordPress AI Client — relays prompts to your configured provider (OpenAI, Anthropic, Google, etc.). Post content is sent to that provider when you trigger AI features.
* Google PageSpeed Insights API (optional) — used by the perf audit to measure Core Web Vitals.

== Installation ==

1. Install and activate the WordPress AI Client plugin, then configure a provider in Settings → Connectors.
2. Install Curator AI.
3. Visit Curator AI → Overview to verify status.
4. Enable any automation rules at Curator AI → Automation (all rules are OFF by default).

== Frequently Asked Questions ==

= Do I need to provide an API key? =

No. All AI calls go through the WordPress AI Client plugin. You configure the provider once in Settings → Connectors, and Curator AI uses whatever model is configured there.

= Does it work without Yoast or Rank Math? =

Yes. A native fallback adapter stores meta titles and descriptions in plugin-owned post meta keys. If you later activate Yoast or Rank Math, the adapter switches automatically, or you can pin a choice in Settings.

= Is anything sent to a third party? =

Only what you trigger. AI calls send post content to your configured AI provider through the WordPress AI Client. The optional performance audit sends the page URL to Google PageSpeed Insights. Audits that score readability, count words, or check links run locally.

= What about background jobs? =

v1 uses WordPress native cron. A future release will bundle Action Scheduler for higher reliability on low-traffic sites. The `curai_scheduler` filter lets you swap in your own scheduler today.

= How do I add support for AIOSEO or SEOPress? =

Implement `CURAI_SEO_Adapter_Interface` and append your adapter via the `curai_seo_adapters` filter.

== Changelog ==

= 1.0.0 =
* Initial release.
* 10 registered abilities: generate-meta-title, generate-meta-description, generate-alt-text, refresh-content, audit-stale, audit-readability, audit-missing-meta-alt, audit-thin-content, audit-broken-links, audit-perf.
* SEO adapters for Yoast SEO, Rank Math, plus a native fallback.
* Automation engine with hook-based and scheduled rule support (all OFF by default).
* REST API under `curator-ai/v1`.
* Gutenberg sidebar with SEO, Readability, Freshness, and Broken Links panels.
* Admin dashboard with Overview, Automation, Audit Reports, Bulk Operations, and Settings views.
