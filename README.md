# Rating Summary Export for WC Vendors

An original free extension by ClosedLoop AI / ClosedLoop Labs LLC. Export one vendor’s published product rating summaries from free **WooCommerce** and **WC Vendors Marketplace**. No Pro purchase, subscription, external connection or ClosedLoop AI integration is required.

## Install and download

1. Install and activate WooCommerce and WC Vendors Marketplace. This version requires WordPress7.0 or later, PHP8.3 or later and WooCommerce11.3 or later.
2. In WordPress, choose **Plugins → Add Plugin → Upload Plugin**, select the supplied `wc-vendors-rating-summary-export-1.0.0.zip` and activate it. Alternatively, copy this directory to `wp-content/plugins/wc-vendors-rating-summary-export/` and activate it.
3. Sign in as an administrator and open **Tools → Rating Summary Export**.
4. Choose a vendor and select **Download rating summary CSV**. An eligible vendor with no products gets the column header and no data rows.

The page and download require the WordPress `manage_options` capability, which administrators have by default. A vendor, shop manager or editor does not gain access from knowing the URL or possessing their own valid WordPress nonce. The download also requires a session-bound native WordPress nonce and a POST request.

## What the CSV means

| Column | Meaning |
| --- | --- |
| `vendor_id` | Selected WC Vendors user ID, resolved with WC Vendors’ native vendor and product ownership APIs/filters. |
| `product_id` | WordPress parent product ID. |
| `product_title` | Stored product title, with spreadsheet protection where needed. |
| `product_url` | Product permalink. |
| `rating_1` … `rating_5` | Number of approved entries with that whole-number star rating. |
| `rating_count` | Sum of the five counts. |
| `mean_rating` | Entry-weighted arithmetic mean, rounded to two decimal places; empty when count is zero. |

Counts come from WooCommerce’s uncached `WC_Comments::get_rating_counts_for_product()` API. Only approved whole-number1–5 ratings contribute. Unrated comments, pending/spam reviews, and imported fractional or out-of-range values do not contribute. Counts represent rating entries, not unique people, verified purchases, orders or a statistically representative sample.

The export includes published, non-hidden, non-password-protected parent products. Draft/private products and variation rows are excluded. The native WC Vendors ownership filter is honoured, including when ownership differs from the stored product author. A variation does not duplicate its parent’s row; the exported statistics belong to the parent product itself.

Rows follow ascending product ID. Catalogue queries use100-product batches and CSV rows stream to the response. The export does not repair or otherwise update cached storefront rating statistics. Concurrent catalogue/review changes can affect an in-progress download; it is not a transactionally frozen snapshot. Large catalogues remain subject to the site’s PHP/web-server time and memory limits.

## Data handling and spreadsheet use

The fixed fields exclude review text, reviewer names, reviewer email/IP fields and order/contact fields. Product context and customer-derived aggregates still need your organisation’s normal data-handling controls. This is not an anonymisation guarantee: public product titles may themselves contain identifying text, and small counts can be sensitive in context. Downloaded CSV files are stored wherever your browser saves downloads; the plugin does not retain a separate export file.

CSV quoting preserves commas, quotes and line breaks. Formula-shaped title/URL text, including leading whitespace followed by `=`, `+`, `-` or `@`, is prefixed with an apostrophe; leading tab/CR/LF is also protected. The apostrophe is intentionally part of the exported cell. Import the file as text where your spreadsheet allows it and review its interpretation before sharing it.

The plugin adds no network client, analytics, remote account, API key or integration grant. It creates no custom database tables/options and exposes no public export endpoint. WordPress and your installed plugins retain their own separate behaviour.

## Compatibility and removal

Version1.0.0 passed18 real HTTP integration tests on WordPress7.1.5, WooCommerce11.3.1, WC Vendors Marketplace2.7.3 and PHP8.3.37 with synthetic vendors, products and ratings. Compatibility with other versions or third-party vendor/ownership filters requires your own staging check; the minimum version declarations are not a claim that every supported combination was tested.

Deactivate and delete the plugin through WordPress to remove its page and download action. There are no plugin-created database records to clean up. Delete downloaded CSV files separately according to your data policy. No review, product or cached-rating repair is performed on uninstall.

## Licence

Copyright2026 ClosedLoop Labs LLC. This original extension is licensed under **GNU GPL version3 or any later version**, without warranty. See [LICENSE](LICENSE). WordPress, WooCommerce and WC Vendors are separate dependencies and are not bundled or relicensed by this package. This is an independent extension; no endorsement by those projects is claimed.
