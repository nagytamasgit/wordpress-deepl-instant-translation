# DeepL Translator Widget — WordPress Plugin

> Instant DeepL-powered language switcher for WordPress. Translates page content in real-time via DOM manipulation — no page reload. Inline shortcode, flag + language code dropdown, full style editor in WP Admin. Polylang-compatible.

![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue?logo=wordpress) ![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php) ![License](https://img.shields.io/badge/license-GPL--2.0-green)

---

## Features

- **Real-time translation** — page text is translated instantly without a page reload
- **DeepL API** — uses the official DeepL REST API (Free and Pro keys supported)
- **Inline shortcode** — place the widget anywhere: header, footer, sidebar, nav bar
- **Flag + language code dropdown** — clean, compact UI with animated chevron
- **Full style editor** — configure button and dropdown colors, borders, and border-radius directly in WP Admin, with a live preview
- **Custom strings** — override UI text ("Translating…", "Error") and language names ("English" → "Angol") per installation
- **Transient cache** — translations are cached in WordPress to minimize API calls
- **Theme isolation** — `!important` CSS resets prevent theme styles from bleeding into the widget (tested with Blocksy)
- **Polylang-compatible** — works alongside Polylang without conflicts
- **Accessible** — proper `aria-*` attributes, `role="listbox"`, live region for status messages

---

## Requirements

- WordPress 5.8+
- PHP 8.0+
- A [DeepL API key](https://www.deepl.com/pro-api) (Free or Pro)

---

## Installation

### Via WordPress Admin (recommended)

1. Download the latest `deepl-translator.zip` from [Releases](../../releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Click **Activate Plugin**
5. Go to **Settings → DeepL Translator** and enter your API key

### Manual (FTP / file manager)

1. Unzip `deepl-translator.zip`
2. Upload the `deepl-translator/` folder to `wp-content/plugins/`
3. Activate via **Plugins** in WP Admin
4. Configure at **Settings → DeepL Translator**

---

## Configuration

### Settings → API & Languages

| Field | Description |
|---|---|
| **DeepL API key** | Your DeepL key. Free keys end in `:fx` — the plugin detects this automatically and uses the correct endpoint. |
| **Source language** | The original language of your site (e.g. `HU`) |
| **Target languages** | Which languages visitors can switch to |
| **Cache (hours)** | How long translations are stored in WordPress transients. `0` = no cache. |

### Settings → Widget Style

Customize every visual aspect of the widget without touching CSS:

**Button**
- Background color, text color, border color, border width, border radius
- Hover background

**Dropdown menu**
- Background color, border color, border radius
- Text color, muted text color (language name)
- Row hover background
- Active row background + active text color
- Divider line color

Changes are reflected instantly in the **live preview** panel. You can also set the preview background color to match your actual header for accurate testing.

### Settings → Strings & Language Names

Override any UI string or language display name without editing code:

| Field | Default | Example override |
|---|---|---|
| Translating label | `⏳ Translating…` | `⏳ Fordítás...` |
| Error message | `❌ Translation error` | `❌ Hiba` |
| Aria label | `Language switcher` | `Nyelvválasztó` |
| EN | `English` | `Angol` |
| FR | `Français` | `Francia` |
| … | … | … |

Leave any field empty to keep the default.

---

## Usage

### Shortcode (Gutenberg / Classic Editor)

```
[deepl_translator]
```

### PHP template

```php
<?php echo do_shortcode('[deepl_translator]'); ?>
```

### In a theme builder HTML widget (Blocksy, Elementor, Bricks…)

Paste the shortcode directly into an HTML widget field:

```
[deepl_translator]
```

### Shortcode parameters

| Parameter | Description | Default |
|---|---|---|
| `langs` | Comma-separated target language codes — overrides the admin setting | Admin setting |
| `selector` | CSS selector for the content area to translate | `.entry-content, .page-content, main, article, #content` |

### Examples

```
[deepl_translator]
[deepl_translator langs="EN,FR,DE"]
[deepl_translator langs="EN,FR" selector=".my-content"]
[deepl_translator selector="#main-content, .hero-text"]
```

---

## How it works

```
Visitor clicks a language
        ↓
JS sends POST → /wp-json/deepl-translator/v1/translate
        ↓
PHP checks WordPress transient cache
        ↓ cache miss
DeepL API called → translations returned & cached
        ↓
JS replaces DOM text nodes in-place (no reload)
        ↓
Switching back to original restores text from memory snapshot
```

- Text nodes are collected via `TreeWalker` — `<script>`, `<style>`, `<code>`, and `<pre>` tags are skipped automatically
- Subsequent switches to a previously translated language use the JS-side in-memory cache (zero API calls)
- The transient cache is flushed automatically whenever any post is saved

---

## Supported languages

| Code | Language | Code | Language |
|---|---|---|---|
| EN | English | PL | Polish |
| DE | German | PT | Portuguese |
| FR | French | RU | Russian |
| ES | Spanish | JA | Japanese |
| IT | Italian | ZH | Chinese |
| NL | Dutch | | |

---

## File structure

```
deepl-translator/
├── deepl-translator.php   # Main plugin file (admin + REST API + shortcode + CSS)
└── README.md
```

---

## DeepL API limits

| Plan | Characters / month | Cost |
|---|---|---|
| Free | 500,000 | Free |
| Pro | Unlimited | Pay-as-you-go |

Get your key at [deepl.com/pro-api](https://www.deepl.com/pro-api).

---

## FAQ

**Does it work with page builders (Elementor, Bricks, Divi)?**
Yes. Use the `selector` parameter to target the specific content container your builder outputs.

**Does it affect SEO?**
No. Translation is client-side DOM manipulation — search engines see only the original language.

**Can I use it alongside Polylang?**
Yes. The plugin does not interfere with Polylang's URL-based language switching.

**My theme's styles are breaking the dropdown.**
The plugin uses `!important` on all widget CSS to prevent this. If issues persist, check whether your theme injects styles via JavaScript after page load.

**How do I clear the translation cache?**
Save any post or page — this triggers a full cache flush. You can also set cache hours to `0` to disable caching entirely.

---

## Changelog

### 1.1.0
- Admin style editor with live preview (button + dropdown colors, borders, radius)
- Strings & Language Names panel in admin (override UI text and language names without code changes)
- `!important` CSS isolation for theme builder compatibility (Blocksy, etc.)
- Full English admin UI
- Fixed DeepL API request body format (`text` array was incorrectly nested as objects)

### 1.0.0
- Initial release
- DeepL REST API integration with Free/Pro auto-detection
- `[deepl_translator]` shortcode
- Flag + language code dropdown with animated chevron
- WordPress Transient cache with `save_post` flush

---

## License

[GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.html)

---

Built by [nstudio.hu](https://nstudio.hu)
