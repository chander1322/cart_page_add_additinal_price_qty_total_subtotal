<?php
/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';

if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */

//add Postmeta
function create_meta_box_product() {
    add_meta_box(
        'title',//id
        'Title:',//title
        'box_for_product',//callback
        'product',//post type
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'create_meta_box_product');
//==============callback for metabox======================
function box_for_product($post) {
    wp_nonce_field('bp_metabox_nonce', 'bp_nonce');
    $title = get_post_meta($post->ID, 'items', true);
    ?>            
        <p>
            <label for="title"><strong>Enter Box Quantity</strong></label>
            <input type="text" id="title" name="items" size="26" value="<?php echo esc_attr( $title ); ?>" placeholder="Enter Box Quantity Here" />
        </p>
    <?php 
}
//==============end callback for metabox======================

//==============save metabox======================
function save_post_meta($post_id) {
    if(!isset($_POST['bp_nonce']) ||
        !wp_verify_nonce($_POST['bp_nonce'],
        'bp_metabox_nonce')) return;  


    if(isset($_POST['items'])) {
        update_post_meta($post_id, 'items', $_POST['items']);
    }
}
add_action('save_post', 'save_post_meta');
//==============end save metabox======================

//==============create select dropdown======================
add_action('woocommerce_before_cart_totals','drop_down_product_page');
function drop_down_product_page(){
	wp_nonce_field('bp_metabox_nonce', 'item_nonce');
	//get products
	$box1 = wc_get_product(17);
	$box2 = wc_get_product(30);
	$box3 = wc_get_product(31);
	//get products price
	$price_box1=$box1->get_sale_price();
	$price_box2=$box2->get_sale_price();
	$price_box3=$box3->get_sale_price();

	//get current product price
	$sale = get_post_meta( get_the_ID(), '_sale_price', true);
	// echo $sale;

	$meta_field_box1 = get_post_meta(17, 'items', true);
	$meta_field_box2 = get_post_meta(30, 'items', true);
	$meta_field_box3 = get_post_meta(31, 'items', true);
	
	//=======================calculate the total quantity of all products===========
		global $woocommerce;
		$cart=$woocommerce->cart->get_cart();
		$total_qty=0;
		foreach ( $cart as $cart_item ) {
			// gets the cart item quantity
			$quantity  = $cart_item['quantity'];
			$total_qty= $total_qty+$quantity;
		}
	//=======================end calculate the total quantity of all products================
?>

<!-- dropdown select option -->
<h2 for="box">Please Select The Box</h2>
<input type='hidden' class='total_qty' value='<?php echo $total_qty;?>' readonly>
<select class="box_selcted" id='select_box' required>
	<option class='box' disabled>Please Select The Box</option>
	<option class='box1' name='box' value='<?php echo $meta_field_box1;?>'>box1</option>
	<option class='box2' name='box' value='<?php echo $meta_field_box2;?>'>box2</option>
	<option class='box3' name='box'  value='<?php echo $meta_field_box3;?>' >box3</option>
</select></br></br>
<!-- end dropdown select option -->
<?php 
}

//========================for checkout total=============================
add_filter( 'woocommerce_calculated_total', 'modify_calculated_total', 20, 2 );
function modify_calculated_total( $total) {
		$box1 = wc_get_product(17);
		$box2 = wc_get_product(30);
		$box3 = wc_get_product(31);
		$price_box1=$box1->get_sale_price();
		$price_box2=$box2->get_sale_price();
		$price_box3=$box3->get_sale_price();
		// total quantity
		global $woocommerce;
		$cart=$woocommerce->cart->get_cart();
		$total_qty=0;
		foreach ( $cart as $cart_item ) {
			// gets the cart item quantity
			$quantity  = $cart_item['quantity'];
			$total_qty= $total_qty+$quantity;
		}
		// end total quantity

		//for update total price
		if($total_qty<='3'){
    	return $total + $price_box1;
		}
		elseif($total_qty >'3' && $total_qty <='6'){
			return $total + $price_box2;
		}
		elseif($total_qty >'6' && $total_qty <='12'){
			return $total + $price_box3;
		}
		else{
			return $total;
		}
		//end for update total price
}
//=========================End for checkout total=============================

//========================= for checkout subtotal=============================
add_action( 'woocommerce_calculate_totals', 'add_custom_price22', 10, 1);
function add_custom_price22( $cart_object ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;
    if ( did_action( 'woocommerce_calculate_totals' ) >= 2 )
        return;
		$box1 = wc_get_product(17);
		$box2 = wc_get_product(30);
		$box3 = wc_get_product(31);

		$price_box1=$box1->get_sale_price();
		$price_box2=$box2->get_sale_price();
		$price_box3=$box3->get_sale_price();
		global $woocommerce;
		$cart=$woocommerce->cart->get_cart();
		$total_qty=0;
		foreach ( $cart as $cart_item ) {
			// gets the cart item quantity
			$quantity  = $cart_item['quantity'];
			$total_qty= $total_qty+$quantity;
		}
		if($total_qty<='3'){
    	return $cart_object->subtotal += $price_box1;
		}
		elseif($total_qty >'3' && $total_qty <='6'){
			return $cart_object->subtotal += $price_box2;
		}
		elseif($total_qty >'6' && $total_qty <='12'){
			return $cart_object->subtotal += $price_box3;
		}
		else{
			return $cart_object->subtotal;
		}
		// $cart_object->subtotal += $price_box1;
		?>
		<?php
	
}
//========================= End for checkout subtotal=============================

//update cart on change of qty
add_action( 'wp_footer', 'bbloomer_cart_refresh_update_qty' ); 
function bbloomer_cart_refresh_update_qty() {
   if ( is_cart() || ( is_cart() && is_checkout() ) ) {
	?>
	<script>
		function update_qty_box(){
			var qty=jQuery('input.total_qty').val();
			var box_val = jQuery('#select_box option.box1').val();
			var box_val_2 = jQuery('#select_box option.box2').val();
			var box_val_3 = jQuery('#select_box option.box3').val();
			// alert('heloo');
			// alert(qty);
		
            
			if(qty<=3){
				setTimeout(function () {
					jQuery('#select_box  option[value="'+box_val+'"]').attr('selected','selected');
					jQuery('#select_box option.box2').prop('disabled', true);
					jQuery('#select_box option.box3').prop('disabled', true);
				}, 800);
				

			}
			else if(qty>'3' && qty<='6'){
				// alert('hello');
				setTimeout(function () {
				jQuery('#select_box  option[value="'+box_val_2+'"]').attr('selected','selected');
				jQuery('#select_box option.box1').prop('disabled', true);
				jQuery('#select_box option.box3').prop('disabled', true);
			}, 800);
			}
			else if(qty>'6' || qty<='12'){
				setTimeout(function () {
				jQuery('#select_box  option[value="'+box_val_3+'"]').attr('selected','selected');
				jQuery('#select_box option.box1').prop('disabled', true);
				jQuery('#select_box option.box2').prop('disabled', true);
			}, 800);
			}
			
		}
		jQuery(document).ready(function(){
				setTimeout(function () {
				update_qty_box();
			}, 2500);
		});
		jQuery('div.woocommerce').on('click', 'input.qty', function(){
			jQuery('[name=\'update_cart\']').trigger('click');
			setTimeout(function () {
				update_qty_box();
			}, 2500);
         });
	</script>
	<?php
    //   wc_enqueue_js( "
         

    //   " );
   }
}
//========================show box price================
add_action( 'woocommerce_cart_totals_before_shipping', 'boxed_price', 20 );
function boxed_price(){
?><style>
	.cart_totals .shop_table tbody {
    display: flex;
    flex-direction: column;
}
.cart_totals tr.cart-total-volume {
    order: -1;
}
</style><?php
	// echo "<tr class='cart-subtotal'><th>Title</th><td>Text</td></tr>";
		echo ' <tr class="cart-total-volume">
				<th>' . __( "Box Price", "woocommerce" ) . '</th>
				<td data-title="total-volume">'.test().'</td>
			</tr>';
}

function test(){
	
	$box1 = wc_get_product(17);
	$box2 = wc_get_product(30);
	$box3 = wc_get_product(31);

	$price_box1=$box1->get_sale_price();
	$price_box2=$box2->get_sale_price();
	$price_box3=$box3->get_sale_price();
	global $woocommerce;
	$cart=$woocommerce->cart->get_cart();
	$total_qty=0;
	foreach ( $cart as $cart_item ) {
		// gets the cart item quantity
		$quantity  = $cart_item['quantity'];
		$total_qty= $total_qty+$quantity;
	}
	if($total_qty<='3'){
	return '₹ '.$price_box1;
	}
	elseif($total_qty >'3' && $total_qty <='6'){
		return '₹ '. $price_box2;
	}
	elseif($total_qty >'6' && $total_qty <='12'){
		return '₹ '.$price_box3;
	}
	else{
		
	}
}


// function subotal_update(){

// 	echo 'updated price';
// 	die();
// }
//get the cart items

?>
