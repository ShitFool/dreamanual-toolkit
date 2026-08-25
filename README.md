# Dreamanual Toolkit

Modular WordPress toolbox that consolidates scattered micro-plugins into one plugin. Every module can be toggled independently; disabled modules add zero overhead.

## Why this plugin exists

WordPress sites end up with too many tiny plugins: back-to-top buttons, maintenance mode, featured image management... Each does one thing, yet registers its own hooks, loads its own assets, and clutters the plugins list. Dreamanual Toolkit gathers these small features into standalone modules under a single plugin, greatly reducing the number of active plugins.

## Modules

| Module | What it does | Replaces |
| ------ | ------------- | -------- |
| AI Optimizer | AI-powered tag generation with batch processing and multiple AI model support | WPJAM tag optimization |
| Content Visibility | Per-category content visibility with per-channel hiding and role bypass | WPJAM content restriction |
| Role Manager | Fine-grained WordPress role & capability editing | WPJAM user management |
| Site Enhance | Back-to-top button, maintenance mode, featured image filters, default featured image, quick-edit excerpt, comment avatar, SMTP mail | Various single-purpose plugins |
| Site Optimize | 16 site optimization toggles, Chinese typography, admin ad blocker, plus Speculative Loading | WPJAM feature blocking |
| Search Push | Auto-submits post links to Baidu and Bing on publish/update | baidu-submit-link |

## Design principles

- **Independent modules**: inactive modules load no code and register no hooks
- **Encrypted storage**: API keys and SMTP passwords are AES-256-CBC encrypted with a key derived from `AUTH_KEY` + `SECURE_AUTH_KEY`; leaked databases cannot reveal plaintext
- **Vanilla JS**: front-end uses Vanilla JS + CSS BEM naming; jQuery is only used where WordPress core requires it (e.g., quick edit)
- **Security**: every AJAX endpoint goes through `check_ajax_referer` + `current_user_can` double verification
- **i18n**: text domain `dreamanual-toolkit`, fully translatable
- **Cache busting**: front-end asset versions are managed automatically with `filemtime()`

## Requirements

- PHP 7.4+
- WordPress 6.4+

## Installation

1. Download the [latest release](https://github.com/ShitFool/dreamanual-toolkit/archive/refs/heads/main.zip) or clone the repository.
2. Upload the `dreamanual-toolkit` folder to `/wp-content/plugins/`.
3. Activate Dreamanual Toolkit on the Plugins page.
4. Go to "DM Toolkit → Module Management" and enable the modules you need.

Or via WP-CLI:

```bash
wp plugin install https://github.com/ShitFool/dreamanual-toolkit/archive/refs/heads/main.zip
```

## Module Details

### AI Optimizer

Automatically generates tags for posts using AI models. Supports DeepSeek, OpenAI and other providers, single-post and batch processing.

- Auto-analysis of post content with tag suggestions
- Custom AI model and API endpoint configuration
- Batch processing queue to avoid timeouts
- Encrypted API key storage

### Content Visibility

Controls which content is visible to which audience.

- Per-category access control with hidden channels (frontend/RSS/REST API/search/sitemap)
- Role bypass: specified logged-in roles can still view restricted content
- Per-post hiding: hide a single post with one click; direct links return 404

### Role Manager

Fine-grained WordPress role and capability management.

- Visual capability matrix
- Create, edit, delete custom roles
- Toggle individual capabilities
- Clone roles with one click

### Site Enhance

Practical front-end and admin enhancements, each sub-feature independently toggled.

- **Back-to-top button**: custom background and icon colors with live preview
- **Maintenance mode**: 503 page, admins unaffected
- **Featured image filter**: filter posts by has/missing featured image
- **Default featured image**: fallback image for posts without one
- **Quick-edit excerpt**: excerpt field inside the posts list quick-edit panel
- **Comment avatar**: replace Gravatar with mirror source (e.g. cn.cravatar.com) for faster loading; set custom default avatar for unregistered commenters
- **SMTP mail**: configurable SMTP server, SSL/TLS support, encrypted password, one-click test email

### Site Optimize

16 optimization toggles plus Chinese typography and admin ad blocker, no code changes needed.

- Disable Emoji, Embed, XML-RPC, REST API, etc.
- Disable post revisions and autosave
- Remove the WordPress version number and redundant head tags
- Disable the block widget editor
- Disable admin email verification
- Speculative Loading: preload link targets using the browser Speculation Rules API
- **Chinese Typography**: auto-spacing between Chinese and Latin/digits, text justify, smart quotes, paragraph indent
- **Admin Ad Blocker**: hide third-party plugin upsell and ad banners with editable CSS selector rules

### Search Push

Automatically submits post links to search engines at publish/update for better indexing.

- **Baidu push**: Baidu's regular submission API, with configurable site domain and token (from Baidu Search Resource Platform)
- **Bing push**: Bing Webmaster API, supports single and batch submission
- 30-second delayed push after publishing, so publishing is never blocked
- Auto-migrates settings from the legacy `baidu-submit-link` plugin on first activation

## Uninstall

Deactivating and deleting the plugin runs `uninstall.php`, which cleans up all module options from the database - no residue left behind.

## Directory Structure

```
dreamanual-toolkit/
├── dreamanual-toolkit.php    # Plugin entry
├── uninstall.php             # Cleanup on uninstall
├── includes/
│   ├── class-core.php        # Core module scheduler
│   ├── class-module.php      # Module base class
│   └── class-ai-client.php   # AI client + encryption tools
├── modules/
│   ├── ai-optimizer/
│   ├── content-visibility/
│   ├── role-manager/
│   ├── site-enhance/
│   ├── site-optimize/
│   └── search-push/
├── assets/                   # Global assets
└── languages/                # Translation files
```

## Changelog

### 1.4.0
* Site Optimize: added Chinese Typography group (auto-spacing, text justify, smart quotes, paragraph indent)
* Site Optimize: added Admin Ad Blocker group with editable CSS selector rules
* Site Enhance: added Comment Avatar optimization (Gravatar mirror, custom default avatar)
* Fixed missing `drea-toggle__input` class on Site Optimize settings page checkboxes

### 1.3.0
* Full internationalization: all user-facing PHP strings converted to English source
* Added `zh_CN.po` translation file (364 translated entries)
* Refreshed `.pot` translation template (369 unique entries)
* Translated JavaScript hardcoded strings and error log messages
* Plugin defaults to English; Chinese users receive translations via GlotPress or `.po` file

### 1.2.2
* Fixed invalid Terms of Service and Privacy Policy URLs in readme.txt
* Fixed `update_option()` return value check in AI Optimizer module

### 1.2.1
* Replaced inline `<style>` and `<script>` with proper `wp_enqueue` calls
* Added Terms of Service and Privacy Policy links for all external services in readme.txt
* Removed unnecessary `load_plugin_textdomain()` call (auto-loaded since WP 4.6)

### 1.2.0
* Back-to-top button: replaced solid triangle with SVG arrow icon, added icon color customization with live preview
* All module settings pages: save button disabled until form is modified, re-disabled on successful save
* Plugin prepared for WordPress.org submission: .pot translation template, external service declarations, English plugin description

### 1.1.0
* Site Enhance: SMTP password sanitization hardening
* Plugin Check compliance fixes across all modules
* Minor asset and styling refinements

### 1.0.0
* Initial release with 6 modules

## License

GPL-2.0+
