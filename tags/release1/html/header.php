<?php

/* Note: the variable $siteurl must be set (by config.php) before including this file */

print "<p><img src='$base_url/images/topbanner.jpg' width='800' height='90'></p>

 <table width='550' border='0' cellpadding='0' cellspacing='0' >
     <tr>
    	  <td><a href='${base_url}index.html'>Home</a></td>
          <td>&#149;</td>";

// commented out until Rusche's essay is available
//	  <td><a href='${base_url}about.html'>About the Site</a></td>
print "  <td>About the Site</td>";
print "   <td>&#149;</td>
	  <td><a href='${base_url}postcards/index.php'>Postcards</a></td>
          <td>&#149;</td>
	  <td><a href='${base_url}poetry/browse.php'>Poetry</a></td>
          <td>&#149;</td>
	  <td><a href='${base_url}links/browse.php'>Links</a></td>
       </tr>
	  </table>

<hr align='left'>
";
?>
