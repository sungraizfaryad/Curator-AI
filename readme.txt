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

== Changelog ==

= 1.0.0 =
* Initial release.
