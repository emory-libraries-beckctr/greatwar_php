<?php

/* Note: the variable $base_url must be set (by config.php) before including this file */

print "<div class='search'>
<h3>Search</h3> 

<form action='${base_url}searchall.php'>
<input type='text' name='keyword' size='15'>
<input type='submit' value='Go'>
<font size='-1'>

<h4>Search within:</h4>
<input type='checkbox' name='poetry' checked='yes'>Poetry<br>
<input type='checkbox' name='postcards' checked='yes'>Postcards<br>
<input type='checkbox' name='links' checked='yes'>Links<br>
</font>
</form>

</div>";

?>