# Hydynamic

WordPress child theme (Mediakanjers) voor de nieuwe website van Hydynamic Holland BV, gebouwd op het parent theme `mkbase`.

## Structuur
- `web/` – het thema zelf (dit is wat er gedeployed wordt; WordPress-stylesheet `Mediakanjers/web`)
  - `theme.json` – huisstijl (kleuren, typografie, spacing, tokens)
  - `assets/scss`, `assets/js` – bronbestanden, gebouwd naar `dist/`
  - `template-parts/blocks/` – eigen ACF-blokken (velden in `assets/functions/block-fields.php`)
- `setup/` – opbouwscript dat pagina's, producten, menu's en het offerteformulier klaarzet
- `design/` – referentie uit het Claude Design-ontwerp (assets, code, screenshots)

## Ontwikkelen
```bash
npm install
npx gulp          # build + watch
npx gulp build    # eenmalige build
```

## Content opbouwen (lokaal)
```bash
php setup/hydynamic-setup.php
```
Let op: dit overschrijft de blokinhoud van de pagina's die het script aanmaakt.
