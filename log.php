<?php 
require_once('../../../wp-config.php');
require_once('../../../wp-settings.php');
if($_POST['log']!='')
{
$myFile = WP_CONTENT_DIR."/plugins/attributor-fairshare-plugin/fairshare-widget.log";
$fh = fopen($myFile, 'a');
fwrite($fh, $_POST['log']);
fclose($fh);
}
?>