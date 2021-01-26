<?php /* Template Name: profile */ ?>
<?php get_header(); ?>
<?php 
$user = wp_get_current_user();
$user_id = $user->data->ID;
$user_fname = $user->data->first_name;
$user_lname = $user->data->last_name;

$user_email = $user->data->user_email;
?>
	<div class="form">
		<div class="name">
 <label>
    ID:</label>
    <input id="id.no" type="text" value = "<?php echo $user_id; ?>">
  </div>
  <div class="name">
  <label>
    Name:</label>
    <input id="name" type="text">
    <label>
    	Address:</label>
    <input type="text" id="address">
  </div>
		<div class="name">
  <label>
    Lastname:</label>
    <input id="lastname" type="text">
</div>
<div class="name">
  <label>
    Phone:</label>
    <input id="phone" type="text">
</div>
<div class="name">
  <label>
    Business Phone:</label>
    <input id="name" type="text">
</div>
<div class="name">
   <label>
    Tele No:</label>
    <input id="name" type="text">
  </div>
</div>

<?php
    // GET CURR USER
    $current_user = wp_get_current_user();
    // echo $current_user;
    if ( 0 == $current_user->ID ) return;
    // GET USER ORDERS (COMPLETED + PROCESSING)
    $customer_orders = get_posts( array(
        'numberposts' => -1,
        'meta_key'    => '_customer_user',
        'meta_value'  => $current_user->ID,
        'post_type'   => wc_get_order_types(),
        'post_status' => array_keys( wc_get_is_paid_statuses() ),
    ) );
    // LOOP THROUGH ORDERS AND GET PRODUCT IDS
    if ( ! $customer_orders ) return;
    $product_ids = array();
    foreach ( $customer_orders as $customer_order ) {
        $order = wc_get_order( $customer_order->ID );
        // echo $order;
        $post_id  = $customer_order->ID;
        echo $post_id;
        $site_url = get_post_meta( $post_id, 'web_site_url'); 
        echo ($site_url[0]);
        $site_ip = get_post_meta( $post_id, 'web_site_ip'); 
        echo ($site_ip[0]);
        echo ($order->get_total());
        echo "subscription<br>";
        $subscriptions   = get_post_meta( $post_id , 'sumo_subsc_get_available_postids_from_parent_order' , true ) ;
        foreach ($subscriptions as $subscription) {
        $subscription_id  =  $subscription;
        echo sumo_display_start_date($subscription_id);
        echo "<br>";
        echo sumo_display_next_due_date($subscription_id);
        }
        // echo $order;
        // $items = $order->get_items();
        // foreach ( $items as $item ) {
        //     $product_id = $item->get_product_id();
        //     $product_ids[] = $product_id;
        // }
    }
?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>