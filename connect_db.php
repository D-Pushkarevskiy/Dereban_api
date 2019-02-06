<?php
include("./adodb5/adodb.inc.php");

$db = ADONewConnection(dbsdriver);
$db->charSet = "UTF8";
$db->dialect = 3;
$db->fmtDate = "d.m.Y";
$db->fmtTimeStamp = "d.m.Y H:i:s";

$db->Connect(dbshost, dbsuname, dbspass, dbsname);

?>
