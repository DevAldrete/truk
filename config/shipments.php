<?php

/**
 * Shipment package limits.
 *
 * A shipment is a movement of goods, not a warehouse: an unbounded package
 * count is almost always a typo. These ceilings keep one shipment (and one
 * request) sane and give the user a clear error instead of a slow insert.
 */

return [
    // The most packages a single shipment may ever carry.
    'max_packages_per_shipment' => (int) env('MAX_PACKAGES_PER_SHIPMENT', 500),

    // The most packages one request may add at a time.
    'max_packages_per_request' => (int) env('MAX_PACKAGES_PER_REQUEST', 200),
];
