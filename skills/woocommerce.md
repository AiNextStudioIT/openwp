---
name: woocommerce
title: WooCommerce
detects: plugin:woocommerce
status: verified
tested: WooCommerce 11.x test sites
---
# WooCommerce

OpenWP uses the WooCommerce REST API (`/wc/v3`). Tools: `get_woo_products`, `create_woo_product`, `update_woo_product`, `get_woo_orders`, `update_woo_order`, `get_woo_customers`.

## Rules
- A product is created as `draft` unless the user asks to publish it: visitors cannot buy a draft.
- `regular_price` and `sale_price` are strings with a dot, for example `"49.90"`. Use the store currency; do not add the symbol.
- Do not change the status of paid orders to `cancelled` or `refunded` without the user's explicit request: this does not move money, but it triggers customer emails.
- For stock changes set `manage_stock` to true and `stock_quantity`.
