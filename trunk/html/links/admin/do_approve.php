<html>
  <head>
    <link rel="stylesheet" type="text/css" href="../wwi.css">
    <title>The Great War : Links : Process Approval</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <base href="http://reagan.library.emory.edu/rebecca/wwiweb/">
  </head>
<body>

<?php
// run everything as if one directory up
chdir("..");
include_once ("lib/alinkRecord.class.php");
include_once("lib/mybreadcrumb.php");


$myargs = array('host' => "vip.library.emory.edu",
		'db' => "WW1",
		'coll' => 'links',
		'debug' => false);



include("header.html");
print "<p class='breadcrumbs'>" . $breadcrumb->show_breadcrumb() . "</p>";

print '<div class="content">'; 

print '<h2>Processing link record approval</h2>'; 

// each id is a record to approve, if val=on
foreach ($_GET as $id => $val) {
  if ($val != "on") {
    next; //probably should not happen
  }
  // spaces in the ids are getting converted to underscores
  $id = str_replace("_", " ", $id);
  $myargs[id] = $id;
  $newlink[$id] = new alinkRecord($myargs);
  $newlink[$id]->taminoGetRecord();	// initialize link
  $newlink[$id]->approve();
}


print "</div>";

print '<div class="sidebar">';
include("searchbox.html");
print "</div>";

include("footer.html");


?>

</body>
</html>

