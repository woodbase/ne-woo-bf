# ne-woo-bf

WordPress-plugin för första release av **BeautyFort ↔ WooCommerce**-integration.

## Första release (v1.0.0)

Den här releasen fokuserar på en stabil grund för produktimport och synk mellan BeautyFort och WooCommerce.

### Innehåll i releasen
- Import av produkter från BeautyFort till WooCommerce.
- Synk av lagerstatus.
- Grundläggande adminvyer för:
  - Dashboard
  - Produkter
  - Inställningar
- Språkstöd via språkfiler (`sv_SE`, `en_US`, `de_DE`, `es_ES`).

## Krav
- WordPress 6+
- WooCommerce 7+
- PHP 8.0+

## Installation
1. Lägg plugin-mappen i:
   `wp-content/plugins/ne-woo-bf`
2. Aktivera pluginet via **Tillägg** i WordPress admin.
3. Gå till pluginets inställningar och fyll i API-uppgifter.
4. Kör första produktimporten.

## Användning
1. Öppna pluginets adminsektion.
2. Verifiera anslutning mot BeautyFort.
3. Starta import/synk.
4. Kontrollera importerade produkter i WooCommerce.

## Projektstruktur (översikt)
- `includes/Core/` – bootstrap/autoload/plugin-kärna
- `includes/Controllers/` – adminflöden och routing
- `includes/Services/` – API/synk/notiser
- `includes/Models/` – datalager/repositories
- `admin/views/` – adminvyer
- `assets/` – statiska resurser
- `languages/` – översättningar

## Licens
MIT (se `LICENSE`).
