<?php

if (!defined('WPINC')) {
    exit;
}


$post_columns = array(
                
    'name' => 'name',
    'url' => 'url',
    'price' => 'price',
    'currencyId' => 'currencyId',
    'categoryId' => 'categoryId',
    'delivery' => 'delivery',
    'weight' => 'weight',
    'dimensions' => 'dimensions',
    'description' => 'description',
    'params' => 'params'   
);

return apply_filters('wt_pf_yandex_product_post_columns',$post_columns);


