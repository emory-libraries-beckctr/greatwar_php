<?php

include_once("lib/xmlDbConnection.class.php");

class subjectList {
  var $tamino;
  var $subject_url;
  var $xmlContent;
  var $xml;
  var $xml_result;
  var $subjects;


  // constructor
  function subjectList($argArray) {
    // pass host/db/collection settings to tamino object
    $this->tamino = new xmlDbConnection($argArray);

    // initialize subject list from Tamino
    $this->taminoGetSubjects();
  }

  // generate an xquery using current settings, depending on the mode 
  // optionally takes a string to search against
  function xquery ($mode, $string = NULL) {
    // Dublin Core namespace
    $dcns = 'dc="http://purl.org/dc/elements/1.1/"';
    
    switch ($mode):
  case 'subject':
    // retrieve all subjects
    $query = "declare namespace $dcns
              for \$b in input()/linkCollection/subjectList/dc:subject 
              return \$b"; 
    break;
  case 'delete':
    // delete a subject
    $query = "declare namespace $dcns
              update for \$b in input()/linkCollection/subjectList/dc:subject
              where \$b eq '$string'
              do delete \$b";
    break;
  case 'add':
    // add a new subject to subject list 
    $query = "declare namespace $dcns
              update for \$b in input()/linkCollection/subjectList
              do insert <dc:subject>$string</dc:subject> into \$b";
    break;
    endswitch;
    return $query;
  }
  

  // get the full list of possible subjects from Tamino
  function taminoGetSubjects() {
    $rval = $this->tamino->xquery($this->xquery('subject'));
    if ($rval) {
      print "<p>SubjectList Error: failed to retrieve subject list.<br>";
      if ($this->tamino->debug) { print "(Tamino error code $rval)"; }
      print "</p>";
    } else {       
      // convert xml subjects into a php array 
      $this->subjects = array();

      $this->tamino->xpath->registerNamespace("dc","http://purl.org/dc/elements/1.1/");
      $subj = $this->tamino->xpath->query("//dc:subject");
      for ($j=0; $j < $subj->length; $j++) {
 	array_push($this->subjects, $subj->item($j)->textContent);
      } 

    } /* end else */
  }  /* end taminoGetSubjects() */

  // Delete a subject from subject list in tamino
  function taminoDelete ($subj) {
    $rval = $this->tamino->xquery($this->xquery('delete', $subj));
    if ($rval) {
      print "<p>There was an error deleting <b>$subj</b> from the subject list.<br>";
      if ($this->tamino->debug) { print "(Tamino error code $rval)."; }
      print "</p>";
    } else {
      print "<p>Successfully deleted <b>$subj</b> from the subject list.</p>";
    } 
  }

  // Add a new subject to the list in tamino
  function taminoAdd ($subj) {
    $rval = $this->tamino->xquery($this->xquery('add', $subj));
    if ($rval) {
      print "<p>There was an error adding <b>$subj</b> to the subject list.<br>";
      if ($this->tamino->debug) { print "(Tamino error code $rval)."; }
      print "</p>";
    } else {
      print "<p>Successfully added <b>$subj</b> to the subject list.</p>";
    }
  }

  // check if a subject is in the list of subjects
  function isSubject ($subj) {
    return (in_array($subj, $this->subjects)) ? 1 : 0;
  }
  
  // print out all subjects as an html list 
  function printHTMLList () {
    print "<ul>";
    foreach ($this->subjects as $subj) {
      print "<li>$subj</li>";
    }
    print "</ul>";
  }

  /* Print all subjects as an html select form.
     Optionally takes an array of subjects; any subjects in the array 
     will be selected by default.
  */
  function printSelectList ($matches = NULL, $size = 5, $multiple = 'yes',
			    $viewall = false) {
    if ($this->tamino->xml) {		// only display if we have content
      $selected = '';
      print "<select name='subj[]'"; 
      if (($size != 1) && $multiple != 'no') {
        // browsers seem to handle the select better if size is 
        // not specified, rather than being set to 1
        print " size='$size' multiple='$multiple'";  
      }
      print ">\n"; 
      if ($viewall) {
        print "<option value='all'>View All</option>\n";
      }

      foreach ($this->subjects as $subj) { 
        // mark a subject as selected if it is in the list of matches
       if (isset($matches) && (in_array($subj, $matches))) { 
	  $selected = "selected='yes' ";
        } else {
  	  $selected = "";
        }
        print "<option value='$subj' $selected>$subj</option>\n"; 
      } 
      print "</select>\n";
    } else if ($this->tamino->debug) {
      	print "<p><b>Warning:</b> in SubjectList::printSelectList; XML is undefined, not printing select list.</p>\n";
    } else {
      print "Subject List unavailable.";
    }
  }

  function printRemovalForm ($action = "modify_subj.php") {
    print "<form action=\"$action\" method=\"get\">";
    print '<input type="hidden" name="mode" value="del">';
    $this->printSelectList();
    print '<br>';
    print '<input type="submit" value="Remove">';
    print '<input type="reset">';
    print '</form>';
  }

} /* end class subjectList */
