<?php
// Heading
$_['heading_title'] = 'BlockBee';

$_['title'] = 'Title';

$_['blockchain_fees'] = 'Add the blockchain fee to the order';
$_['fees'] = 'Service fee manager fees';

$_['never'] = 'Never';


// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified your BlockBee details!';
$_['text_edit'] = 'Edit BlockBee';
$_['text_blockbee'] = '<a target="_BLANK" href="https://blockbee.io/"><img src="view/image/payment/blockbee.png" alt="blockbee" title="blockbee" style="border: 1px solid #EEEEEE; height:35px" /></a>';
$_['text_connect_blockbee'] = 'This module allows you to accept BlockBee Payments securely.';
$_['text_blockbee_image'] = '<a target="_BLANK" href="https://blockbee.io/"><img src="view/image/payment/blockbee.png" alt="blockbee" title="blockbee" class="img-fluid" style="height:60px; margin-left:12px" /></a>';
$_['text_blockbee_suppport'] = 'If you need any help or have any suggestion, contact us via the live chat on our <a target="_blank" href="https://blockbee.io">website</a>';
$_['text_blockchain_fees'] = 'This will add an estimation of the blockchain fee to the order value';
$_['text_fees'] = 'Set the BlockBee service fee you want to charge the costumer. Note: Fee you want to charge your costumers (to cover BlockBee\'s fees fully or partially)';
$_['text_qrcode'] = 'Select how you want to show the QR Code to the user. Either select a default to show first, or hide one of them.';
$_['text_btc'] = 'Bitcoin';
$_['text_refresh_values'] = 'The system will automatically update the conversion value of the invoices (with real-time data), every X minutes. This feature is helpful whenever a customer takes long time to pay a generated invoice and the selected crypto a volatile coin/token (not stable coin). Warning: Setting this setting to none might create conversion issues, as we advise you to keep it at 5 minutes.';
$_['text_order_cancelation_timeout'] = 'Selects the amount of time the user has to pay for the order. When this time is over, order will be marked as \'Cancelled\' and every paid value will be ignored. Notice: If the user still sends money to the generated address, value will still be redirected to you. Warning: We do not advice more than 1 Hour.';


// Entry
$_['entry_cryptocurrencies'] = 'Accepted Cryptocurrencies';
$_['entry_btc_address'] = $_['text_btc'] . ' Address';

$_['entry_order_status'] = 'Order status';
$_['entry_status'] = 'Status';

$_['branding'] = 'Show BlockBee logo and credits below the QR code';

$_['qrcode_default'] = 'Show QR Code';
$_['qrcode'] = 'QR Code to show';
$_['qrcode_size'] = 'QR Code size';
$_['qrcode_without_ammount'] = 'Default Without Amount';
$_['qrcode_ammount'] = 'Default Amount';
$_['qrcode_hide_ammount'] = 'Hide Amount';
$_['qrcode_hide_without_ammount'] = 'Hide Without Amount';

$_['color_scheme'] = 'Color Scheme';
$_['scheme_light'] = 'Light';
$_['scheme_dark'] = 'Dark';
$_['scheme_auto'] = 'Auto';

$_['refresh_values'] = 'Refresh converted value';
$_['five_minutes'] = 'Every 5 Minutes';
$_['ten_minutes'] = 'Every 10 Minutes';
$_['fifteen_minutes'] = 'Every 15 Minutes';
$_['thirty_minutes'] = 'Every 30 Minutes';
$_['forty_five_minutes'] = 'Every 45 Minutes';
$_['sixty_minutes'] = 'Every 60 Minutes';

$_['order_cancelation_timeout'] = 'Order cancelation timeout';
$_['one_hour'] = '1 Hour';
$_['six_hours'] = '6 Hours';
$_['twelve_hours'] = '12 Hours';
$_['eighteen_hours'] = '18 Hours';
$_['twenty_four_hours'] = '24 Hours';

$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_sort_order'] = 'Sort order';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify the BlockBee payment module';

// Help hints
$_['help_cryptocurrencies'] = 'If you are using BlockBee you can choose if setting the receiving addresses here bellow or in your BlockBee settings page.<br/>In order to set the addresses on plugin settings, you need to select “Address Override” while creating the API key.<br/>In order to set the addresses on BlockBee settings, you need to NOT select “Address Override” while creating the API key.';
$_['help_cryptocurrency'] = 'Click the checkbox to enable the cryptocurrency';


// Order page - payment tab
$_['text_payment_info'] = 'Payment information';

$_['disable_conversion'] = 'Disable Conversion';
$_['disable_conversion_warn_bold'] = 'Attention: This option will disable the price conversion for ALL cryptocurrencies!';
$_['disable_conversion_warn'] = 'If you check this, pricing will not be converted from the currency of your shop to the cryptocurrency selected by the user, and users will be requested to pay the same value as shown on your shop, regardless of the cryptocurrency selected';


$_['api_key'] = 'API Key';
$_['api_key_info'] = "Insert here your BlockBee API Key. You can get one with BlockBee. Notice: If API permission 'Address Override' is not enabled you must set the address in the dashboard otherwise payments may fail.";
