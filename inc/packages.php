<?php
/**
 * Package pricing + item-loading helpers.
 *
 *   package_items_for_package($pkg_id, $cid) — returns one row per
 *     section item with product info + section grouping.
 *   package_retail_price($pkg_id, $cid)      — computed retail from
 *     real product prices: sum(items * qty) for 'included' sections,
 *     average of items for 'choice' sections.
 *
 * Safe on a partial deploy: if the package_section_items table
 * doesn't exist yet, returns empty / 0.0.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function package_items_for_package(int $package_id, int $cid): array {
    if (!db_table_exists('package_section_items')) return [];
    return db_all(
        'SELECT psi.id          AS item_id,
                psi.section_id,
                psi.product_id,
                psi.quantity,
                psi.sort_order,
                s.title         AS section_title,
                s.kind          AS section_kind,
                p.name          AS product_name,
                p.price_min,
                p.price_max,
                (SELECT image_path FROM product_images
                  WHERE product_id = p.id
                  ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
           FROM package_section_items psi
           JOIN package_sections s ON s.id = psi.section_id
           JOIN products p ON p.id = psi.product_id
          WHERE psi.company_id = ? AND s.package_id = ?
          ORDER BY s.sort_order, s.id, psi.sort_order, psi.id',
        [$cid, $package_id]
    );
}

function package_items_for_section(int $section_id, int $cid): array {
    if (!db_table_exists('package_section_items')) return [];
    return db_all(
        'SELECT psi.id        AS item_id,
                psi.product_id,
                psi.quantity,
                psi.sort_order,
                p.name        AS product_name,
                p.price_min,
                p.price_max,
                p.category,
                p.subcategory,
                (SELECT image_path FROM product_images
                  WHERE product_id = p.id
                  ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS img
           FROM package_section_items psi
           JOIN products p ON p.id = psi.product_id
          WHERE psi.company_id = ? AND psi.section_id = ?
          ORDER BY psi.sort_order, psi.id',
        [$cid, $section_id]
    );
}

/**
 * Returns a representative price for a product. Uses price_min, else
 * price_max, else 0.
 */
function product_unit_price(array $product): float {
    if ($product['price_min'] !== null) return (float) $product['price_min'];
    if ($product['price_max'] !== null) return (float) $product['price_max'];
    return 0.0;
}

/**
 * Auto-computed retail price from the package's section items.
 * - 'included' section → sum(price × quantity) of all items
 * - 'choice'   section → average price across choices (× 1)
 */
function package_retail_price(int $package_id, int $cid): float {
    $rows = package_items_for_package($package_id, $cid);
    if (!$rows) return 0.0;

    $by_section = [];
    foreach ($rows as $r) {
        $sid = (int) $r['section_id'];
        if (!isset($by_section[$sid])) {
            $by_section[$sid] = ['kind' => $r['section_kind'], 'items' => []];
        }
        $by_section[$sid]['items'][] = [
            'price' => product_unit_price($r),
            'qty'   => (int) $r['quantity'],
        ];
    }

    $total = 0.0;
    foreach ($by_section as $sec) {
        if ($sec['kind'] === 'choice') {
            $count = count($sec['items']);
            if ($count === 0) continue;
            $sum = 0.0;
            foreach ($sec['items'] as $it) $sum += $it['price'] * $it['qty'];
            $total += $sum / $count;
        } else {
            foreach ($sec['items'] as $it) $total += $it['price'] * $it['qty'];
        }
    }
    return $total;
}
