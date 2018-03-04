<?php
///////////////////////////////WOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO00000000//////////////////////////////////////
/////////////////////////////// WoCommerce Custom Functions Starts Here//////////////////////////////////////
///////////////////////////////WOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO0000000//////////////////////////////////////

add_filter( 'woocommerce_product_tabs', 'nutriton_facts_product_tab' );
add_action('save_post', 'nutrition_facts_save_postdata');
add_action('admin_init', 'nutrition_facts_meta_box');
add_filter( 'woocommerce_product_tabs', 'woo_reorder_tabs', 98 );
add_action( 'woocommerce_after_checkout_billing_form', 'gift_message_field', 1);
add_action( 'woocommerce_checkout_update_order_meta', 'update_gift_order_meta' );
add_action( 'woocommerce_cart_calculate_fees', 'woo_check_custom_message' );
add_filter( 'woocommerce_product_tabs', 'remove_additional_information_tab', 98 );
add_action( 'wp_footer', 'woocommerce_add_gift_box' );
add_action( 'wp_footer', 'remove_gift_fee' );
add_action( 'woocommerce_cart_calculate_fees', 'woo_add_cart_fee' );
add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );
add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );
add_action( 'woocommerce_checkout_order_processed', 'postConstant',  1, 1  );
add_action('init', 'cloneRole');
add_action( 'woocommerce_after_checkout_billing_form', 'constantContactCheckbox');
add_filter('woocommerce_login_redirect', 'wc_login_redirect');
add_action( 'add_meta_boxes', 'mv_add_meta_boxes' );

// Add the new tab to woocommerce 
function nutriton_facts_product_tab( $tabs ) {
    $tabs['test_tab'] = array(
        'title'       => __( 'Nutrition Facts', 'text-domain' ),
        'priority'    => 50,
        'callback'    => 'nutriton_facts_product_tab_content'
    );
    return $tabs;
}

// Check If URL exists such as image 
function UR_exists($url){
    $headers=get_headers($url);
    return stripos($headers[0],"200 OK")?true:false;
}

//Add Content to woocommerce tabs
function nutriton_facts_product_tab_content() {
    global $post;
    $nutrition_facts_content =  get_post_meta($post->ID, 'nutrition_facts', true);
    if(!empty($nutrition_facts_content)){
        echo $nutrition_facts_content;
    }
}

//This function Add meta box Nutrition Facts on the admin screen 
function nutrition_facts_meta_box() {    
   add_meta_box ( 
      'nutrition-facts', 
      __('Nutrition Facts', 'nutrition-facts') , 
      'nutrition_facts', 
      'product'
    );
}

//Displaying the meta box and the WYSIWYG Editor on Admin Screen 
function nutrition_facts($post) {          
    $content = get_post_meta($post->ID, 'nutrition_facts', true);
    wp_editor ( 
    $content , 
    'nutrition_facts', array ( "media_buttons" => true ));
}

//Save the meta box of Extra Meta Box
function nutrition_facts_save_postdata($post_id) {
    if( isset( $_POST['nutrition_facts_once'] ) && isset( $_POST['product'] ) ) {
        //Not save if the user hasn't submitted changes
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
        } 
        // Verifying whether input is coming from the proper form
        if ( ! wp_verify_nonce ( $_POST['nutrition_facts_once'] ) ) {
        return;
        } 
        // Making sure the user has permission
        if('post' == $_POST['product'] ){
           if( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        } 
    } 
    //Now lets Update Post Meta
    if (!empty($_POST['nutrition_facts'])) {
        $data = $_POST['nutrition_facts'];
        update_post_meta($post_id, 'nutrition_facts', $data);  
    }
}

// This funciton hides the default additional info tab Remove this function to add additional informatoin Tab
function remove_additional_information_tab( $tabs ) {
    unset( $tabs['additional_information'] ); 
    return $tabs;
}

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

//This function setting review tabs setting the priority
function woo_reorder_tabs( $tabs ) {
    $tabs['reviews']['priority'] = 100;           // Reviews first
    return $tabs;
}

//This function checks if product is in the cart
function woo_in_cart($product_id) {
    global $woocommerce;
    foreach($woocommerce->cart->get_cart() as $key => $val ) {
        $_product = $val['data'];
        if($product_id == $_product->id ) {
            return true;
        }
    }
    return false;
}

//Gift Message Box HTML AND CSS will move to template
function gift_message_field( $checkout ){
    get_template_part('partials/gift-message');
}

//This Function Update Gift Message
function update_gift_order_meta( $order_id ) {
    if ($_POST['card_message'] AND !empty($_POST['card_message'])){
        update_post_meta( $order_id, 'order_card_message', esc_attr( $_POST[ 'card_message' ] ) );
        update_post_meta( $order_id, 'order_card_name', esc_attr( $_POST[ 'add_gift_box' ] ) );
    }
}

//This function remove gift fee and trigger ajax
function remove_gift_fee() {
    if (is_checkout()){?>
        <script type="text/javascript">
            jQuery( document ).ready(function( $ ) {
                $('#remove_gift_fee').click(function(){
                    jQuery('body').trigger('update_checkout');
                });
            });
        </script>
    <?php
    }
}

//This function check if check box is checked then add fee through AJAX
function woo_check_custom_message( $cart ){
    if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        return;
    }
    if ( isset( $_POST['post_data'] ) ) {
        parse_str( $_POST['post_data'], $post_data );
    } 
    else {
        $post_data = $_POST;
    }
    if (isset($post_data['remove_gift_fee'])) {
        $extracost = 3;
        WC()->cart->add_fee( 'Card Message', $extracost );
    }
}

//This function update gift box
function woocommerce_add_gift_box() {
    if (is_checkout()) { ?>
        <script type="text/javascript">
        jQuery( document ).ready(function( $ ) {
            $('#add_gift_box').click(function(){
                jQuery('body').trigger('update_checkout');
            });
        });
        </script>
        <?php
    }
}

//This function refresh cart fee
function woo_add_cart_fee() {
  global $woocommerce;
    if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
        return;
    }
    $checkout = WC()->checkout()->checkout_fields;
    parse_str( $_POST['post_data'], $post_data );
    if(isset($_POST['add_gift_box'])){
        $vat_label = 'Card';
        if($_POST['add_gift_box'] == '' OR empty($_POST['add_gift_box'])){
          $woocommerce->cart->add_fee( __($vat_label, 'woocommerce'), $vat_total );
        }
    } 
}

//This function add two checkbox on woocommerce product admin page to set new product or in sttory only product
function woo_add_custom_general_fields() {
  global $woocommerce, $post;
    echo '<div class="options_group">';
    woocommerce_wp_checkbox( 
    array( 
        'id'            => '_checkbox_new_product', 
        'wrapper_class' => '', 
        'label'         => __('Set New Product?', 'woocommerce' ), 
        'description'   => __( 'It will flash new product icon ', 'woocommerce' ) 
        )
    );
    echo '</div>';
    echo '<div class="options_group">';
    woocommerce_wp_checkbox( 
    array( 
        'id'            => '_checkbox_instoreonly', 
        'wrapper_class' => '', 
        'label'         => __('In-Store Only?', 'woocommerce' ), 
        'description'   => __( 'It will remove add to cart button ', 'woocommerce' ) 
        )
    );
    echo'</div>';
}

//This function saves the instore only and new product data
function woo_add_custom_general_fields_save( $post_id ){   
    $woocommerce_checkbox = isset( $_POST['_checkbox_new_product'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_checkbox_new_product', $woocommerce_checkbox );
    $woocommerce_instoreonly = isset( $_POST['_checkbox_instoreonly'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_checkbox_instoreonly', $woocommerce_instoreonly );
}

//This function redirect to shop page after log In
function wc_login_redirect( $redirect_to ) {
     $redirect_to = '/shop';
     return $redirect_to;
}

//This function add meta box to display gift option to shipping manager in order page

if (!function_exists('mv_add_meta_boxes')){
    function mv_add_meta_boxes(){
        add_meta_box( 'mv_other_fields', __('Gift Options','woocommerce'), 'mv_add_other_fields_for_packaging', 'shop_order', 'side', 'core' );
    }
}

//This function get the post meta content for gift options
if (!function_exists( 'mv_add_other_fields_for_packaging')){
    function mv_add_other_fields_for_packaging(){
        global $post;
        $order_card_name = get_post_meta( $post->ID, 'order_card_name', true ) ? get_post_meta( $post->ID, 'order_card_name', true ) : '';
        $meta_field_data = get_post_meta( $post->ID, 'order_card_message', true ) ? get_post_meta( $post->ID, 'order_card_message', true ) : '';
        echo '<p style="color:gray">Card: '.$order_card_name.'</p>';
        echo '<p style="color:gray">Card Message: '.$meta_field_data.'</p>';
    }
}

//Creating Test User for testing cloning Admin Roles
function cloneRole(){
    global $wp_roles;
    if (!isset( $wp_roles))
    $wp_roles = new WP_Roles();
    $adm = $wp_roles->get_role('administrator');
    $wp_roles->add_role('tester_account', 'Testing Account', $adm->capabilities);
}

//Check box for email subscription 
function constantContactCheckbox(){
    echo '<br>';
    echo '<div class="options_group">'; 
    echo '<input type="checkbox" value="1" name="constant_contact_checkbox" id = "constant_contact_checkbox"> Subscribe to mail list'; 
    echo '</div>';
}

//This function is to subscribe user email during checkout it is posting information to Constant Contact form 
function postConstant(){
    //Check User Posted to subscribe
    if (isset($_POST['constant_contact_checkbox'])){
    if (isset($_POST['billing_email'])){
        $checkoutUserEmail = $_POST['billing_email'];
    }
    $url = '';
    //create a new cURL resource
    $ch = curl_init($url);
    $current_user = wp_get_current_user();
    //setup request to send json via POST
    $data = array(
            'status' => 'ACTIVE',
            'lists' => array(
                        0 =>array (
                            'id' => '2028867157',
                            ),
                        ),
            'email_addresses' => array (
                                    0 =>array (
                                    'status' => 'ACTIVE',
                                    'email_address' => $checkoutUserEmail,
                                    ),
                                ),
            'first_name' => $current_user->user_firstname,
            'last_name' => $current_user->user_lastname,
            );
    $payload = json_encode($data);
    //attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    //set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    //return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //execute the POST request
    $result = curl_exec($ch);
    //close cURL resource
    curl_close($ch);
    }
}

///////////////////////////////WOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO00000000//////////////////////////////////////
/////////////////////////////// WoCommerce Custom Functions Finish Here//////////////////////////////////////
///////////////////////////////WOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO0000000//////////////////////////////////////