# ✨ NE BeautyFort Woo

[![Version](https://img.shields.io/badge/version-1.0.0-2563eb?style=for-the-badge)](#)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-7f54b3?style=for-the-badge&logo=woocommerce&logoColor=white)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-10b981?style=for-the-badge)](LICENSE)

A WordPress plugin for the first public release of the **BeautyFort ↔ WooCommerce** integration.

---

## 🚀 First Release (v1.0.0)

This release provides a stable baseline for product import and synchronization between BeautyFort and WooCommerce.

### ✅ Included in this release
- Product import from BeautyFort into WooCommerce
- Stock synchronization
- Core admin pages:
  - Dashboard
  - Products
  - Settings
- Localization files:
  - `sv_SE`
  - `en_US`
  - `de_DE`
  - `es_ES`

## 📋 Requirements

| Platform | Minimum version |
|---|---|
| WordPress | `6.0+` |
| WooCommerce | `7.0+` |
| PHP | `8.0+` |

## 🛠 Installation
1. Place the plugin folder in:
   `wp-content/plugins/ne-woo-bf`
2. Activate the plugin from **Plugins** in the WordPress admin
3. Open plugin settings and add API credentials
4. Run the first product import

## 🔄 Usage
1. Open the plugin admin section
2. Verify the BeautyFort connection
3. Start import/sync
4. Verify imported products in WooCommerce

## 🧩 Project structure (overview)
- `includes/Core/` – bootstrap, autoloader, plugin core
- `includes/Controllers/` – admin flow and routing
- `includes/Services/` – API, sync, notices
- `includes/Models/` – repositories/data access
- `admin/views/` – admin views
- `assets/` – static assets
- `languages/` – translation files

## 📄 License
MIT (see `LICENSE`).
