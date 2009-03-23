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

$error = true;
require('includes/config.inc.php');

$template->assign_vars(array(
        'ERROR' => print_r($_SESSION['SESSION_ERROR'], true),
        'ERRORTXT' => $system->SETTINGS['errortext'],
        'ERRORMAIL' => $system->SETTINGS['errormail']
        ));

require("header.php");
$template->set_filenames(array(
        'body' => 'error.html'
        ));
$template->display('body');
require("footer.php");

?>