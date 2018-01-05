<?php
if (isset($_GET["ajax"]) && $_GET["ajax"] == 1){
	echo generateList(50);
} else {
?>	
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>jQuery plugin - jqPageFlow : makes scrolling pagination easy</title>
	<link rel="stylesheet" type="text/css" href="css/jqpageflow.css" />
	<!-- include our jquery scripts -->
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
    <script src="js/jquery.jqpageflow.js" type="text/javascript"></script>
    <script type="text/javascript">
    	$(document).ready(function(){
    	
	    	$("form").flexiPagination({
	    		url: "index.php?ajax=1",
	   			currentPage: 0,
	   			totalResults: 150,
	   			perPage: 50,
	   			container: "#mycontainer",
	   			pagerVar : "p",
	   			loaderImgPath: "images/loader.gif",
	   			debug : 1
	    	});
    	});
    </script>
    
</head>
<body>
	<h1>jqPageFlow</h1>
	<div>
		<p>Simply put, this plugin makes the scrolling 
			pagination found on sites like dzone.com and Google Reader easy to implement on your site.</p>
		<p>To see an example, simply scroll down to the bottom of this page - you should see an unordered list with
			50 items. As you get closer to the bottom, this list will expand to 100, then 150 afterwhich it should stop.</p>
		<p>This is of course all configurable, and intended to be used on an automatically generated page using a backend script (eg php).</p>
		<p>Please note that this script is still currently in beta, so if you find any problems please post a bug submission 
			to <a href="http://code.google.com/p/flexidev/issues/">http://code.google.com/p/flexidev/issues/</a> 
			with as much information as possible</p>
	</div>
	<div>
		<h2>Usage</h2>
		<ol>
			<li>Download the latest source code from the repository : <a href="http://code.google.com/p/flexidev/downloads/">http://code.google.com/p/flexidev/downloads</a></li> 
			<li>Include the jQuery library into your page header (this plugin supports both jQuery v1.2.6 and v1.3)</li>
			<li>Include the jquery.jqpageflow.js file into your page header</li>
			<li>Include the supplied css file in your page header : modify as you see fit</li>
			<li>View the source of this page: copy, paste and modify the javascript block as required. Use $("body").jqpageflow(); to use defaults.</li>
			<li>Create a php/.net/perl/python etc backend script to handle the serverside stuff - see the index.php file (this page) included in the download source for a basic example</li>
		</ol>		
	</div>
	<div>
		<h2>Features</h2>
		<ul>
			<li>Compatable with jQuery v1.2.6 and 1.3</li>
			<li>Chainable</li>
			<li>Configurable options : including the url to call, current page, pager var, container element etc</li>
			<li>Server side platform independant : tell it how to interact with your backend scripts</li>
			<li>Cross browser compatability, tested in FF 2.5+, Safari 3+ (more browsers to come soon)</li>
			<li>Skinnable loader with CSS and customizable image path</li>
		</ul>
	</div>		
	<div>
		<h2>Options</h2>
		<ul>
			<li><strong>url</strong>: Specify which url (relative or absolute) the plugin should submit the ajax request to. Defaults to current window.location</li>
			<li><strong>currentPage</strong>: Gets appended to url as a GET value to help your backend script keep track of which page of results the user is on. Defaults to 0 (first page)</li>
			<li><strong>pagerVar</strong>: Related to currentPage, gets appended to url as a GET var to help your backend script keep track of which page of results the user is on (e.g. index.php?page=2). Defaults to "p"</li>
			<li><strong>perPage</strong>: Used for calculation and display purposes, tell the plugin how many results are being displayed per page. Defaults to 50.</li>
			<li><strong>totalResults</strong>: Tells the plugin when it needs to stop trying to look for more results. Defaults to 100</li>
			<li><strong>container</strong>: Specify which html element contains the result items, can be any jQuery compatible selector (eg "#mycontainer", ".results", "body").
			 Any html returned to the ajax call gets appended to this element. Defaults to "body"</li>
			<li><strong>loaderImgPath</strong>: Tell the plugin where to find the loader img relative to the page calling the plugin. Defaults to "images/loader.gif"</li>
			<li><strong>debug</strong>: When set to 1, the plugin will print debugging information to the console (firebug). Defaults to 0</li>
		</ul>
	</div>
	<div>
		<h2>Changelog</h2>
		<ul>
			<li>18 Jan 2009 : version 0.2b - commented out references to console (forced plugin to not work until firebug console was enabled)</li>
			<li>18 Jan 2009 : version 0.1b released</li>
		</ul>
	</div>
	<div>
		<h2>Credits</h2>
		<ul>
			<li>Plugin Author: Barry Roodt - <a href="http://calisza.wordpress.com">http://calisza.wordpress.com</a> , <a href="http://twitter.com/barryroodt">Twitter</a></li>
			<li>Naming ideas, SEO/SEM help: Christopher Mills - <a href="http://imod.co.za">http://imod.co.za</a> , <a href="http://twitter.com/ChristopherM">Twitter</a></li>
		</ul>
	</div> 
	<div>
		<h2>Demo</h2>
		<ul id="mycontainer">
			<?php echo generateList(50); ?>
		</ul>
	</div>
</body>
</html>
<? } 


function generateList($num){
	
	if (isset($_GET["p"])){
		$start = $_GET["p"] * $num;
	} else {
		$start = 0;
	}
	
	$content = "";
	for($i=1; $i <= $num; $i++){
		$counter = $start + $i;
		$content .= "<li>This is result item number " . $counter . "</li>";
	}
	
	return $content;
}

?>