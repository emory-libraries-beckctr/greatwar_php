<?php
include("../../config.php");	

print "<html>
  <head>
    $csslink
    <title>The Great War : Admin : Links : Test Links</title>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'> 
  </head>
<body>
";

include_once ("lib/alinkCollection.class.php");
include_once("lib/mybreadcrumb.php");

include("header.php");
print "<p class='breadcrumbs'>" . $breadcrumb->show_breadcrumb() . "</p>";

$id = $_GET["id"]; // test only one link

$args = array('host' => "vip.library.emory.edu",
	      'db' => "WW1",
	      'coll' => 'links',
	      'sort' => $sort);

$linkset = new aLinkCollection($args);

print '<div class="content">'; 

$linkset->printUrlStatus($id);

print "</div>";

print '<div class="sidebar">';
include("nav.html");
include("searchbox.php");
print "</div>";

include("footer.html");


?>

</body>
</html>

