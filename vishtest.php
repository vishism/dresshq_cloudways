<?php 
echo"VISH";

$content = file_get_contents("https://system.netsuite.com/core/media/media.nl?id=1538732&c=3523680&h=206ef826b9f409762894&_xt=.xml");

file_put_contents("newstuffrss.xml", $content);

?>