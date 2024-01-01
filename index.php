<?php
/*
Plugin Name: Send Cloud API
Description: Plugin to connect Automatic Labels Using SendCloud.
Version: 1.0.0.0
Author: ComputeUniverse
*/
// Register activation hook
register_activation_hook(__FILE__, 'sendcloud_plugin_activate');
// Register admin menu hook
add_action('admin_menu', 'sendcloud_plugin_menu');

// Function to create custom table on activation
function sendcloud_plugin_activate() {
    global $wpdb;
    
    // Define table name for parcels
    $table_name = $wpdb->prefix . 'cu_sendcloud_parcels';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL query for creating parcels table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        `parcel_id` varchar(255) DEFAULT NULL,
        `order_number` varchar(255) DEFAULT NULL,
        `tracking_number` varchar(255) DEFAULT NULL,
        `carrier_code` varchar(255) DEFAULT NULL,
        `date_created` datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        `tracking_url` varchar(255) DEFAULT NULL,
        `status` int NOT NULL,
        `status_id` int NOT NULL,
        `status_message` varchar(255) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Define table name for credentials
    $table_names = $wpdb->prefix . 'cu_sendcloud_credentials';
    
    // SQL query for creating credentials table
    $sqls = "CREATE TABLE $table_names (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        api_key VARCHAR(255) NOT NULL 
    ) $charset_collate;";

    // Define table name for webhooks
    $table_name_webhook ='cu_sendcloud_webhook';
    
    // SQL query for creating webhooks table
    $sql_webhook = "CREATE TABLE $table_name_webhook (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        order_number VARCHAR(255) NOT NULL ,
        status_message VARCHAR(255) NOT NULL 
    ) $charset_collate;";

    // Include necessary WordPress upgrade file
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Execute SQL queries using dbDelta
    dbDelta($sql);
    dbDelta($sqls);
    dbDelta($sql_webhook);
}

// Function to display the SendCloud API token settings page
function sendcloud_plugin_menu() {
    add_menu_page(
        'SendCloud API Settings',
        'SendCloud Settings',
        'manage_options',
        'sendcloud-settings',
        'sendcloud_settings_page'
    );
}

// Function to initialize SendCloud API credentials

    global $wpdb;
    // Retrieve SendCloud API credentials from the database
    $current_credentials = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}cu_sendcloud_credentials LIMIT 1");
    $token = esc_attr($current_credentials->token ?? '');
    $api_key = esc_attr($current_credentials->api_key ?? '');
define('SENDCLOUD_API_TOKEN', "72ad735b-bba2-4ba8-9b04-3206dcb12346");
define('SENDCLOUD_API_KEY', "8fbe740f6d9042059c50dae1ef244326");

// Hook the function to run during WordPress initialization




global $sendcloudCarriers;
$sendcloudCarriers = array(
    'dhl'    => 'DHL',
    'postnl' => 'PostNL',
    'dpd'    => 'DPD',
    'ups'    => 'UPS',
);

// Function to display the SendCloud API token settings form
function sendcloud_settings_page() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save the API token when the form is submitted
   if (isset($_POST['save_sendcloud_credentials'])) {
        $api_token = ($_POST['sendcloud_api_token']);
        $api_key = ($_POST['sendcloud_api_key']);

     global $wpdb;
       
        $table_name_credentials = $wpdb->prefix . 'cu_sendcloud_credentials';
        $wpdb->insert(
            $table_name_credentials,
            array(
                'token' => $api_token,
                'api_key' => $api_key,
            )
        );

        echo '<div class="notice notice-success"><p>SendCloud API credentials saved successfully!</p></div>';
    }
 global $wpdb;
    // Retrieve the current API token
    $current_credentials = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}cu_sendcloud_credentials LIMIT 1");

    // Display the settings form
    ?>
	<style>
    /* Styles for the form container */
    form {
        max-width: 400px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Styles for form labels */
    label {
        display: block;
        margin-bottom: 8px;
    }

    /* Styles for form inputs */
    input {
        width: 100%;
        padding: 8px;
        margin-bottom: 16px;
        box-sizing: border-box;
    }

    /* Styles for description paragraph */
    p.description {
        margin-top: -10px;
        margin-bottom: 16px;
        color: #666;
    }

    /* Styles for the submit button */
    button {
        background-color: #0073e6;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    /* Styles for button hover effect */
    button:hover {
        background-color: #0056b3;
    }
</style>
  <div class="wrap">
    <h1>SendCloud API Settings</h1>
    <!-- SendCloud API Settings Form -->
    <form method="post" action="">
        <!-- SendCloud API Token Input -->
        <label for="sendcloud_api_token">SendCloud API Token:</label>
        <input type="text" name="sendcloud_api_token" value="<?php echo esc_attr($current_credentials->token ?? ''); ?>" />
		
        <!-- SendCloud API Key Input -->
        <label for="sendcloud_api_key">SendCloud API Key:</label>
        <input type="text" name="sendcloud_api_key" value="<?php echo esc_attr($current_credentials->api_key ?? ''); ?>" />
		
        <!-- Description -->
        <p class="description">Enter your SendCloud API credentials.</p>

        <!-- Save Credentials Button -->
        <button type="submit" name="save_sendcloud_credentials" class="button-primary">Save Credentials</button>
    </form>
    <!-- End of SendCloud API Settings Form -->
</div>
    <?php
}

// Hook the function into the action
add_action('custom_label_button', 'display_create_label_button');
  // Function to display the "Create Label" or label printing button and form
function display_create_label_button($user_order_number) {
	 // Check if a success notice is set in the session if yes then display it
	 if (isset($_SESSION['sendcloud_success_notice']) AND !empty($_SESSION['sendcloud_success_notice'])) {
		 $sendcloud_success_alert=$_SESSION['sendcloud_success_alert'];
        sendcloud_success_notice($sendcloud_success_alert);  // Display the success notice
        unset($_SESSION['sendcloud_success_notice']);  // Clear the session variable
    }

	
	// Check if a label exists in the database againt the order number 
    if (is_label_exists_for_order($user_order_number)) {
		// Retrieve parcel information for the order
  $parcel_info = get_parcel_info_for_order($user_order_number);
   // If parcel information exists, extract details
	 if ($parcel_info) {
    $parcel_id = $parcel_info->parcel_id;
    $tracking_number = $parcel_info->tracking_url;
    $label_status = $parcel_info->status;
     $date_created = $parcel_info->date_created;
   
	 }
	 // Get order status
$order = new WC_Order($user_order_number);
$order_status = $order->get_status();
 // Check if the order is completed

if ($order_status == 'wc-completed' || $order_status == 'completed') 
	{
?>
 <!-- Displaying the completed  -->
   <div class="dokan-clearfix">
                <div class="" style="width:100%">
                    <div class="aw-wrap-order-buttons">
                        <div class="aw-cancel-order inactive">
                            <?php $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>
                            <span class="aw-order-button tips" title="" data-original-title="This order has been completed, so the full payout amount is already available on your withdrawable balance or paid to your bank account. If you want to refund the buyer, you will have to transfer the amount manually.">Refund</span>
                        </div>
                        <div class="aw-ship-order <?php echo ($order_status == 'wc-processing' || $order_status == 'processing') ? 'active' : 'inactive'; ?>">
                            <span class="aw-order-button">Completed</span>
                        </div>
                    </div>
                </div>
            </div>
			
<?php 
	}else{

/*
the following below code is to hide and display the buttons on the Basic of the time duration.
 Convert the date string to a DateTime object
*/
$created_time = new DateTime($date_created);
// Get the current time as a DateTime object
$current_time = new DateTime();
// Calculate the difference in seconds
$time_difference_seconds = $current_time->getTimestamp() - $created_time->getTimestamp();
// Check if the difference is more than 1 hour (3600 seconds)
if ($time_difference_seconds > 3600) {
  $display_cancel_shipping="style='display:none;'"; 
  $display_cancel_refund="style='display:block;'";
} else {
  $display_cancel_shipping="style='display:block;'"; 
  $display_cancel_refund="style='display:none;'";
}

// action Link 
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); 
        ?>
		<?php 
		// this will check if the label_status have value 1 or not if it have value 1 then will print else statement as the order or a label is canceled. 
		if($label_status!=1){ ?>
		 <a href="<?php echo esc_attr($tracking_number); ?>"  target="_blank"  class="button">Track Parcel</a>
		 <form method="get" style="margin-top:10px;" >
            <input type="hidden" name="parcelNumber" value="<?php echo esc_attr($parcel_id); ?>">
            <button type="submit" name="print_label_button" id="print-label-button" style="background-color:black;">Download Shipping label</button>
        </form>

               <div <?php echo $display_cancel_shipping; ?>>           
		 <form action="<?php echo $actual_link; ?>&refund_order=yes" method="POST" style="margin-top:10px;" >
            <input type="hidden" name="parcelNumber" value="<?php echo esc_attr($parcel_id); ?>">
            <button type="submit" name="cancel_shipping_label" id="cancel-label-button" onclick="return confirm('This action cannot be undone, are you sure you want to cancel/refund the order?')"
			style="background-color:red;" >Cancel Shipping</button>
        </form>
		</div>
		
		<div <?php echo $display_cancel_refund; ?>>           
		<?php  
		echo '<a onclick="return confirm(\'This action cannot be undone, are you sure you want to refund the order?\')" 
		href="' . $actual_link . '&refund_order=yes" class="dokan-btn" style="margin-top:10px; background-color:red; color:white;">Refund order</a>'; 
		?>
		</div>
			<?php }else if ($order_status=='refunded' OR $order_status=='Pending Cancel')
			{
				?>
					  <div class="dokan-clearfix">
                <div class="" style="width:100%">
                    <div class="aw-wrap-order-buttons">
                        <div class="aw-cancel-order active" hidden>
                            <?php $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>
                            <a onclick="return confirm('This action cannot be undone, are you sure you want to cancel/refund the order?')"
							href="<?php echo $actual_link; ?>&refund_order=yes" class="aw-order-button"><?php echo ($order_status == 'wc-processing' || $order_status == 'processing') ? 'Cancel' : 'Refund'; ?></a>
                        </div>
                        <div class="aw-ship-order <?php echo ($order_status == 'wc-processing' || $order_status == 'processing') ? 'active' : 'inactive'; ?>">
                            <span class="aw-order-button"><?php echo ($order_status == 'wc-refunded' || $order_status == 'refunded') ? 'Refunded' : 'Refund'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
				<?php 
			}
        } else {
            // Display the "Create Label" button and form
          ?>
            <form method="post">
                <input type="hidden" name="user_order_number" value="<?php echo esc_attr($user_order_number); ?>">
                <button type="submit" name="create_label_button" id="create-label-button">Create Label</button>
            </form>
            <?php
          
        }
        ?>

			
	
			 
    
			<?php 

	}
    }
}
// Hook the function into the action
add_action('custom_label_button', 'display_create_label_button');

// Function to handle cancellation form submission
function handle_cancel_shipping_submission() {
    if (isset($_POST['cancel_shipping_label'])) {     
		 $token = base64_encode(SENDCLOUD_API_TOKEN . ':' . SENDCLOUD_API_KEY);    
        $parcelNumber = isset($_POST['parcelNumber']) ? sanitize_text_field($_POST['parcelNumber']) : '';
		$deleted=delete_shipping_entry($parcelNumber);
        $apiUrl = 'https://panel.sendcloud.sc/api/v2/parcels/' . $parcelNumber . '/cancel';
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // Use POST method to cancel the parcel
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $token,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$deleted=delete_shipping_entry($parcelNumber);
	$message='';
     if ($httpCode == 404) {
			 if ($httpCode) {
        $message = $response['message'];
    }
      }else if ($httpCode==410){
	
		  if ($response && isset($response['status']) && $response['status'] === 'deleted') {
        $message = $response['message'];
    } else {
        $message = 'Parcel Delteted Sucessfully';
    }
	 }
	if($deleted){
	$message = 'Parcel Delteted Sucessfully';
	}        
	curl_close($ch);
$_SESSION['sendcloud_success_notice'] = true;
$_SESSION['sendcloud_success_alert']=$message;

    }
}
// this function will update the staus in the database of canceled label
function delete_shipping_entry($parcel_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cu_sendcloud_parcels';
$new_status=1;
$wpdb->update(
    $table_name,
    array('status' => $new_status),
    array('parcel_id' => $parcel_id),
    array('%d'), // Format for the new status
    array('%d') // Format for the parcel_id column
);
$order_number=esc_attr($_GET['order_id']);
$status_message='wc-refunded';
$table_name = $wpdb->prefix . 'posts'; 
$result = $wpdb->update(
    $table_name,
    array('post_status' => $status_message),
    array('ID' => $order_number),
    array('%s'), // Format for the post_status column
    array('%d')  // Format for the ID column
);
}
// Function to cancel shipping
function cancel_shipping_label($message) {
    ?>
    <div class="notice notice-success ocean-theme-notice is-dismissible">
        <p><?php echo $message; ?></p>
    </div>
    <?php
}



// Dunction which gives a label in in pdf agint the parcel id of Send cloud.
function download_and_display_label($parcelNumber) {
 $token = base64_encode(SENDCLOUD_API_TOKEN . ':' . SENDCLOUD_API_KEY);
   // Set the label URL
    $labelUrl = 'https://panel.sendcloud.sc/api/v2/labels/label_printer/' . $parcelNumber;
    $ch = curl_init($labelUrl);
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $token,
        'Content-Type: application/json',
    ]);
    // Execute cURL session
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        exit;
    }

    // Close cURL session
    curl_close($ch);

    // Check if the response contains an error message
    $data = json_decode($response, true);

    if (isset($data['error'])) {
        echo 'Error: ' . $data['error']['message'];
        exit;
    }

    // Save the label content to a PDF file
    $labelFileName = 'label_' . $parcelNumber . '.pdf';
    file_put_contents($labelFileName, $response);

    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $labelFileName . '"');
    header('Content-Length: ' . filesize($labelFileName));
    // Output the file content
    readfile($labelFileName);
    // Remove the file from directory after download
    unlink($labelFileName);
    exit;
}

// this function is used to pass the pfd file to the auto create label action 
function get_label_content($parcelNumber) {
    $token = base64_encode(SENDCLOUD_API_TOKEN . ':' . SENDCLOUD_API_KEY);

    // Set the label URL
    $labelUrl = 'https://panel.sendcloud.sc/api/v2/labels/label_printer/' . $parcelNumber;

    $ch = curl_init($labelUrl);
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $token,
        'Content-Type: application/json',
    ]);
    // Execute cURL session
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        exit;
    }

    // Close cURL session
    curl_close($ch);

    // Check if the response contains an error message
    $data = json_decode($response, true);

    if (isset($data['error'])) {
        echo 'Error: ' . $data['error']['message'];
        exit;
    }

    // Save the response to a PDF file
    $pdfFileName = 'label_' . $parcelNumber . '.pdf';
    file_put_contents($pdfFileName, $response);

    return $pdfFileName;
}





// Function to retrieve and display order details
function get_order_details($order) {
    $user_order_number = $order->get_id();
 $order_id = $order->get_id();
    $order_details['aw_order'] = wc_get_order($order_id);
    $billing_first_name = $order_details['aw_order']->get_billing_first_name();
    $billing_last_name = $order_details['aw_order']->get_billing_last_name();
    $customer_name = $billing_first_name . ' ' . $billing_last_name;
    $billing_email = esc_html(get_post_meta($order_id, '_billing_email', true));
    $billing_phone = esc_html(get_post_meta($order_id, '_billing_phone', true));
    $billing_country = esc_html(get_post_meta($order_id, '_billing_country', true));
    $billing_postcode = esc_html(get_post_meta($order_id, '_billing_postcode', true));
	
  if (!empty($order->get_formatted_shipping_address())) {
    $shipping_address_parts = $order->get_address();
    $filtered_address_parts = array_filter($shipping_address_parts, function ($part) {
        return !filter_var($part, FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?\d+$/', $part);
    });

 $postal_code = isset($shipping_address_parts['postcode']) ? $shipping_address_parts['postcode'] : '';
   $city = isset($shipping_address_parts['city']) ? $shipping_address_parts['city'] : '';
  $country_code = isset($shipping_address_parts['country']) ? $shipping_address_parts['country'] : '';
  $countries = WC()->countries->countries;
    $country = isset($countries[$country_code]) ? $countries[$country_code] : '';
    unset($filtered_address_parts['city']);
    unset($filtered_address_parts['country']);
    unset($filtered_address_parts['postcode']);
   $shipping_address = implode(' ', $filtered_address_parts);
     wp_kses_post($shipping_address . ' ' . $city . ' ' . $country);
}else if (!empty($order->get_formatted_billing_address())) {
	
    $billing_address_parts = $order->get_address();
    $filtered_address_parts = array_filter($billing_address_parts, function ($part) {
        return !filter_var($part, FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?\d+$/', $part);
    });
$postal_code = isset($shipping_address_parts['postcode']) ? $shipping_address_parts['postcode'] : '';
    $city = isset($billing_address_parts['city']) ? $billing_address_parts['city'] : '';
    $country_code = isset($billing_address_parts['country']) ? $billing_address_parts['country'] : '';
 $countries = WC()->countries->countries;
    $country = isset($countries[$country_code]) ? $countries[$country_code] : '';
 unset($filtered_address_parts['city']);
    unset($filtered_address_parts['country']);
    unset($filtered_address_parts['postcode']);
    $shipping_address = implode(' ', $filtered_address_parts);
wp_kses_post($shipping_address . ' ' . $city . ' ' . $country);
}		else {
        return esc_html__('No shipping address set.', 'dokan-lite');
    }
	
	//echo 'Postal COde is '.$postal_code."<br>";
	 return array(
            'user_order_number' => $user_order_number,
            'customer_name' => $customer_name,
            'billing_email' => $billing_email,
            'billing_phone' => $billing_phone,
            'billing_country' => $country_code,
            'billing_postcode' => $postal_code,
            'shipping_address' => $shipping_address,
            'city' => $city,
            'aw_order' => $order_details['aw_order'],  // Include the 'aw_order' key in the array
        );
	
}

// Function to check if a label exists for a given order number
function is_label_exists_for_order($order_number) 
{
    global $wpdb;
    $table_name = $wpdb->prefix.'cu_sendcloud_parcels';
    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE order_number = %d",
            $order_number
        )
    );
    return $result > 0;
}
// getting the parcel information from the databse againt the order number. for tracking the parcel.

function get_parcel_info_for_order($order_number) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cu_sendcloud_parcels';

    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT parcel_id, tracking_url,status,date_created FROM $table_name WHERE order_number = %d",
            $order_number
        )
    );

    return $result;
}


// SendCloud API integration function for creating the parcel in the send cloud.
function sendcloud_api_integration($order_details) {
	  $token = base64_encode(SENDCLOUD_API_TOKEN . ':' . SENDCLOUD_API_KEY);
    $apiUrl = 'https://panel.sendcloud.sc/api/v2/parcels';

    // Initialize parcel_items array
    $parcel_items = array();

// Initialize $post_author_id outside the loop
$post_author_id = 0; // or any default value

	foreach ($order_details['aw_order']->get_items() as $item_id => $item) {
		$product = $item->get_product();
		$post_author_id = get_post_field('post_author', $product->get_id());
		$product_name = $product->get_name();
		$quantity = $item->get_quantity();
		$value = $item->get_total();
		$sku = $product->get_sku();

		// Add each item as an array to parcel_items
		$parcel_items[] = array(
			'description' => $product_name,
			'quantity' => $quantity,
			'value' => $value,
			'sku' => $sku,
			'weight' => '2.000',  // Add weight if available
			// Add other item details as needed
		);
	}
 global $wpdb;

$selected_shipping_method_key = get_user_meta($post_author_id, 'cu_user_selected_Carier', true);	 
$vendor_info = dokan_get_store_info($post_author_id);
if ($vendor_info) {
		$store_name=$vendor_info['store_name'];
		if (isset($vendor_info['address'])) {
        $address_one=$vendor_info['address']['street_1'];
		
        $address_two=$vendor_info['address']['street_2'];
        $address_city=$vendor_info['address']['city'];
        $address_zip=$vendor_info['address']['zip'];
        $country=$vendor_info['address']['country'];
        $state=$vendor_info['address']['state'];
    }
}
	  $house_numer_vendor = preg_replace('/[^0-9]/', '', $vendor_info['address']['street_1'] . $vendor_info['address']['street_2'] . $vendor_info['address']['city']. $vendor_info['address']['country'] . $vendor_info['address']['state']);   
	 $address_without_number = str_replace($house_numer_vendor, '', $address_one);
	 $address2_without_number = str_replace($house_numer_vendor, '', $address_two);
	$house_numer_customer = preg_replace('/[^0-9]/', '', $order_details['shipping_address'] . $order_details['city']. $order_details['billing_country']);
	 $shipping_address=$order_details['shipping_address'];
	 $customer_name=$order_details['customer_name'];
	  $filter_address = str_replace($house_numer_customer, '', $shipping_address);
	 $shipping_adress_customer = str_replace($customer_name, '', $filter_address);
 $billing_country=$order_details['billing_country'];
$carriersss = display_carriers($selected_shipping_method_key,$billing_country);
foreach ($carriersss as $carrier) {
    $selected_shipping_method_name=$carrier['carriername'];
	$price=$carrier['price'];
$carrier_ids=$carrier['carrier_id'];
								  }
   // Shipment Data 
    $shipmentData = array(
        'name' => $order_details['customer_name'],
        'address' => $shipping_adress_customer,
        'city' => $order_details['city'],
        'postal_code' => $order_details['billing_postcode'],
        'country' => $order_details['billing_country'],
        'email' => $order_details['billing_email'],
        'telephone' => $order_details['billing_phone'],
        'house_number' => $house_numer_customer,
        'weight' => '2.000',
        'shipment' => array(
            'id' => $carrier_ids,
            'name' => $selected_shipping_method_name
						   ),
        'parcel_items' => $parcel_items,
        'order_number' => $order_details['user_order_number'],
        'request_label' => true,	
	'from_name' => '',
    'from_company_name' => $store_name,
    'from_address_1' => $address_without_number,
    'from_address_2' => $address2_without_number,
    'from_house_number' => $house_numer_vendor,
    'from_city' => $address_city,
    'from_postal_code' => $address_zip,
    'from_country' => $country,
    );
  $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('parcel' => $shipmentData))); // Wrap data in 'parcel' key
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $token,
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);
   $data = json_decode($response, true);
 
 if (isset($data['parcel'])) {
        $parcel_info = $data['parcel'];
 add_action('admin_notices', 'sendcloud_success_notice');
        // Extract the required information
        $parcel_id = $parcel_info['id'];
        $tracking_number = $parcel_info['tracking_number'];
        $order_number = $parcel_info['order_number'];
        $carrier_code = $parcel_info['carrier']['code'];
 $tracking_url = $parcel_info['tracking_url'];
 $date_created_raw = $parcel_info['date_created'];
        $date_created = date('Y-m-d H:i:s', strtotime($date_created_raw));
 $status_id = $parcel_info['status']['id'];
    $status_message = $parcel_info['status']['message'];
        global $wpdb;
        $table_name = $wpdb->prefix . 'cu_sendcloud_parcels';
   $result = $wpdb->insert(
        $table_name,
        array(
            'parcel_id' => $parcel_id,
            'tracking_number' => $tracking_number,
            'order_number' => $order_number,
            'carrier_code' => $carrier_code,
            'date_created' => $date_created,
            'tracking_url' => $tracking_url,
            'status_id' => $status_id, // Save status ID
            'status_message' => $status_message, // Save status message
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s') // Adjust the format placeholders accordingly
    );
		
     if ($result === false) {
    $wpdb_last_error = $wpdb->last_error;
     $_SESSION['sendcloud_success_alert']== 'Error While Saving Record: ' . $wpdb_last_error;
 }else{
	return $parcel_id;
	}
    }else{
		 $_SESSION['sendcloud_success_alert']='Error while creating Label !';
	}
}
// function which will display the alert on the vendor side.
function sendcloud_success_notice($sendcloud_success_alert) {
    ?>
   <div class="notice notice-success ocean-theme-notice is-dismissible">
    <p><?php echo $sendcloud_success_alert; ?></p>
</div>

    <?php
}
// Hook the form submission function

add_action('init', 'handle_cancel_shipping_submission');
// Hook the function into the action
add_action('custom_label_button', 'display_create_label_button');

function custom_shipping_styles() {
    wp_enqueue_style('custom-shipping-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
add_action('wp_enqueue_scripts', 'custom_shipping_styles');
/* 
The following below code is used to display the Shipping Courier in the vendor dashboard  and also make the user selction.
to display the form in the user side we use echo do_shortcode('[custom_shipping_form]');  
*/

// Shortcode to display the shipping form
function custom_shipping_form() {
    ob_start(); ?>

    <form method="post" action="">
        <?php
        $carriers = array(
            'dhl'    => 'DHL',
            'postnl' => 'PostNL',
            'dpd'    => 'DPD',
           // 'ups'    => 'UPS',
        );
 $plugin_url = plugin_dir_url(__FILE__); 
   $selected_shipping_method = '';
   
  if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $selected_shipping_method = get_user_meta($user_id, 'cu_user_selected_Carier', true);
        }
		foreach ($carriers as $key => $carrier) 
		{ 
		?>
		
            <label class="custom-radio">
                 <input type="radio" name="shipping" value="<?php echo esc_attr($key); ?>" <?php checked($selected_shipping_method, $key); ?>>
				<?php if($key=='postnl'){
					?>
<span class="radio-img"><svg id="Layer_1"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 75 74.2" width="40px" ><style>.st0{fill:none}.st1{fill:#a6acb2}.st2{fill:url(#SVGID_1_)}.st3{fill:#fff}.st4{fill:#383792}</style><path class="st0" d="M-37.5-37.8h150v150h-150z"/><path class="st1" d="M16.4.1c9 0 23.5 5.7 33.1 11C60.1 17 75 28.9 75 37.1c0 8.7-15.3 20.3-25.5 26-9.3 5.1-23.5 11.1-32.8 11.1-2.5 0-4.5-.4-6-1.2C3.4 68.9 0 51.5 0 37.1c0-7.3.9-15 2.6-21.4 2-7.6 4.8-12.6 8.1-14.5C12.1.5 14 .1 16.4.1"/><radialGradient id="SVGID_1_" cx="304.728" cy="1214.748" r="49.143" gradientTransform="translate(-283.6 -1185.99)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#ffc429"/><stop offset="1" stop-color="#f26f23"/></radialGradient><path class="st2" d="M72.5 37.3c0-6.2-11.7-17.1-24.2-24C33.8 5.4 17.4.8 12 3.7 5.9 7.1 2.5 24.1 2.5 37.3c0 13.3 3.2 30.1 9.5 33.6 5.8 3.2 21.5-1.5 36.2-9.7C61 54.3 72.5 43.9 72.5 37.3"/><path class="st3" d="M56.1 43.6l-.1-6.3c0-1.4-.5-2-1.6-2-.4 0-.9.1-1.3.4-.5.3-.8.5-1 .6l-.1.1v7.1s0 .1-.1.1h-2.8s-.1 0-.1-.1v-10c0-.2.1-.3.3-.3h2.5s.1 0 .1.1v.8s0 .1.1.1h.1l.1-.1c.3-.2.8-.5 1.1-.6.7-.3 1.4-.4 2-.4 2.3 0 3.5 1.3 3.5 3.8v6.7s0 .1-.1.1l-2.6-.1M61.3 43.6c-.1 0-.1 0 0 0l-.1-15.4c0-.1 0-.1.1-.1.2 0 1.7 1 2.2 1.5.4.4.6 1 .6 1.5v12.3s0 .1-.1.1h-2.7"/><path class="st4" d="M30.6 38.4c0 3.7-2.4 5.4-5.4 5.4-3 0-5.4-1.7-5.4-5.4s2.4-5.4 5.4-5.4c3 0 5.4 1.7 5.4 5.4m-3 0c0-2-1-2.8-2.3-2.8-1.3 0-2.3.9-2.3 2.8 0 1.8 1 2.8 2.3 2.8 1.3.1 2.3-.9 2.3-2.8zM44.7 30.4c0-.1 0-.1-.1-.1-.2 0-1.8 1-2.2 1.5-.4.4-.6 1-.6 1.5V40c0 2.9 1.7 3.8 3.5 3.8 1 0 1.7-.1 2.1-.4.1 0 .2-.1.2-.3v-1.9c0-.1 0-.1-.1-.1s-.8.3-1.2.3c-.9 0-1.5-.4-1.5-1.9v-3.8c0-.1 0-.1.1-.1h2.5c.1 0 .1 0 .1-.1v-2c0-.2-.1-.3-.3-.3h-2.3c-.1 0-.1 0-.1-.1l-.1-2.7M8.6 33.6c0-.2.2-.3.3-.3h3.9c3.9 0 5.9 2.3 5.9 5.3s-2.2 5.1-5.9 5.1h-1.2c-.1 0-.1 0-.1.1v4.9c0 .1 0 .1-.1.1-.2 0-1.8-1-2.2-1.5-.4-.4-.6-1-.6-1.5V33.6m7.2 4.8c0-1.3-.8-2.5-3-2.5h-1.2c-.1 0-.1 0-.1.1v4.8c0 .1 0 .1.1.1h1.2c2.6 0 3-1.8 3-2.5zM39 38c-.6-.4-1.4-.6-2.1-.7-.1 0-.6-.1-.7-.2-.9-.2-1.6-.4-1.6-.9s.5-.8 1.2-.8c.9 0 2.2.2 3.6.7.1 0 .2 0 .2-.1v-2c0-.1-.1-.3-.2-.3-.5-.2-2-.6-3.2-.6-1.4 0-2.5.3-3.3.9-.8.6-1.2 1.4-1.2 2.4 0 2.3 1.9 2.7 3.7 3.1.3.1.2.1.3.1.8.2 1.7.4 1.7 1 0 .2-.1.4-.2.5-.2.2-.6.3-1.2.3-1.1 0-3.3-.5-3.9-.7-.1 0-.1.1-.1.1v2c0 .1.1.3.2.3 0 0 1.9.6 3.6.6 3.1 0 4.7-1.2 4.7-3.4-.3-1-.7-1.8-1.5-2.3"/><path class="st3" d="M20.7 30.6c-.2 0-.4-.1-.4-.4V29c0-.3.2-.5.5-.5h8.9c.3 0 .5.2.5.5v1.1c0 .3-.1.4-.4.4h-9.1m8-3.1c-.1 0-.2 0-.2-.1v-.2c.3-.9 1.3-3.7 1.3-3.7.1-.2 0-.4-.2-.5l-.5-.2H29c-.1 0-.2 0-.2.1-.2.5-.3.8-.5 1.3 0 .1-.1.1-.2.1h-1c-.1 0-.1 0-.2-.1v-.2c.3-.9.5-1.6.9-2.4.1-.1.2-.4.6-.4h.2c.3.1.7.2 1.1.3.3.1.6.2 1 .3 1 .4 1 1.1.8 1.8-.1.4-.7 2.1-1.1 3.1-.1.2-.1.4-.2.5 0 .1-.1.2-.3.2h-1.2zm-4.1 0c-.2 0-.2-.2-.2-.3 0-.1-.1-5.3-.1-6 0-.1 0-.2.1-.2 0 0 .1-.1.2-.1H26c.1 0 .1 0 .2.1s.1.2.1.2c0 .7-.1 5.9-.1 6 0 0 0 .3-.2.3h-1.4zm-4.1 0c-.2 0-.2-.1-.3-.2 0-.1-.1-.3-.2-.5-.4-1-.9-2.6-1.1-3.1-.2-.7-.2-1.5.8-1.8.3-.1.7-.2 1-.3.4-.1.8-.2 1.1-.3h.2c.3 0 .5.2.6.4.4.8.6 1.5.9 2.4v.2s-.1.1-.2.1h-1c-.1 0-.2 0-.2-.1-.2-.5-.3-.9-.5-1.3 0 0-.1-.1-.2-.1h-.1l-.5.2c-.2.1-.3.3-.2.5 0 0 1 2.8 1.3 3.7v.2s-.1.1-.2.1c0-.1-1.2-.1-1.2-.1zm4.7-7.6c-.1 0-.1 0-.2-.1-.4-.3-.8-.7-1.1-1.1 0 0-.1-.1 0-.3.3-.5.8-.8 1.2-1.2h.2c.4.3.8.7 1.2 1.2.1.1.1.2 0 .3-.3.4-.7.8-1.1 1.1-.1.1-.1.1-.2.1z"/></svg></span>&nbsp;&nbsp;
					<?php 
				}else{ ?>
                <span class="radio-img"><img style="width:40px;" src="<?php echo esc_url($plugin_url.$key . '-logo.png'); ?>" alt="<?php echo esc_attr($carrier); ?>"></span>
                <?php } ?>
				<span class="radio-text"><?php echo esc_html($carrier); ?></span>
            </label>
        
		<?php } ?>
<br>
        <button type="submit" class="save-button">SAVE</button>
    </form>

    <?php
	$output = ob_get_clean();
    return $output;
}
add_shortcode('custom_shipping_form', 'custom_shipping_form');

// Save selected shipping method for a specific user
function save_shipping_method() {
    if (isset($_POST['shipping']) && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $selected_shipping_method = sanitize_text_field($_POST['shipping']);

        // Update the user meta field 'user_selectedCarier'
        update_user_meta($user_id, 'cu_user_selected_Carier', $selected_shipping_method);

	}
}

add_action('init', 'save_shipping_method');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
}



// Displaying on Checkout which method is selcted for shipping
function display_shipping_courier($atts) {
   global $sendcloudCarriers;

$plugin_url = plugin_dir_url(__FILE__); // Assuming this line is declared in your file
 $shipping_country = WC()->customer->get_shipping_country();
    $selected_shipping_method_key = get_user_meta($atts['user_id'], 'cu_user_selected_Carier', true);

 $billing_country=$shipping_country;
 $output='';

$carriersss = display_carriers($selected_shipping_method_key,$billing_country);
 global $custom_price;
$price='';
foreach ($carriersss as $carrier) {
    $selected_shipping_method_name=$carrier['name'];
	$price=$carrier['price'];
						}

    // Check if the selected shipping method key exists in the carriers array
    if (array_key_exists($selected_shipping_method_key, $sendcloudCarriers)) {
        $selected_shipping_method_name = $sendcloudCarriers[$selected_shipping_method_key];
        $carrier_logo_url = $plugin_url . $selected_shipping_method_key . '-logo.png';
        $output.= '<p style="font-size:8px;"><img src="' . esc_url($carrier_logo_url) . '" style="width:30px;" alt="' 
		. esc_attr($selected_shipping_method_name) . '"> This order will be shipped by ' . esc_html($selected_shipping_method_name) ;
		//$output .= ' - Price: ' . wc_price($price);
        $output .= '</p>';
if (!function_exists('custom_wc_shipping_method_label')) {
    function custom_wc_shipping_method_label($label, $method, $price) {
        // Add the provided price to the label
        $custom_label = wc_price($price);

        return $custom_label;
    }
}
if($price){
	$custom_price=$price;
}else{
$custom_price='Shipping not Avaibale';
}


add_filter('woocommerce_cart_shipping_method_full_label', function ($label, $method) use ($custom_price) {
    // Call the custom_wc_shipping_method_label function with the specified price
    return custom_wc_shipping_method_label($label, $method, $custom_price);
}, 99, 2);



        return $output;
    } else {
        // Handle the case where the selected shipping method key is not found
        return '<p style="font-size: 8px;">Invalid shipping method</p>';
    }

}

add_shortcode('display_shipping_courier', 'display_shipping_courier');

if (!class_exists('WC_Custom_Shipping_Method')) {
    class WC_Custom_Shipping_Method extends WC_Shipping_Method
    {
        private $dynamic_value;

        public function __construct($dynamic_value)
        {
            $this->id                 = 'custom_shipping_method';
            $this->method_title       = __('Custom Shipping Method', 'your-text-domain');
            $this->method_description = __('Description of your custom shipping method', 'your-text-domain');
            // Enable shipping method by default
            $this->enabled = 'yes';
            // Add your settings here

            // Set the dynamic value
            $this->dynamic_value = $dynamic_value;
        }

        public function calculate_shipping($package = array())
        {     
foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
   $product = $cart_item['data'];
   $product_id = $cart_item['product_id'];
   $variation_id = $cart_item['variation_id'];
   $quantity = $cart_item['quantity'];
 $post_author_id = get_post_field('post_author', $product_id);
}

		   $shipping_cost =pass_shipping_method($post_author_id); // Modify the calculation as needed
					
            // Create the shipping rate
            $rate = array(
                'id'    => $this->id,
                'label' => $this->method_title,
                'cost'  => $shipping_cost,
            );
	$this->add_rate($rate);
        }
    }
}




		  // echo $shipping_cost =test_data_function($post_author_id); // Modify the calculation as needed


	// Passing the data to the shipping class 	   
function pass_shipping_method($post_author_id){
	
global $sendcloudCarriers;

    $plugin_url = plugin_dir_url(__FILE__); // Assuming this line is declared in your file
 $shipping_country = WC()->customer->get_shipping_country();
    $selected_shipping_method_key = get_user_meta($post_author_id, 'cu_user_selected_Carier', true);

 $billing_country=$shipping_country;


$carriersss = display_carriers($selected_shipping_method_key,$billing_country);
 global $custom_price;
$price='';
foreach ($carriersss as $carrier) {
    $selected_shipping_method_name=$carrier['name'];
	$price=$carrier['price'];
						}

    // Check if the selected shipping method key exists in the carriers array
    if (array_key_exists($selected_shipping_method_key, $sendcloudCarriers)) {
        $selected_shipping_method_name = $sendcloudCarriers[$selected_shipping_method_key];
        $carrier_logo_url = $plugin_url . $selected_shipping_method_key . '-logo.png';
       
if (!function_exists('custom_wc_shipping_method_label')) {
    function custom_wc_shipping_method_label($label, $method, $price) {
        // Add the provided price to the label
        $custom_label = wc_price($price);

        return $custom_label;
    }
}
if($price){
	$custom_price=$price;
}else{
$custom_price='Shipping not Avaibale';
}

     return $custom_price;
    } else {
       
    }

	
	
	
}

add_shortcode('pass_shipping_method', 'pass_shipping_method');



// This function is used in the theme function to create auto label when order is set to be in processing.
function create_autolabel_on_order($order_number) {
    // Your logic for creating an auto label on order
    if ($order_number > 0) {
        $order = wc_get_order($order_number);
        if ($order) {	
            if (function_exists('dokan_get_prop')) {
                $order_details = get_order_details($order);
                // Check if the label already exists in the database
                $label_exists = is_label_exists_for_order($order_number);
                if (!$label_exists) {
                    // SendCloud API integration
                    $parcel_idss = sendcloud_api_integration($order_details);
					if($parcel_idss){
						$parcel_id = $parcel_idss;
					$labelPdfFileName = get_label_content($parcel_id);
				}
                 
					return $labelPdfFileName;
                } else {
                  
                    return 'Label already created for this order.';
                }
            } else {
                return 'Dokan plugin is not active. Please activate Dokan to use this feature.';
            }
        } else {
            return 'Invalid order number.';
        }
    } else {
        return 'Invalid order number.';
    }
}

// Hook the create_autolabel_on_order function to the updated action
add_action('create_autolabel_on_order', 'create_autolabel_on_order', 10, 1);





function get_sendcloud_shipping_methods() {
    $token = base64_encode(SENDCLOUD_API_TOKEN . ':' . SENDCLOUD_API_KEY);
    $apiUrl = 'https://panel.sendcloud.sc/api/v2/shipping_methods';

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $token,
    ]);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }

    curl_close($ch);

    if ($httpStatus !== 200) {
        echo 'Error: Unexpected HTTP status code - ' . $httpStatus;
        return false;
    }

    return json_decode($response, true);
}

function display_carriers($selected_shipping_method_key,$billing_country) {
	$billing_country_code = $billing_country;
$wc_countries = new WC_Countries();
$billing_country_name = $wc_countries->countries[ $billing_country_code ];
 $data = get_sendcloud_shipping_methods();
    if (!$data || !isset($data['shipping_methods'])) {
        return ['error' => 'Error decoding JSON response or missing "data" key.'];
    }
    $targetCarrier = $selected_shipping_method_key;
    $targetCountry = $billing_country_name;
  $targetShippingMethodsNames = ['PostNL Standard 0-23kg', 'DHL For You Dropoff - S', 'DPD Home 0-31.5kg'];
$targetShippingMethods = [];
foreach ($data['shipping_methods'] as $shippingMethod) {
    if (
        $shippingMethod['carrier'] == $targetCarrier &&
        in_array($targetCountry, array_column($shippingMethod['countries'], 'name')) &&
        in_array($shippingMethod['name'], $targetShippingMethodsNames)
    ) {
        $targetShippingMethods[] = [
            'id' => $shippingMethod['id'],
            'name' => $shippingMethod['name'],
            'carrier' => $shippingMethod['carrier'],
            'minWeight' => $shippingMethod['min_weight'],
            'maxWeight' => $shippingMethod['max_weight'],
            'servicePointInput' => $shippingMethod['service_point_input'],
           // 'price' => $shippingMethod['countries'][0]['price'],
            'priceWithoutInsurance' => 0, // Initialize the value
            'fuelSurcharge' => 0, // Initialize the value
            'seasonalSurcharge' => 0, // Initialize the value
        ];

        // Find and set the values of price_without_insurance, fuel surcharge, and seasonal surcharge
        foreach ($shippingMethod['countries'][0]['price_breakdown'] as $breakdown) {
            if ($breakdown['type'] == 'price_without_insurance') {
                $targetShippingMethods[count($targetShippingMethods) - 1]['priceWithoutInsurance'] = $breakdown['value'];
            } elseif ($breakdown['type'] == 'fuel') {
                $targetShippingMethods[count($targetShippingMethods) - 1]['fuelSurcharge'] = $breakdown['value'];
            } elseif ($breakdown['type'] == 'seasonal') {
                $targetShippingMethods[count($targetShippingMethods) - 1]['seasonalSurcharge'] = $breakdown['value'];
            }
        }
    }
}

$result = [];

foreach ($targetShippingMethods as $shippingMethod) {
    $totalPrice = $shippingMethod['priceWithoutInsurance'] + $shippingMethod['fuelSurcharge'] + $shippingMethod['seasonalSurcharge'];

    $result[] = [
        'name' => str_replace('0-23kg', '', $shippingMethod['name']),
        'carriername' => $shippingMethod['name'],
        'carrier_id' => $shippingMethod['id'],
        'carrier' => $shippingMethod['carrier'],
        //'price' => $shippingMethod['countries'][0]['price'],
        'priceWithoutInsurance' => $shippingMethod['priceWithoutInsurance'],
        'fuelSurcharge' => $shippingMethod['fuelSurcharge'],
        'seasonalSurcharge' => $shippingMethod['seasonalSurcharge'],
        'price' => $totalPrice,
    ];
}
return $result;
}


function get_shipping_currier($atts) {
    $carriers = array(
        'dhl'    => 'DHL',
        'postnl' => 'PostNL',
        'dpd'    => 'DPD',
        'ups'    => 'UPS',
    );
$selected_shipping_method_key = get_user_meta($atts, 'cu_user_selected_Carier', true);
$billing_country='';
$carriersss = display_carriers($selected_shipping_method_key,$billing_country);

foreach ($carriersss as $carrier) {
    $selected_shipping_method_name=$carrier['carriername'];
	$price=$carrier['price'];
$carrier_id=$carrier['carrier_id'];
								  }
 $result[] = [
            'names' => $selected_shipping_method_name,            
            'carrier_ids' => $carrier_id,  
            'price' => $price,
        ];
	
		  return $result;
}


function add_custom_shipping_method($methods, $dynamic_value)
{
	$methods['custom_shipping_method'] = new WC_Custom_Shipping_Method($dynamic_value);
    return $methods;
}
// Call the function with your desired dynamic value
add_filter('woocommerce_shipping_methods', function ($methods)  use ($custom_price){
    $dynamic_value = 5;
return add_custom_shipping_method($methods, $custom_price);
});
