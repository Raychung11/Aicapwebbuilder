<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Seeds a realistic catalog of furniture products + a couple of vouchers
 * for the given company. Idempotent — does nothing when the company
 * already has any product.
 *
 * Returns ['products' => N, 'vouchers' => N] inserted, or zeros if skipped.
 */
function seed_sample_products(int $company_id): array {
    $existing = (int) (db_one(
        'SELECT COUNT(*) c FROM products WHERE company_id = ?', [$company_id]
    )['c'] ?? 0);
    if ($existing > 0) {
        return ['products' => 0, 'vouchers' => 0, 'skipped' => true];
    }

    // First 6 items are flagged as Featured for the homepage.
    $items = sample_furniture_data();
    $count = 0;
    foreach ($items as $idx => $p) {
        $base = slugify($p['name']);
        $slug = $base; $i = 1;
        while (db_one('SELECT id FROM products WHERE company_id = ? AND slug = ?',
                      [$company_id, $slug])) {
            $slug = $base . '-' . (++$i);
        }
        db_insert(
            'INSERT INTO products
               (company_id, name, slug, category, subcategory, description,
                price_min, price_max, stock_status, is_featured,
                meta_title, meta_description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "in_stock", ?, ?, ?, "active")',
            [
                $company_id, $p['name'], $slug,
                $p['category'], $p['subcategory'], $p['description'],
                $p['price_min'], $p['price_max'],
                $idx < 6 ? 1 : 0,
                $p['name'] . ' | ' . $p['category'],
                $p['meta'] ?? mb_substr(strip_tags($p['description']), 0, 160),
            ]
        );
        $count++;
    }

    // Vouchers
    $voucher_count = 0;
    $vouchers = [
        [
            'title' => 'First Order Discount',
            'description' => 'Welcome! Get 10% off your first order.',
            'type' => 'percent', 'value' => 10,
            'usage_limit' => null, 'per_member_limit' => 1,
        ],
        [
            'title' => 'Free Delivery',
            'description' => 'Free delivery within Klang Valley with any purchase above RM 1,500.',
            'type' => 'gift', 'value' => null,
            'usage_limit' => null, 'per_member_limit' => 1,
        ],
    ];
    foreach ($vouchers as $v) {
        db_insert(
            'INSERT INTO vouchers
               (company_id, title, description, type, value, expiry_date,
                usage_limit, per_member_limit, redemption_method, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "code", "active")',
            [
                $company_id, $v['title'], $v['description'], $v['type'], $v['value'],
                date('Y-m-d', strtotime('+90 days')),
                $v['usage_limit'], $v['per_member_limit'],
            ]
        );
        $voucher_count++;
    }

    return ['products' => $count, 'vouchers' => $voucher_count, 'skipped' => false];
}

/**
 * Profiles for the demo tenants. Each one ends up as a fully-formed
 * company with its own branding, branch, products and vouchers.
 */
function sample_company_profiles(): array {
    return [
        [
            'name'      => 'Ladore Furniture',
            'slug'      => 'ladore',
            'theme'     => '#1e3a8a',  // navy
            'theme_2'   => '#f59e0b',  // amber
            'tagline'   => 'Modern furniture for modern living.',
            'meta_desc' => 'Ladore Furniture — modern sofas, beds, dining sets and office pieces designed for contemporary Malaysian homes.',
            'phone'     => '+603-1234 5678',
            'email'     => 'hello@ladore.my',
            'whatsapp'  => '60123456789',
            'hours'     => 'Mon–Sun, 10:00 – 21:00',
            'admin'     => ['name' => 'Ladore Owner', 'email' => 'owner@ladore.my', 'pass' => 'Owner@12345'],
            'branch'    => [
                'name'    => 'Ladore Damansara Showroom',
                'address' => 'No. 22, Jalan SS 21/35, Damansara Utama, 47400 Petaling Jaya, Selangor',
                'phone'   => '+603-7732 8800',
                'hours'   => 'Daily 10am – 9pm',
            ],
        ],
        [
            'name'      => 'Suifour Living',
            'slug'      => 'suifour',
            'theme'     => '#0f766e',  // teal
            'theme_2'   => '#fbbf24',  // gold
            'tagline'   => 'Premium living spaces, crafted for every home.',
            'meta_desc' => 'Suifour Living — premium sofas, bedrooms, dining and workspace furniture with handcrafted detail and free delivery.',
            'phone'     => '+603-2222 3344',
            'email'     => 'info@suifour.my',
            'whatsapp'  => '60181234567',
            'hours'     => 'Mon–Sat, 11:00 – 20:00',
            'admin'     => ['name' => 'Suifour Owner', 'email' => 'owner@suifour.my', 'pass' => 'Owner@12345'],
            'branch'    => [
                'name'    => 'Suifour KL Flagship',
                'address' => 'Lot G-12, The Gardens Mall, Mid Valley City, Lingkaran Syed Putra, 59200 Kuala Lumpur',
                'phone'   => '+603-2287 5599',
                'hours'   => 'Mon–Sat 11am – 8pm',
            ],
        ],
    ];
}

/**
 * Idempotently create a sample company with admin + branch + catalog +
 * vouchers. Returns a per-step report.
 */
function seed_sample_company(array $profile, int $cost = 10): array {
    $report = ['name' => $profile['name'], 'created' => false, 'products' => 0, 'vouchers' => 0];

    $existing = db_one('SELECT id FROM companies WHERE slug = ?', [$profile['slug']]);
    if ($existing) {
        $cid = (int) $existing['id'];
    } else {
        $cid = db_insert(
            'INSERT INTO companies
               (name, slug, subdomain, theme_color, theme_secondary_color,
                description, phone, email, whatsapp_number, operating_hours,
                meta_title, meta_description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")',
            [
                $profile['name'], $profile['slug'], $profile['slug'],
                $profile['theme'], $profile['theme_2'],
                $profile['tagline'], $profile['phone'], $profile['email'],
                $profile['whatsapp'], $profile['hours'],
                $profile['name'] . ' — ' . $profile['tagline'],
                $profile['meta_desc'],
            ]
        );
        $report['created'] = true;
    }

    // Company admin (idempotent on email)
    if (!db_one('SELECT id FROM company_admins WHERE email = ?', [$profile['admin']['email']])) {
        db_insert(
            'INSERT INTO company_admins (company_id, name, email, password_hash, role)
             VALUES (?, ?, ?, ?, "owner")',
            [
                $cid, $profile['admin']['name'], $profile['admin']['email'],
                password_hash($profile['admin']['pass'], PASSWORD_BCRYPT, ['cost' => $cost]),
            ]
        );
    }

    // Branch (idempotent on name)
    if (!db_one('SELECT id FROM branches WHERE company_id = ? AND name = ?',
                [$cid, $profile['branch']['name']])) {
        db_insert(
            'INSERT INTO branches (company_id, name, address, phone, operating_hours, status)
             VALUES (?, ?, ?, ?, ?, "active")',
            [
                $cid, $profile['branch']['name'], $profile['branch']['address'],
                $profile['branch']['phone'], $profile['branch']['hours'],
            ]
        );
    }

    // Products + vouchers
    $r = seed_sample_products($cid);
    $report['products'] = $r['products'];
    $report['vouchers'] = $r['vouchers'];
    $report['cid']      = $cid;
    return $report;
}

/**
 * 16 sample furniture items across 4 categories and 16 subcategories.
 */
function sample_furniture_data(): array {
    return [
        // ---- Living Room ----
        [
            'name' => 'Aria 3-Seater Sofa',
            'category' => 'Living Room', 'subcategory' => 'Sofa',
            'description' => "Modern 3-seater sofa with high-density foam cushions and a solid kembang frame. Stain-resistant fabric available in 6 colors.\n\nDimensions: 215 × 92 × 85 cm",
            'price_min' => 2499, 'price_max' => 3999,
        ],
        [
            'name' => 'Carlton L-Shape Sofa',
            'category' => 'Living Room', 'subcategory' => 'Sofa',
            'description' => "Spacious L-shape with reversible chaise, hidden storage and removable washable covers. Perfect for family living rooms.\n\nDimensions: 280 × 165 × 88 cm",
            'price_min' => 4500, 'price_max' => 6800,
        ],
        [
            'name' => 'Oslo Coffee Table',
            'category' => 'Living Room', 'subcategory' => 'Coffee Table',
            'description' => "Scandinavian coffee table in solid oak with a smooth matte finish and a lower magazine shelf.\n\nDimensions: 120 × 60 × 45 cm",
            'price_min' => 599, 'price_max' => 899,
        ],
        [
            'name' => 'Modena TV Console',
            'category' => 'Living Room', 'subcategory' => 'TV Console',
            'description' => "Sleek TV console with cable management, two soft-close drawers and an open shelf for media devices. Fits TVs up to 75\".\n\nDimensions: 180 × 40 × 50 cm",
            'price_min' => 1299, 'price_max' => 2199,
        ],
        [
            'name' => 'Plush Recliner Chair',
            'category' => 'Living Room', 'subcategory' => 'Recliner',
            'description' => "Power recliner with memory-foam cushion, USB charging port and built-in cup holder.",
            'price_min' => 1899, 'price_max' => 1899,
        ],
        [
            'name' => 'Vienna Armchair',
            'category' => 'Living Room', 'subcategory' => 'Armchair',
            'description' => "Mid-century inspired armchair with walnut legs and bouclé upholstery.",
            'price_min' => 1099, 'price_max' => 1099,
        ],

        // ---- Bedroom ----
        [
            'name' => 'Helsinki Queen Bed',
            'category' => 'Bedroom', 'subcategory' => 'Bed Frame',
            'description' => "Queen-size bed with upholstered headboard, slatted base and under-bed storage drawers.\n\nDimensions: 160 × 200 cm",
            'price_min' => 2299, 'price_max' => 3799,
        ],
        [
            'name' => 'Stockholm King Bed',
            'category' => 'Bedroom', 'subcategory' => 'Bed Frame',
            'description' => "King-size bed with tufted headboard, hydraulic lift storage and reinforced solid-wood frame.\n\nDimensions: 180 × 200 cm",
            'price_min' => 3499, 'price_max' => 4999,
        ],
        [
            'name' => 'Nordic 6-Door Wardrobe',
            'category' => 'Bedroom', 'subcategory' => 'Wardrobe',
            'description' => "Spacious 6-door wardrobe with full-length mirror, hanging rails, four drawers and adjustable shelves.\n\nDimensions: 240 × 60 × 220 cm",
            'price_min' => 3899, 'price_max' => 3899,
        ],
        [
            'name' => 'CloudComfort Mattress (Queen)',
            'category' => 'Bedroom', 'subcategory' => 'Mattress',
            'description' => "Queen-size hybrid mattress with pocketed coils, gel-infused memory foam and a breathable cooling cover. 10-year warranty.",
            'price_min' => 2499, 'price_max' => 3299,
        ],
        [
            'name' => 'Dakota Bedside Table',
            'category' => 'Bedroom', 'subcategory' => 'Bedside Table',
            'description' => "Compact bedside table with two drawers and a cable port for charging at night.",
            'price_min' => 449, 'price_max' => 449,
        ],

        // ---- Dining ----
        [
            'name' => 'Maple Dining Set (6-Seater)',
            'category' => 'Dining', 'subcategory' => 'Dining Table',
            'description' => "Solid maple 6-seater dining table set with cushioned chairs. Heat-resistant top and tapered legs.",
            'price_min' => 3599, 'price_max' => 4899,
        ],
        [
            'name' => 'Rattan Dining Chair',
            'category' => 'Dining', 'subcategory' => 'Dining Chair',
            'description' => "Hand-woven rattan dining chair with solid teak frame. Sold per piece.",
            'price_min' => 599, 'price_max' => 599,
        ],
        [
            'name' => 'Industrial Bar Stool',
            'category' => 'Dining', 'subcategory' => 'Bar Stool',
            'description' => "Adjustable-height bar stool with footrest, swivel seat and matte black metal frame.",
            'price_min' => 449, 'price_max' => 449,
        ],

        // ---- Office ----
        [
            'name' => 'Executive Office Desk',
            'category' => 'Office', 'subcategory' => 'Office Desk',
            'description' => "Executive desk with built-in cable tray, locking drawers and a side return shelf.\n\nDimensions: 180 × 80 × 76 cm",
            'price_min' => 1899, 'price_max' => 2499,
        ],
        [
            'name' => 'Ergo Mesh Office Chair',
            'category' => 'Office', 'subcategory' => 'Office Chair',
            'description' => "Ergonomic mesh chair with adjustable lumbar support, 3D armrests and synchronized tilt mechanism.",
            'price_min' => 999, 'price_max' => 1499,
        ],
    ];
}
