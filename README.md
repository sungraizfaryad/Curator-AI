# Curator AI — SEO & Site Care

Keep your WordPress site healthy without the busywork.

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%202.0%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress 7.0+](https://img.shields.io/badge/WordPress-7.0%2B-21759b.svg)](https://wordpress.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/)
[![Version 1.0.0](https://img.shields.io/badge/Version-1.0.0-green.svg)](https://github.com/sungraizfaryad/Curator-AI)
[![WordPress.org plugin](https://img.shields.io/badge/WordPress.org-Plugin%20Page-0073aa.svg)](https://wordpress.org/plugins/curator-ai-seo-site-care/)

## 🌱 What this is

Keeping a WordPress site healthy is a lot of small chores. You have to write the headline Google shows, the little summary under it, and a description for every image. Old posts go stale. Links rot. Some posts end up too short to rank. None of it is hard on its own, but it piles up, and it is easy to let it slide.

Curator AI takes those chores off your plate. It writes the SEO text for you, points out the posts that have gone stale, and runs quick checks on your content so you know what needs fixing. Some of the writing is done by AI when you ask for it. The rest just reads your own content and reports back. You stay in control the whole time, and nothing runs on its own unless you turn it on.

You do not need to be technical to use it.

## ✨ What it does

Ten things in total, grouped into three areas.

### SEO writing help (uses the free AI plugin)

| Feature | What you get |
| --- | --- |
| Generate a meta title | The clickable headline that shows in Google search results |
| Generate a meta description | The short summary that sits under that headline in Google |
| Generate image alt text | The sentence that describes an image for screen readers and search engines |

If you use Yoast SEO or Rank Math (two popular SEO plugins), Curator AI writes these straight into them. If you use neither, it stores them itself so nothing is lost.

> Example: you finish a blog post, click "Generate Meta Title," and get back something like "5 Easy Ways to Repot a Houseplant Without Killing It," ready to use.

### Keeping content fresh

| Feature | What you get |
| --- | --- |
| Find stale posts | A list of the posts you have not updated in a long time |
| Refresh a post | Bump the date so it reads as recent, or rewrite the parts that have gone out of date |

Bumping the date works on its own. Rewriting the content uses the AI plugin.

### Site health checks (no AI needed)

These all run on your own server and just report what they find.

| Check | What it tells you |
| --- | --- |
| Readability score | How easy a post is to read |
| Missing meta | Posts with no meta title or meta description |
| Missing alt text | Images with no description |
| Thin content | Posts that are too short to be useful |
| Broken links | Links in a post that no longer work |
| Page speed | How fast a page loads, plus Core Web Vitals (Google's score for how fast and steady a page feels), measured by Google PageSpeed Insights |

## 📋 Requirements

- WordPress 7.0 or newer
- PHP 8.1 or newer
- For the AI writing features only: the official free **AI** plugin by WordPress.org (plugin slug: `ai`). That plugin is where you connect your provider (OpenAI, Anthropic, Google, and others) and enter your API key.

The audit and freshness checks work without the AI plugin. Only the three SEO writing features and the post rewrite need it. Curator AI itself never stores or handles any API keys.

## 🚀 Getting started

1. Install and activate Curator AI from the Plugins screen in your WordPress admin.
2. Open any post in the editor and look for the **Curator AI** sidebar. From there you can generate a meta title or meta description, score readability, refresh the post, or scan it for broken links.
3. Want the AI writing features? Install the free **AI** plugin by WordPress.org, then open it and connect your provider and API key. Curator AI will use that connection.
4. For site-wide work, open the **Curator AI** menu in your admin area. It has five screens: Overview, Automation, Audit Reports, Bulk Operations, and Settings.
5. To have tasks run automatically, open the **Automation** screen and switch on the rules you want. Every rule is off when you install, so nothing happens until you turn it on. You can have work run when you save a post or on a schedule.

## 🔒 Privacy

This part matters, so here it is in plain terms.

Curator AI holds no API keys. It never sees them and never stores them.

The AI features send your post content to whichever provider you set up in the AI plugin (OpenAI, Anthropic, Google, or another). That is the only time your content leaves your site through this plugin, and it only happens when you click a button to ask for it.

The optional page speed check sends the page address (not your content) to Google.

Every other check runs on your own server and sends nothing out. So unless you trigger an AI action or the page speed check, your content stays put.

## 🛠️ For developers

Curator AI is built around the WordPress 7.0 AI Client and Abilities APIs, so every action is a registered ability that the editor, the dashboard, and the REST API all share.

- **REST API.** Routes live under the `curator-ai/v1` namespace (abilities, audit results, settings, and a status endpoint). Every route has a capability check.
- **SEO adapters.** Writing meta values into a given SEO plugin goes through `CURAI_SEO_Adapter_Interface`. Yoast, Rank Math, and a native fallback ship in the box. To support another SEO plugin (AIOSEO, SEOPress, or your own), implement the interface and register it with the `curai_seo_adapters` filter.
- **Scheduling.** Background and automated work runs on WordPress native cron in 1.0.0. The `curai_scheduler` filter lets you swap in your own scheduler (for example, Action Scheduler) without touching the rest of the plugin.
- **Naming.** Function and option prefix is `curai_`, class and constant prefix is `CURAI_`, and the text domain is `curator-ai-seo-site-care`.
- **AI dependency.** The four AI features (meta title, meta description, alt text, and post rewrite) call through the official WordPress.org `ai` plugin, which owns the provider connection and the key. The audits and the date-only refresh need none of that.

## License

Curator AI — SEO & Site Care is free software under the GPL-2.0-or-later license. Built by Sungraiz.

## Links

- WordPress.org plugin page: https://wordpress.org/plugins/curator-ai-seo-site-care/
- GitHub repository: https://github.com/sungraizfaryad/Curator-AI
