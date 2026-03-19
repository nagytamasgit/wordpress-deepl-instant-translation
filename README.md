# DeepL Translator Widget – WordPress Plugin

Valós idejű DeepL fordítás shortcode-dal. Polylang-kompatibilis, cache-eléssel.

---

## Telepítés

1. Másold a `deepl-translator/` mappát a WordPress `wp-content/plugins/` könyvtárába
2. Aktiváld az **Bővítmények** menüben
3. Állítsd be: **Beállítások → DeepL Translator**

---

## Beállítás

| Mező | Leírás |
|------|--------|
| **DeepL API kulcs** | Ingyenes vagy Pro kulcs a [DeepL Pro](https://www.deepl.com/pro-api) oldaláról |
| **Forrásnyelv** | Az oldal eredeti nyelve (pl. HU) |
| **Célnyelvek** | Milyen nyelvekre fordítható az oldal |
| **Cache időtartam** | Hány óráig tárolja a fordítást WordPress transient-ben (0 = nincs cache) |

> **Ingyenes DeepL API kulcs** `:fx` végű, pl. `abc123:fx` — a plugin automatikusan felismeri és a `api-free.deepl.com` endpointot használja.

---

## Használat

### Shortcode szerkesztőben (Gutenberg / Classic Editor)

```
[deepl_translator]
```

### PHP sablon fájlban

```php
<?php echo do_shortcode('[deepl_translator]'); ?>
```

### Shortcode paraméterek

| Paraméter | Leírás | Alapértelmezés |
|-----------|--------|----------------|
| `langs` | Vesszővel elválasztott célnyelvek | Admin beállítás |
| `style` | `dropdown` vagy `flags` | `dropdown` |
| `selector` | CSS selector a fordítandó területre | `.entry-content, main, article` |

### Példák

```
[deepl_translator langs="EN,FR,DE"]
[deepl_translator style="flags"]
[deepl_translator style="flags" langs="EN,FR" selector=".my-content"]
[deepl_translator selector="#main-content, .sidebar-text"]
```

---

## Hogyan működik?

1. A látogató a widgeten kiválaszt egy célnyelvet
2. A plugin JavaScript-ből REST API hívást küld a WordPress backendnek
3. A backend meghívja a DeepL API-t (és cache-eli az eredményt)
4. A JS a DOM szövegcsomópontjait lecseréli a fordítással — **oldal-újratöltés nélkül**
5. Az "Eredeti" gombra kattintva minden visszaáll az eredeti szövegre

### Cache-elés
- WordPress Transient API-t használ
- Cache kulcs = MD5(célnyelv + összes fordított szöveg)
- Tartalom mentésekor (`save_post`) automatikusan törlődik az összes deepl cache

---

## Korlátok

- **DeepL Free API**: havonta 500 000 karakter ingyenesen
- A plugin szöveg csomópontokat fordít — a HTML attribútumokat (pl. `alt`, `placeholder`) nem
- SEO szempontból a tartalom keresőknek továbbra is az eredeti nyelven jelenik meg (ez DOM-csere, nem SSR)

---

## Fájlszerkezet

```
deepl-translator/
└── deepl-translator.php    # Fő plugin fájl (admin UI + REST API + shortcode + CSS)
└── README.md
```

---

## Changelog

### 1.0.0
- Kezdeti kiadás
- DeepL REST API integráció
- Shortcode: `[deepl_translator]`
- Dropdown és zászló stílus
- WordPress Transient cache
- Dark mode támogatás
- Cache törlés tartalom mentésekor
