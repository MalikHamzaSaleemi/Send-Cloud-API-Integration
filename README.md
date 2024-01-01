# SendCloud Integration Plugin README FILE


## Introduction

This plugin is designed to integrate with the Dokan plugin and requires Dokan to be installed and activated. Upon activation, the plugin will upload three tables to the database, namely:

1. **cu_sendcloud_webhook**: Contains webhook calls.
2. **cu_sendcloud_credentials**: Stores API keys of SendCloud.
3. **cu_sendcloud_parcels**: Contains parcel entries in the Database.

## Usage

### Display Orders Details in Vendor Side

To display the order details in the vendor dashboard, use the following code snippet:

```php
 $user_order_number = esc_attr(dokan_get_prop($order, 'id'));
if (function_exists('display_create_label_button')) {
    display_create_label_button($user_order_number);
}

```

### Display Carriers List to the Vendor

To display the list of carriers chosen by the seller in the vendor dashboard, use the following code snippet:

```php
 echo do_shortcode('[custom_shipping_form]');
```

### Override or Display Shipping Price in WooCommerce

Use the following code in WooCommerce to override or display the shipping price. The `post_author_id` is used to post the user ID of the vendor of the product.

```php
 function custom_wc_shipping_package_name($package_name, $i, $package, $post_author_id) {
    $custom_package_name = __('Shipping', 'woocommerce') . "
" . do_shortcode('[display_shipping_courier user_id="' . $post_author_id . '"]');
    return $custom_package_name;
}
add_filter('woocommerce_shipping_package_name', function ($package_name, $i, $package) use ($post_author_id) {
    return custom_wc_shipping_package_name($package_name, $i, $package, $post_author_id);
}, 99, 3);

```

### Auto Create Label on Successful Payment

To automatically create a label when the payment is successful, use the following code snippet:

```php
 $pdfFileName = create_autolabel_on_order($order_id_to_trigger);

```

This will create a label on SendCloud and return the PDF file, which can be used to send an email to the vendor.

## Note

Make sure to configure the SendCloud API keys in the `cu_sendcloud_credentials` table for proper integration.

Feel free to reach out for any issues or further assistance. Happy shipping!
