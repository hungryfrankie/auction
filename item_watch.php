<?php
/***************************************************************************
 *   copyright				: (C) 2008 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

require('includes/config.inc.php');
include $include_path . "browseitems.inc.php";
include $include_path . 'dates.inc.php';
// If user is not logged in redirect to login page
if (!isset($_SESSION['WEBID_LOGGED_IN'])) {
    header("location: user_login.php");
    exit;
}
// Auction id is present, now update table
if (isset($_GET['add']) && !empty($_GET['add'])) {
    // Check if this item is not already added
    $query = "SELECT item_watch from " . $DBPrefix . "users WHERE nick='" . $system->cleanvars($_SESSION['WEBID_LOGGED_IN_USERNAME']) . "'";
    $result = mysql_query($query);
    $system->check_mysql($result, $query, __LINE__, __FILE__);
    $items = trim(mysql_result ($result, 0, "item_watch"));
    $match = strstr($items, $_GET['add']);

    if (!$match) {
        $item_watch = trim($items . ' ' . $_GET['add']);
        $item_watch_new = trim($item_watch);
        $query = "UPDATE " . $DBPrefix . "users SET item_watch = '" . addslashes($item_watch_new) . "', reg_date = reg_date WHERE nick = '" . $system->cleanvars($_SESSION['WEBID_LOGGED_IN_USERNAME']) . "' ";
        $result = mysql_query($query);
        $system->check_mysql($result, $query, __LINE__, __FILE__);
    }
}
// Delete item form item watch
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $query = "SELECT item_watch FROM " . $DBPrefix . "users WHERE nick = '" . $system->cleanvars($_SESSION['WEBID_LOGGED_IN_USERNAME']) . "'";
    $result = mysql_query($query);
    $system->check_mysql($result, $query, __LINE__, __FILE__);
    $items = trim(mysql_result ($result, 0, 'item_watch'));

    $auc_id = split(" ", $items);
    for ($j = 0; $j < count($auc_id); $j++) {
        $match = strstr($auc_id[$j], $_GET['delete']);
        if ($match) {
            $item_watch = $item_watch;
        } else {
            $item_watch = $auc_id[$j] . ' ' . $item_watch;
        }
    }
    $item_watch_new = trim($item_watch);
    $query = "UPDATE " . $DBPrefix . "users SET item_watch='$item_watch_new', reg_date=reg_date WHERE nick='" . $system->cleanvars($_SESSION['WEBID_LOGGED_IN_USERNAME']) . "' ";
    $result = mysql_query($query);
    $system->check_mysql($result, $query, __LINE__, __FILE__);
}
// Show results
$query = "SELECT item_watch from " . $DBPrefix . "users WHERE nick='" . $system->cleanvars($_SESSION['WEBID_LOGGED_IN_USERNAME']) . "' ";
$result = mysql_query($query);
$system->check_mysql($result, $query, __LINE__, __FILE__);

$TPL_auctions_list_value = array();
$items = trim(mysql_result ($result, 0, "item_watch"));
if (mysql_num_rows($result) > 0) $HasResults = true;
if ($items != "" && $items != null) {
    $item = split(" ", $items);
    $itemids = '0';
    for ($j = 0; $j < count($item); $j++) {
        $itemids .= ',' . $item[$j];
    }
    $query = "SELECT * from " . $DBPrefix . "auctions WHERE id IN ($itemids)";
    $result = mysql_query($query);
    $system->check_mysql($result, $query, __LINE__, __FILE__);
    if (mysql_num_rows($result) > 0) {
        browseItems($result, 'item_watch.php');
    }
}

include "header.php";
$TMP_usmenutitle = $MSG['472'];
include "includes/user_cp.php";
$template->set_filenames(array(
        'body' => 'item_watch.html'
        ));
$template->display('body');
include "footer.php";

?>