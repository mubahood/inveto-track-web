<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use App\Models\Utils;
use Encore\Admin\Facades\Admin;

Utils::importRecs();

/* $msg = "Your ICT4Farmers vendor registration request has been received, we are going to review and get back to you shortly.";
Utils::send_sms([
    'to' => $phone_number,
    'message' => $msg
]); */
$u = Admin::user();
$review_link = admin_url('system-users/' . $u->id . '/edit');
$mail_body = <<<EOD
                    <p>Dear Admin,</p>
                    <p>New vendor registration request from {$u->name}.</p>
                    <p>Business Name: {$u->business_name}</p>
                    <p>Business Address: {$u->business_address}</p>
                    <p>Business Phone Number: {$u->business_phone_number}</p>
                    <p>Business Category: {$u->business_category}</p>
                    <p>Location: {$u->location_id}</p>
                    <p>Phone Number: {$u->phone_number}</p>
                    <p>Email: {$u->email}</p>
                    <p>Click <a href="{$review_link}">here</a> to review this request.</p>
                    <p>Thank you.</p>
                EOD;
$data['email'] = [
    'mubahood360@gmail.com',
];
$date = date('Y-m-d');
$data['subject'] = env('APP_NAME') . " - New Vendor Registration Request: " . $u->business_name . " at " . $date;
$data['body'] = $mail_body;
$data['data'] = $data['body'];
$data['name'] = 'Admin';
try {
    Utils::mail_sender($data);
    die('Sent'); 
} catch (\Throwable $th) {
    throw $th;
}
die('ok');

Encore\Admin\Form::forget(['map', 'editor']);
$u = Admin::user();
if ($u != null) {
    Utils::generate_dummy($u);
}
