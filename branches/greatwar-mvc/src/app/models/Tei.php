i<?php

require_once("xml-utilities/XmlObject.class.php");

  
class Tei extends Emory_Xml_Tei { 
  
  protected function configure() {
    parent::configure();
    $this->xmlconfig['author']['xpath'] =  "teiHeader/fileDesc/titleStmt/author/name";
    $this->xmlconfig['editor']['xpath'] =  "teiHeader/fileDesc/titleStmt/editor/name";
    $this->xmlconfig['front']['xpath'] =  "text/front"; 
    $this->xmlconfig['front']['is_series'] = "true";
    $this->xmlconfig['front']['class_name'] = "TeiDiv";
    $this->xmlconfig['body']['xpath'] = "text/body";
    $this->xmlconfig['fdiv']['id'] = "@id";
    $this->xmlconfig['fdiv']['xpath'] = "text/front/div1";
    $this->xmlconfig['fdiv']['is_series'] = "true";
    $this->xmlconfig['fdiv']['class_name'] = "TeiDiv";
    $this->xmlconfig['div1']['id'] = "@id";
    $this->xmlconfig['div1']['xpath'] = "text/body/div1";
    $this->xmlconfig['div1']['is_series'] = "true";
    $this->xmlconfig['div1']['class_name'] = "TeiDiv";
    $this->xmlconfig['div2']['id'] = "@id";
    $this->xmlconfig['div2']['xpath'] = "text/body/div1/div2";
    $this->xmlconfig['div2']['is_series'] = "true";
    $this->xmlconfig['div2']['class_name'] = "TeiDiv";
    $this->xmlconfig['div3']['id'] = "@id";
    $this->xmlconfig['div3']['xpath'] = "text/body/div1/div2/div3";
    $this->xmlconfig['div3']['is_series'] = "true";
    $this->xmlconfig['div3']['class_name'] = "TeiDiv";

  }

  /**
   * Check if is document complicated enough that a ToC should be displayed.
   * Checks for more than one div directly under body, or more than
   * one div directly under first div under body.
   *
   * @return boolean
   */
  public function showTableOfContents() {
    return (isset($this->text->div) && (count($this->text->div) > 1 ||
	     (isset($this->text->div[0]->div) && count($this->text->div[0]->div) > 1)));
  }

  public function __get($name) {
    $value = parent::__get($name);
    switch ($name) {
    case "docname":
      $value = str_replace(".xml", "", $value);	// return document name without .xml extension
      break;
    }
    return $value;
  }


  public static function find($options = null) {
    $exist = Zend_Registry::get("exist-db");
    // FIXME: need to use db name from site config here...
    $path = "/db/hdot";
    if (isset($options["section"])) $path .= "/" . $options["section"];

    $filter = "";
    if (isset($options["subset"]))
      $filter .= "[.//rs[@type='subset'] = '" . $options["subset"] . "']";
    if (isset($options["object"]))
      $filter .= "[.//rs[@type='object'] = '" . $options["object"] . "']";

    $find_query = 'for $a in collection("' . $path . '")//TEI.2' . $filter . '
 	order by $a/teiHeader/fileDesc/titleStmt/title
	return <TEI.2>{$a/@id}
	  <document-name>{util:document-name($a)}</document-name>
        <teiHeader>
 	  <fileDesc>
          {$a/teiHeader/fileDesc/titleStmt}
	  {$a/teiHeader/fileDesc/notesStmt}
 	  </fileDesc>
	  {$a/teiHeader/profileDesc}
        </teiHeader>
        </TEI.2>';
    $xml = $exist->query($find_query, 50, 1);

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    return new TeiSet($dom);

  }

  public static function findByName($name) {
    $exist = Zend_Registry::get("exist-db");
    $xml = $exist->getDocument($name);
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    return new Tei($dom);
  }

  public static function getTranscriptChunk($docname, $start = 1, $end = 5) {

    // FIXME: need to use db name from site config here... (above)
    // FIXME2: language collections ?
    $path = "/db/hdot/" . $docname;
    $query = '
import module namespace hdot="http://www.hdot.org/xquery/hdot" at
"xmldb:exist:///db/xquery-modules/hdot.xqm"; 

for $a in document("' . $path . '")/TEI.2
	return <TEI.2>
	     <document-name>{util:document-name($a)}</document-name>
	     {$a/teiHeader}
	     {$a/text/front/castList} ';
    if ($start == 1)
      $query .= '<front>{$a/text/front/titlePage}{$a/text/front/pb}</front> ';
    $query .= '
	<lastpage>{max($a//pb/@pn)}</lastpage>
	<content>
	{ hdot:transcript-pagerange($a, ' . $start . ', ' . $end . ') }
        </content>
     </TEI.2>';
    $exist = Zend_Registry::get("exist-db");
    $xml = $exist->query($query, 50, 1);

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    return new Tei($dom);

  }
  

  public static function getPoetryTitle() {
    $exist = Zend_Registry::get("exist-db");
    $path = $exist->getDbPath();
    $query =  'for $a in collection("' . $path . '")/TEI.2/teiHeader/fileDesc/titleStmt
       let $docname := util:document-name($a)
       order by($a/title)
       return <TEI.2 id="{$docname}"> <teiHeader><fileDesc>{$a}</fileDesc></teiHeader> </TEI.2>';
    $xml = $exist->query($query, 50, 1);
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    print $xml; //DEBUG: outputs xml to source view.
    return new GWTeiSet($dom);
  }


  /* FIXME: this query returns a single object, but wrapped in exist result tags. So the object doesn't show. Returning the 1st object of the TeiSet array is a workaround */

  public static function getPoetryContent($id) {
    $exist = Zend_Registry::get("exist-db");
    $path = $exist->getDbPath();
    $query = 'for $a in collection("' . $path . '")/TEI.2
       let $docname := util:document-name($a)
       let $titlestmt := $a/teiHeader/fileDesc/titleStmt
       let $fileDesc := $a/teiHeader/fileDesc
       let $bibl := $a/teiHeader/fileDesc/sourceDesc/bibl
       where $docname = ' . "'$id'" . '
       return <TEI.2>
         <teiHeader>
           {$fileDesc}
         </teiHeader>
      { for $fdiv in $a/text/front/div
        return <fdiv> {$fdiv/@id} {$fdiv/@n} {$fdiv/@type} {$fdiv/head} </fdiv> }
      { for $div1 in $a/text/body/div 
        return <div1> {$div1/@id} {$div1/@n} {$div1/@type}
          {$div1/head} {$div1/p[1]}
      { for $div2 in $div1/div 
        return <div2> {$div2/@id} {$div2/@n} {$div2/@type} 
           {$div2/docAuthor}
      { for $div3 in $div2/div return <div3>{$div3/@id} {$div3/@n} {$div3/@type}</div3> }
          </div2> }
        </div1> }
     </TEI.2>';
    $xml = $exist->query($query, 50, 1, array("wrap" => false));
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    print $xml;                 //DEBUG: outputs xml to source to view
    $tei = new GWTeiSet($dom);
    return $tei->docs[0];
    //return new Tei($dom);
  }

  public function getByPoet()  {
    $query = 'for $a in collection("' . $path . '")/TEI.2/text/body//docAuthor
      let $div := $a/..
      let $docname := util:document-name(root($a))
      where $div/docAuthor/@n
      return <div docname="{$docname}">
      {$div/@n}
      {$div/@id}
      {$div/@type}
      {$a}
      {for $div2 in $div/div2
         return <TEI.2> {$div2/@id} {$div2/@n} {$div2/@type} {$div2/docAuthor}
       </TEI.2> }
      order by (docAuthor/@n, @n)
      </div>';
    $exist = Zend_Registry::get("exist-db");
    $xml = $exist->query($query, 50, 1);

    $dom = new DOMDocument();
    $dom->loadXML($xml);
    return new Tei($dom);
  }

}   





/**
 * group of TEI documents (e.g., grouped in a result set returned by eXist)
 */
class GWTeiSet extends XmlObject {
  
  public function __construct($xml) {
    $config = $this->config(array(
	"docs" => array("xpath" => "//TEI.2",
			"is_series" => true, "class_name" => "Tei"),
	));
    parent::__construct($xml, $config);
  }
}


// group of TEI documents (e.g., grouped in a result set returned by eXist)
/* class TeiSet extends XmlObject {
  
  public function __construct($xml) {
    $config = $this->config(array(
	"docs" => array("xpath" => "//TEI.2",
			"is_series" => true, "class_name" => "Tei"),
	));
    parent::__construct($xml, $config);
  }
}
*/
/* mappings for TEI.2/text */
/* class TeiText extends XmlObject {
  public function __construct($xml) {
    $config = $this->config(array(
	"front" => array("xpath" => "front", "class_name" => "TeiFront"),
	"div" => array("xpath" => "body/div",
	"is_series" => true,
	"class_name" => "TeiDiv"),
	"back" => array("xpath" => "back", "class_name" => "TeiBack"),
	));
    parent::__construct($xml, $config);
  }
  } */

/* front matter */ 
/* class TeiFront extends XmlObject {
  public function __construct($xml) {
    $config = $this->config(array(
	"id" => array("xpath" => "@id"),
	"title" => array("xpath" => "titlePage/docTitle/titlePart[@type='main']"),
	"div" => array("xpath" => "div",
		       "is_series" => true,
		       "class_name" => "TeiDiv"),
	));
    parent::__construct($xml, $config);
  }
  } */
/* generic div */ 
/* class TeiDiv extends XmlObject {
  public function __construct($xml) {
    $config = $this->config(array(
	"id" => array("xpath" => "@id"),
	"title" => array("xpath" => "head"),
  	"div" => array("xpath" => "div",
		       "is_series" => true,
		       "class_name" => "TeiDiv"),
	));
    parent::__construct($xml, $config);
  }
  
  } */

/* back matter */ 
/* class TeiBack extends XmlObject {
  public function __construct($xml) {
    $config = $this->config(array(
				  "id" => array("xpath" => "@id"),
				  "div" => array("xpath" => "div",
						 "is_series" => true,
						 "class_name" => "TeiDiv"),
				  ));
    parent::__construct($xml, $config);
  }
  
  } */