<?php

function nebf_import_products()
{
    // Hämta data från BeautyFort
    $rows = nebf_api_request_stockfile();

    if (is_wp_error($rows)) {
        return $rows;
    }

    if (empty($rows)) {
        return new WP_Error('nebf_empty', 'Inga produkter inlästa!');
    }

    $products = [];
    $skipped  = 0;

    foreach ($rows as $row) {
        $row = json_decode(json_encode($row), true);

        // StockCode är absolut minimum
        if (empty($row['StockCode'])) {
            error_log('NEBF: Skipping product with missing StockCode');
            $skipped++;
            continue;
        }

        $bf_id = sanitize_text_field($row['StockCode']);

        // Normalisera lager
        $stock = 0;
        if (isset($row['StockLevel'])) {
            if (is_numeric($row['StockLevel'])) {
                $stock = (int) $row['StockLevel'];
            } elseif (is_array($row['StockLevel']) && isset($row['StockLevel']['Available'])) {
                $stock = (int) $row['StockLevel']['Available'];
            }
        }

        $raw_name = $row['FullName'] ?? '';
        $brand    = $row['Brand'] ?? '';

        $clean_name = nebf_clean_product_name($raw_name, $brand);

        $products[$bf_id] = [
            'bf_id'                  => $bf_id,
            'sku'                    => $bf_id,
            'stock_level'            => $stock,
            'price'                  => isset($row['Price']) ? (float) $row['Price'] : 0,

            // Produktdata
            'barcode'                => $row['Barcode'] ?? '',
            'brand'                  => $brand,
            'category'               => $row['Category'] ?? '',
            'collection'             => $row['Collection'] ?? '',
            'description'            => $row['Description'] ?? '',
            'fullname'               => $clean_name,
            'rawname'                => $raw_name,
            'gender'                 => $row['Gender'] ?? '',
            'size'                   => $row['Size'] ?? '',
            'type'                   => $row['Type'] ?? '',

            // Media
            'high_res_image_url'     => $row['HighResImageUrl'] ?? '',
            'thumbnail_url'          => $row['ThumbnailImageUrl'] ?? '',
            'image_last_updated'     => $row['ImageLastUpdated'] ?? '',

            // Historik
            'last_purchased_date'    => $row['LastPurchasedDate'] ?? '',
            'last_purchased_price'   => $row['LastPurchasedPrice'] ?? '',
            'your_rating'            => $row['YourRating'] ?? '',
            'your_stock_code'        => $row['YourStockCode'] ?? '',

            // Alltid rådata för debug
            'raw'                    => $row,
        ];
    }

    /**
     * Cache-hantering
     * - timmar
     * - -1 = permanent
     */
    $hours = (int) get_option('nebf_cache_time', 1);

    if ($hours === -1) {
        set_transient('nebf_beautyfort_products', $products, 0);
        error_log('NEBF cache set: PERMANENT');
    } else {
        $seconds = max(1, $hours) * HOUR_IN_SECONDS;
        set_transient('nebf_beautyfort_products', $products, $seconds);
        error_log('NEBF cache set: ' . $hours . ' hour(s)');
    }

    error_log('NEBF cached products: ' . count($products));
    error_log('NEBF skipped products: ' . $skipped);
    error_log('NEBF total rows from API: ' . count($rows));

    return [
        'cached'  => count($products),
        'skipped' => $skipped,
        'total'   => count($rows),
    ];
}
