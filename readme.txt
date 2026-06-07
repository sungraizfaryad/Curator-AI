=== Curator AI — SEO & Site Care ===
Contributors: sungraizfaryad
Tags: ai, seo, content, audit, accessibility
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Keep your WordPress site healthy with SEO writing help, content freshness checks, and quality audits, all in one place.

== Description ==

Curator AI is a free plugin that helps you take care of your WordPress site without turning it into a second job. It writes the small bits of SEO text that matter, points out posts that have gone stale, and runs quality checks so you can fix problems before your visitors (or Google) notice them.

It is built for the person who runs the site, not just the developer who set it up. If you have ever looked at a post and wondered whether the headline Google shows is any good, whether your older articles are quietly aging, or whether any of your images are missing their descriptions, this plugin does that looking for you and tells you plainly what it found.

You work with it in two places. Right inside the post editor there is a "Curator AI" sidebar where you can generate a meta title or meta description, score a post's readability, refresh a stale post, or scan it for broken links. And there is a dashboard under "Curator AI" in your admin area with five simple screens: Overview, Automation, Audit Reports, Bulk Operations, and Settings.

Here is what it can do, in three groups.

**SEO writing help**

* Write a meta title, the clickable headline that shows up in Google search results.
* Write a meta description, the short summary that sits under that title in Google.
* Write alt text for an image, the description that helps screen readers and search engines understand the picture.
* If you use Yoast SEO or Rank Math (two popular SEO plugins), it writes these straight into them. If you use neither, it just stores them for you.

**Keeping content fresh**

* Find stale posts, the ones you have not touched in a long time.
* Refresh a post by simply updating its date, or by rewriting the parts that have gone out of date.

**Site health checks** (these run on your own server, no outside help needed)

* Give a post a readability score so you know how easy it is to read.
* Find posts that are missing a meta title or meta description.
* Find images that have no alt text.
* Find thin content, meaning posts that are too short to be useful.
* Check a post for broken links.
* Measure page speed and Core Web Vitals, which are Google's measures of how fast and steady a page feels, using Google PageSpeed Insights. This one is optional and sends the page address to Google to run the test.

A quick, honest note on what needs help from elsewhere. The four AI writing jobs (the three SEO text features plus rewriting a stale post) need the free official "AI" plugin from WordPress.org. That separate plugin is where you connect your provider, such as OpenAI, Anthropic, or Google, and where your API key lives. Curator AI never holds or touches your API key. Everything else, including all the audits and the simple date refresh, works fine without the AI plugin.

On privacy, plainly: Curator AI holds no API keys. The AI writing features send your post content to whatever provider you set up in the AI plugin, and that only happens when you click a button to ask for it. The optional page speed check sends the page address to Google. Every other audit runs on your own server and sends nothing out.

One more thing worth knowing. The automation rules, which can run tasks when you save a post or on a schedule, are all turned off when you install. Nothing runs on its own until you decide to switch it on.

This plugin needs WordPress 7.0 or newer and PHP 8.1 or newer.

== External services ==

This plugin can connect to two outside services, and only when you choose to use the features that need them.

* WordPress AI Client. The AI writing features pass your prompt and the relevant post content to the AI provider you set up in the official "AI" plugin (for example OpenAI, Anthropic, or Google). This happens only when you trigger an AI feature. Curator AI does not store or send any API key of its own.
* Google PageSpeed Insights. The optional page speed audit sends the address of the page you are testing to Google so Google can measure how fast it loads. No post content is sent.

== Installation ==

1. Install and activate Curator AI from the Plugins screen in your WordPress admin.
2. Open any post and look for the "Curator AI" sidebar to try the audits and SEO tools right away.
3. If you want the AI writing features, install the free "AI" plugin by WordPress.org, then open it and connect your provider and API key. Curator AI will use that connection automatically.
4. Visit the "Curator AI" menu in your admin area to see the full dashboard.
5. To have tasks run automatically, open the Automation screen and turn on the rules you want. Every rule is off by default.

== Frequently Asked Questions ==

= Do I need an API key? =

Not for Curator AI itself, and it never stores one. The AI writing features use the separate free "AI" plugin, and that is where you add your provider and key.

= Will this work if I do not use Yoast or Rank Math? =

Yes. If you have Yoast or Rank Math, Curator AI writes the SEO text straight into them. If you do not, it simply stores the text itself.

= Does any of my content get sent somewhere? =

Only when you ask for an AI writing feature. Then your post content goes to the provider you set up in the AI plugin. The optional page speed check sends the page address to Google. Everything else stays on your own server.

= Is it safe to install? Will it change my site on its own? =

Yes, it is safe, and no, it will not act on its own. Every automation rule is off until you turn it on, so nothing happens until you choose to make it happen.

= Can I use it without any AI at all? =

Absolutely. The site health checks and the stale-post finder work with no AI plugin and no provider. You only need the AI plugin if you want Curator AI to write text or rewrite a post for you.

== Changelog ==

= 1.0.0 =
* Initial release.
* 10 registered abilities: generate-meta-title, generate-meta-description, generate-alt-text, refresh-content, audit-stale, audit-readability, audit-missing-meta-alt, audit-thin-content, audit-broken-links, audit-perf.
* SEO adapters for Yoast SEO, Rank Math, plus a native fallback.
* Automation engine with hook-based and scheduled rule support (all off by default).
* REST API under curator-ai/v1.
* Gutenberg sidebar with SEO, Readability, Freshness, and Broken Links panels.
* Admin dashboard with Overview, Automation, Audit Reports, Bulk Operations, and Settings views.
