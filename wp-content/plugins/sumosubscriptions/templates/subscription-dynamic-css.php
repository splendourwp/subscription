<?php
global $woocommerce;

$custom_bgcolor = sumosubs_get_custom_bgcolor() ;
?>

li.processing  div.note_content{
    background-color:<?php echo $custom_bgcolor['n_processing']; ?> !important;
}
li.success  div.note_content{
    background-color:<?php echo $custom_bgcolor['n_success']; ?> !important;
}
li.pending  div.note_content{
    background-color:<?php echo $custom_bgcolor['n_pending']; ?> !important;
}
li.failure  div.note_content{
    background-color: <?php echo $custom_bgcolor['n_failure']; ?> !important;
}
li .note_content::after {
    border-style: solid !important;
    border-width:10px 10px  0 0  !important;
    bottom: -10px !important;
    content: "" !important;
    display: block !important;
    height: 0 !important;
    left: 20px !important;
    position: absolute !important;
    width: 0 !important;
}
li.processing .note_content::after {
    border-color: <?php echo $custom_bgcolor['n_processing']; ?> transparent !important;
}
li.success .note_content::after {
    border-color: <?php echo $custom_bgcolor['n_success']; ?> transparent !important;
}
li.pending .note_content::after {
    border-color: <?php echo $custom_bgcolor['n_pending']; ?> transparent !important;
}
li.failure .note_content::after {
    border-color: <?php echo $custom_bgcolor['n_failure']; ?> transparent !important;
}
mark{
    font: 13px arial, sans-serif;
    text-align: center;
    display:table-cell;
    border-radius: 15px;
    padding:4px 6px 4px 6px;
}
mark.Pending{
    background-color:#<?php echo $custom_bgcolor['_pending']; ?>;
    color:white;
}
mark.Trial{
    background-color:#<?php echo $custom_bgcolor['_trial']; ?>;
    color:white;
}
mark.Pause{
    background-color:#<?php echo $custom_bgcolor['_pause']; ?>;
    color:white;
}
mark.Failed{
    background-color:#<?php echo $custom_bgcolor['_failed']; ?>;
    color:white;
}
mark.Active-Subscription{
    background-color:#<?php echo $custom_bgcolor['_active']; ?>;
    color:white;
}
mark.Overdue{
    background-color:#<?php echo $custom_bgcolor['_overdue']; ?>;
    color:white;
}
mark.Suspended{
    background-color:#<?php echo $custom_bgcolor['_suspended']; ?>;
    color:red;
}
mark.Cancelled{
    background-color:#<?php echo $custom_bgcolor['_cancelled']; ?>;
    color:white;
}
mark.Pending_Cancellation{
    background-color:#<?php echo $custom_bgcolor['_pending_cancel']; ?>;
    color:white;
}
mark.Pending_Authorization{
    background-color:#<?php echo $custom_bgcolor['_pending_authorization']; ?>;
    color:white;
}
mark.Expired{
    background-color:#<?php echo $custom_bgcolor['_expired']; ?>;
    color:white;
}
.sumo-entry-title{
    font-weight: normal;
    margin: 0 0 5px;
}
.sumo_alert_box {
    border-radius:20px;
    font-family:Tahoma,Geneva,Arial,sans-serif;
    font-size:14px;
    padding:10px 10px 10px 20px;
    margin:20px;
}
.sumo_alert_box span {
    font-weight:bold;
}
.error {
    background:#ffecec;
    border:2px solid #f5aca6;
}
._success {
    background:#e9ffd9;
    border:2px solid #a6ca8a;
}
.warning {
    background:#fff8c4;
    border:2px solid #f2c779;
}
.notice {
    background:#e3f7fc;
    border:2px solid #8ed9f6;
}
.thumb {
    padding-bottom: 1.5em;
    text-align: left;
    width: 38px;
}
.name{
    border-bottom: 1px solid #f8f8f8;
    line-height: 1.5em;
    padding: 1.5em 1em 1em;
    text-align: left;
    vertical-align: middle;
}
.item {
    -moz-user-select: none;
    background: #f8f8f8 none repeat scroll 0 0;
    font-weight: 400;
    padding: 1em;
    text-align: left;
 }
 .view {
    white-space: nowrap;
    text-align:right;
 }
 .subscription_item{
    font-weight: 400;
    text-align:right !important;
 }
 tr:last-child td {
    border-bottom: 1px solid #dfdfdf;
 }
 .woocommerce table.my_account_orders .button {
    margin: 2px;
 }
 .sumo_alert_box {
    display:none;
 }