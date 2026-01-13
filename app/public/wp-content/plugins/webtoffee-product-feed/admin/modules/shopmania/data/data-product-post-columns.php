<?php

if (!defined('WPINC')) {
    exit;
}

$post_columns = array(
    'Name' => 'Product name[title]',
    'Description' => 'Product Description[description]',
    'URL' => 'Product URL[link]',
    'Image' => 'Product Image[image]',
    'Price' => 'Price',
    'Currency' => 'Currency',
    'Category' => 'Category',
    'MPC' => 'MPC',
    'Manufacturer' => 'Manufacturer',
    'MPN' => 'MPN',
    'GTIN' => 'GTIN',
    'Availability' => 'Availability',
    'Shipping' => 'Shipping'
);

return apply_filters('wt_pf_shopmania_product_post_columns', $post_columns);

