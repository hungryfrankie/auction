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

include "includes/config.inc.php";
include $include_path . "auctionstoshow.inc.php";

$NOW = time();
$NOWB = gmdate('Ymd');
// // If user is not logged in redirect to login page
if (!isset($_SESSION['WEBID_LOGGED_IN'])) {
    header("Location: user_login.php");
    exit;
}
// // DELETE OR CLOSE OPEN AUCTIONS
if (isset($_POST['action']) && $_POST['action'] == "delopenauctions") {
    if (is_array($_POST['O_delete'])) {
        while (list($k, $v) = each($_POST['O_delete'])) {
            $v = str_replace('..', '', htmlspecialchars($v));
            // // Pictures Gallery
            if (file_exists($upload_path . "/$v")) {
                if ($dir = @opendir($upload_path . "/$v")) {
                    while ($file = readdir($dir)) {
                        if ($file != "." && $file != "..") {
                            @unlink($upload_path . "/$v" . $file);
                        }
                    }
                    closedir($dir);

                    @rmdir($upload_path . "/$v");
                }
            }
            // //
            $query = "SELECT photo_uploaded,pict_url FROM " . $DBPrefix . "auctions WHERE id='$v'";
            $res_ = mysql_query($query);
            $system->check_mysql($res_, $query, __LINE__, __FILE__);
            if (mysql_num_rows($res_) > 0) {
                $pict_url = mysql_result($res_, 0, "pict_url");
                $photo_uploaded = mysql_result($res_, 0, "photo_uploaded");
                // // Uploaded picture
                if ($photo_uploaded) {
                    @unlink($upload_path . $pict_url);
                }
            }
            // // Delete Invited Users List and Black Lists associated with this auction ---------------------------
            @mysql_query("DELETE FROM " . $DBPrefix . "auctioninvitedlists WHERE auction_id='$v'");
            @mysql_query("DELETE FROM " . $DBPrefix . "auccounter WHERE auction_id='$v'");
            // // Auction
            $query = "DELETE FROM " . $DBPrefix . "auctions WHERE id='$v'";
            $res = mysql_query($query);
            $system->check_mysql($res, $query, __LINE__, __FILE__);
            // // Update counters
            include $include_path . "updatecounters.inc.php";
        }
    }

    if (is_array($_POST['startnow'])) {
        while (list($k, $v) = each($_POST['startnow'])) {
            // // Update end time to "now"
            @mysql_query("UPDATE " . $DBPrefix . "auctions SET starts='" . $NOW . "' WHERE id='$v'");
        }
    }
}
// // Retrieve active auctions from the database
$TOTALAUCTIONS = mysql_result(mysql_query("select count(id) as COUNT from " . $DBPrefix . "auctions WHERE user='" . $_SESSION['WEBID_LOGGED_IN'] . "' and starts>" . $NOW . " AND suspended=0"), 0, "COUNT");

if (!isset($_GET['PAGE']) || $_GET['PAGE'] < 0 || empty($_GET['PAGE'])) {
    $OFFSET = 0;
    $PAGE = 1;
} else {
    $OFFSET = ($_GET['PAGE'] - 1) * $LIMIT;
    $PAGE = $_GET['PAGE'];
}
$PAGES = ceil($TOTALAUCTIONS / $LIMIT);
if (!$PAGES) $PAGES = 1;
$_SESSION['backtolist_page'] = $PAGE;
$_SESSION['backtolist'] = 'yourauctions_p.php';
// Handle columns sorting variables
if (!isset($_SESSION['pa_ord']) && empty($_GET['pa_ord'])) {
    $_SESSION['pa_ord'] = "title";
    $_SESSION['pa_type'] = "asc";
} elseif (!empty($_GET['pa_ord'])) {
    $_SESSION['pa_ord'] = str_replace('..', '', addslashes(htmlspecialchars($_GET['pa_ord'])));
    $_SESSION['pa_type'] = str_replace('..', '', addslashes(htmlspecialchars($_GET['pa_type'])));
} elseif (isset($_SESSION['pa_ord']) && empty($_GET['pa_ord'])) {
    $_SESSION['pa_nexttype'] = $_SESSION['pa_type'];
}
if ($_SESSION['pa_nexttype'] == "desc") {
    $_SESSION['pa_nexttype'] = "asc";
} else {
    $_SESSION['pa_nexttype'] = "desc";
}

if ($_SESSION['pa_type'] == "desc") {
    $_SESSION['pa_type_img'] = "<img src=\"images/arrow_up.gif\" align=\"center\" hspace=\"2\" border=\"0\" />";
} else {
    $_SESSION['pa_type_img'] = "<img src=\"images/arrow_down.gif\" align=\"center\" hspace=\"2\" border=\"0\" />";
}
$query = "SELECT DISTINCT id, title, current_bid, starts, ends, minimum_bid, duration, relist, relisted
			FROM " . $DBPrefix . "auctions au
			WHERE user='" . $_SESSION['WEBID_LOGGED_IN'] . "'
				AND starts > '" . $NOW . "'
				AND (suspended=0 OR suspended=-1)
			ORDER BY " . $_SESSION['pa_ord'] . " " . $_SESSION['pa_type'] . " LIMIT $OFFSET,$LIMIT";
$res = mysql_query($query);
$system->check_mysql($res, $query, __LINE__, __FILE__);

$i = 0;
while ($item = mysql_fetch_array($res)) {
    $template->assign_block_vars('items', array(
            'BGCOLOUR' => ($i % 2) ? '#FFCCFF' : '#EEEEEE',
            'ID' => $item['id'],
            'TITLE' => $item['title'],
            'STARTS' => FormatDate($item['starts']),
            'ENDS' => FormatDate($item['ends']),

            'B_HASNOBIDS' => ($item['current_bid'] == 0)
            ));
    $i++;
}
// get pagenation
$PREV = intval($PAGE - 1);
$NEXT = intval($PAGE + 1);
if ($PAGES > 1) {
    $LOW = $PAGE - 5;
    if ($LOW <= 0) $LOW = 1;
    $COUNTER = $LOW;
    while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6)) {
        $template->assign_block_vars('pages', array(
                'PAGE' => ($PAGE == $COUNTER) ? '<b>' . $COUNTER . '</b>' : '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_p.php?PAGE=' . $COUNTER . '&id=' . $id . '"><u>' . $COUNTER . '</u></a>'
                ));
        $COUNTER++;
    }
}

$template->assign_vars(array(
        'BGCOLOUR' => ($i % 2) ? '#FFCCFF' : '#EEEEEE',
        'TBLHEADERCOLOUR' => $system->SETTINGS['tableheadercolor'],
        'ORDERCOL' => $_SESSION['pa_ord'],
        'ORDERNEXT' => $_SESSION['pa_nexttype'],
        'ORDERTYPEIMG' => $_SESSION['pa_type_img'],

        'PREV' => ($PAGES > 1 && $PAGE > 1) ? '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_p.php?PAGE=' . $PREV . '&id=' . $id . '"><u>' . $MSG['5119'] . '</u></a>&nbsp;&nbsp;' : '',
        'NEXT' => ($PAGE < $PAGES) ? '<a href="' . $system->SETTINGS['siteurl'] . 'yourauctions_p.php?PAGE=' . $NEXT . '&id=' . $id . '"><u>' . $MSG['5120'] . '</u></a>' : '',
        'PAGE' => $PAGE,
        'PAGES' => $PAGES,

        'B_AREITEMS' => ($i > 0)
        ));

include "header.php";
$TMP_usmenutitle = $MSG['25_0115'];
include "includes/user_cp.php";
$template->set_filenames(array(
        'body' => 'yourauctions_p.html'
        ));
$template->display('body');
include "footer.php";

?>