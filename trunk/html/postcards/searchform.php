<?php
include("../config.php");	

print "<html>
  <head> 
    $csslink
    <title>The Great War : Postcards : Advanced Search</title>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
    <base href='$base_url'>
  </head> 
<body> 
"; 

include_once("lib/mybreadcrumb.php");
include_once("lib/taminoConnection.class.php");

$args = array('host' => $tamino_server,
	      'db' => $tamino_db,
      	      'coll' => $tamino_coll['postcards'],
	      'basedir' => $basedir,
	      'debug' => false,
	      );
$tamino = new taminoConnection($args);

$cat_query = 'for $a in input()/TEI.2/:text/back/:div//interpGrp 
return $a'; 
$cat_xsl = "interp.xsl";
$cat_params = array("mode" => "form");

$tamino->xquery($cat_query);
$tamino->xslTransform($cat_xsl, $cat_params);

include("header.php");

print "<p class='breadcrumbs'>" . $breadcrumb->show_breadcrumb();

print '
<div class="content">
<form name="postcardquery" action="postcards/search.php" method="get">
<table class="searchform" border="0">
<tr><th>Title</th><td><input type="text" size="40" name="title"></td></tr>
<tr><th>Description</th><td><input type="text" size="40" name="figdesc"></td></tr>
<tr><th colspan="2" class="label">Categories</th></tr>';
$tamino->printResult();

print '
</tr></td>
</table>
<input type="submit" value="Submit"> 
<input type="reset" value="Reset">
</form>';


print '</div>';

print '<div class="sidebar">';
include("postcards/nav.html");
include("searchbox.php");
print '</div>';

include("footer.html");

?>

</body>
</html>
