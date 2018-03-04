<?php
//This function  Check If page is sub-category of woocommerce but not category 
function is_subcategory($cat_id = null) {
    if (is_tax('product_cat')) {
        if (empty($cat_id)){
            $cat_id = get_queried_object_id();
        }
        $cat = get_term(get_queried_object_id(), 'product_cat');
        if ( empty($cat->parent) ){
            return false;
        }else{
            return true;
        }
    }
    return false;
}
