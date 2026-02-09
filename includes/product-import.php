<?php

function nebf_import_products()
{
    $rows = nebf_api_request_stockfile();

    if (is_wp_error($rows)) {
        return $rows;
    }

    if (empty($rows)) {
        return 'Inga produkter inlästa!';
    }

    $products = [];
    $skipped  = 0;

    foreach ($rows as $row) {
        $row = json_decode(json_encode($row), true);
        // Validera: StockCode är absolut minimum
        if (empty($row['StockCode'])) {
            error_log('NEBF: Skipping product with missing StockCode: ' . $row['StockCode']);
            $skipped++;
            continue;
        }

        $bf_id = sanitize_text_field($row['StockCode']);

        // StockLevel kan vara objekt, tomt eller nummer – normalisera
        $stock = 0;
        if (isset($row['StockLevel'])) {
            if (is_numeric($row['StockLevel'])) {
                $stock = intval($row['StockLevel']);
            } elseif (is_array($row['StockLevel']) && isset($row['StockLevel']['Available'])) {
                $stock = intval($row['StockLevel']['Available']);
            }
        }

        $raw_name = $row['FullName'] ?? '';
        $brand    = $row['Brand'] ?? '';

        $clean_name = nebf_clean_product_name($raw_name, $brand);


        $products[$bf_id] = [
            'bf_id'       => $bf_id,
            'sku'         => $bf_id, // BeautyFort använder StockCode som SKU
            'stock_level' => $stock,
            'price'       => isset($row['Price']) ? floatval($row['Price']) : 0,
            // Rå API‑data sparad för fri åtkomst
            'barcode'                   => $row['Barcode'] ?? '',
            'brand'                     => $row['Brand'] ?? '',
            'category'                  => $row['Category'] ?? '',
            'collection'                => $row['Collection'] ?? '',
            'description'               => $row['Description'] ?? '',
            'fullname'                  => $clean_name,
            'rawname'                    => $raw_name,
            'gender'                    => $row['Gender'] ?? '',
            'high_res_image_url'        => $row['HighResImageUrl'] ?? '',
            'thumbnail_url' => $row['ThumbnailImageUrl'] ?? '',
            'image_last_updated'        => $row['ImageLastUpdated'] ?? '',
            'last_purchased_date'       => $row['LastPurchasedDate'] ?? '',
            'last_purchased_price'      => $row['LastPurchasedPrice'] ?? '',
            'size'                      => $row['Size'] ?? '',
            'type'                      => $row['Type'] ?? '',
            'your_rating'               => $row['YourRating'] ?? '',
            'your_stock_code'           => $row['YourStockCode'] ?? '',
            'raw'                       => $row, // alltid behåll rådata för debugg
        ];
    }

    // Spara i cache i 1 timme
    set_transient('nebf_beautyfort_products', $products, HOUR_IN_SECONDS);
    error_log('NEBF cache count: ' . count($products));
    error_log('NEBF skipped products: ' . $skipped);
    error_log('Transient set: ' . (get_transient('nebf_beautyfort_products') ? 'YES' : 'NO'));

    return [
        'cached'  => count($products),
        'skipped' => $skipped,
        'total'   => count($rows),
    ];
}
