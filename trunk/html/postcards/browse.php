<?php
include("../config.php");	

print "<html>
  <head> 
    $csslink
    <title>The Great War : Postcards : Thumbnails</title>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
    <base href='$base_url'>
  </head> 
<body> 
"; 

include_once("lib/taminoConnection.class.php");
include_once("lib/mybreadcrumb.php");

$args = array('host' => $tamino_server,
	      'db' => $tamino_db,
	      'coll' => 'postcards',
              'basedir' => $basedir,
	      'debug' => false,
	      );
$tamino = new taminoConnection($args);

$cat = $_GET["cat"];		// optionally limit postcards by category
$desc = $_GET["desc"];
$pos = $_GET["position"];
$maxdisplay = $_GET["max"];
if (isset($pos)) {} else {$pos = 1;}
if (isset($maxdisplay)) {} else {$maxdisplay = 10;}

($desc == "yes") ? $mode = "thumbdesc" : $mode = "thumbnail";
$xsl_params = array("mode" => $mode);
$cat_params = array("desc" => $desc, "max" => $maxdisplay);

/*
$query ='declare namespace tf="http://namespaces.softwareag.com/tamino/TaminoFunction"
for $a in input()/TEI.2/:text/body/p/figure '; 
if ($cat) { $query .= "where tf:containsText(\$a/@ana, '$cat') "; } 
$query .= 'return $a';
*/

// FIXME: this query won't allow for using the cursor to limit # of figures displayed
/* $query ='declare namespace tf="http://namespaces.softwareag.com/tamino/TaminoFunction"';
$query .= '<div> { for $a in input()/TEI.2/:text/body/p/figure ';   
if ($cat) { $query .= "where tf:containsText(\$a/@ana, '$cat') "; }    
$query .= 'return  $a } ';
$query .= '{ for $b in input()/TEI.2/:text/back/:div//interpGrp return $b }</div>';
*/ 

$declare ='declare namespace tf="http://namespaces.softwareag.com/tamino/TaminoFunction" ';
$for = 'for $a in input()/TEI.2/:text/body/p/figure ';
$let = 'let $b := input()/TEI.2/:text/back/:div//interpGrp ';
if ($cat) { $where = "where tf:containsText(\$a/@ana, '$cat') "; }
else { $where = ""; }
$return = "return <div> { \$a } <total>{ count($for $where return \$a) }</total> {\$b} </div>";
$query = "$declare $for $let $where $return ";

$xsl_file = "figures.xsl";

// need to add an option to use cursor... 
$rval = $tamino->xquery($query, $pos, $maxdisplay); 
if ($rval) {       // tamino Error code (0 = success) 
  print "<p>Error: failed to retrieve contents.<br>";
  print "(Tamino error code $rval)</p>";
  exit();
}
$tamino->getXQueryCursor();


// xquery & xsl for category labels 
$cat_query = 'for $a in input()/TEI.2/:text/back/:div//interpGrp 
return $a'; 
$cat_xsl = "interp.xsl"; 

include("header.html");

print "<p class='breadcrumbs'>" . $breadcrumb->show_breadcrumb();
print " (Thumbnails";
if ($desc == "yes") { print " with descriptions"; }
print ")</p>";


/*
print '<p class="breadcrumbs"> 
<a href="index.html">Home</a> &gt; <a href="postcards/">Postcards</a> 
	  &gt; Browse &gt; Thumbnails  
</p>';
*/

print "<p>";
if ($desc == 'yes') {
  print "<a href='postcards/browse.php?desc=no&cat=$cat&max=$maxdisplay'>Hide descriptions</a>";
} else {
  print "<a href='postcards/browse.php?desc=yes&cat=$cat&max=$maxdisplay'>Show descriptions</a>";
}
if ($cat) { print " | <a href='postcards/browse.php?desc=$desc&max=$maxdisplay'>View all</a>"; }
print "</p>";


print "<table class='maxdisplay'><tr><td>Postcards per page:
<form action='postcards/browse.php'>
<select name='max'>";
foreach (array(5,10,15,20,25) as $i) {
  ($i == $maxdisplay) ? $status = " selected" : $status = "";
  print "<option value='$i'$status>$i</option> ";
}
print "</select>
<input type='hidden' name='desc' value='$desc'>
<input type='hidden' name='cat' value='$cat'>
<input type='submit' value='Go'>
</form>";

print "</td></tr></table>";


print "<p>Displaying postcards " . $tamino->position . " - " . ($tamino->quantity + $tamino->position - 1) . " of " . $tamino->count . "</p>";

// links to more results
if ($tamino->count > $maxdisplay) {
  $result_links .= 'More postcards:';
  for ($i = 1; $i <= $tamino->count; $i += $maxdisplay) {
    if ($i == $pos) { next; }	// skip current set of results
    else {
      // construct the url, maintaining all parameters
      $url = "postcards/browse.php?max=$maxdisplay";
      if ($desc) { $url .= "&desc=$desc"; }
      if ($cat) { $url .= "&cat=$cat"; }
      // now add the key piece: the new position
      $url .= "&position=$i";
      $result_links .= " <a href='$url'>";
      $j = min($tamino->count, ($i + $maxdisplay - 1));
      // special case-- last set only has one result
      if ($i == $j) { $result_links .= "$i"; }
      else { $result_links .= "$i - $j"; }
      $result_links .= "</a> ";
    }
  }
}

print $result_links;

print '<div class="content">'; 

$tamino->xslTransform($xsl_file, $xsl_params); 
//$tamino->xslTransform($xsl_file);
$tamino->printResult();

print '</div>';

print '<div class="sidebar">';
include("postcards/nav.html");
include("searchbox.html");

print '<div class="categories">';
$rval = $tamino->xquery($cat_query);
if ($rval) {       // tamino Error code (0 = success)
  print "<p>Error: failed to retrieve contents.<br>";
  print "(Tamino error code $rval)</p>";
  exit();
}
$tamino->xslTransform($cat_xsl, $cat_params);
$tamino->printResult();

print '</div>';

print '</div>';

include("footer.html");


?>

</body>
</html>