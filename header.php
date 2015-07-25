<?
function navi_link($file, $desc) {
	$script_name = basename($_SERVER['SCRIPT_NAME']);

	$h = '';
	if ($file == $script_name) {
		$h = " class=\"highlight\"";
	}
	return "<a href=\"$file\"$h>".htmlspecialchars($desc)."</a>";
}

function acronym($name, $desc) {
	return "<acronym title=\"".htmlspecialchars($desc)."\">".htmlspecialchars($name)."</acronym>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-AU">
  <head>
    <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
    <meta name="author" content="haran" />
    <meta name="generator" content="author" />

    <link rel="stylesheet" type="text/css" href="sinorca/sinorca-screen.css" media="screen" title="Sinorca (screen)" />
    <link rel="stylesheet alternative" type="text/css" href="sinorca/sinorca-screen-alt.css" media="screen" title="Sinorca (alternative)" />
    <link rel="stylesheet" type="text/css" href="sinorca/sinorca-print.css" media="print" />

    <title>RRDtool Wizard</title>
    <?
    	if (isset($additional_head_html)) {
		echo $additional_head_html;
	}
    ?>
  </head>

  <body>
    <!-- ##### Header ##### -->

    <div id="header">
      <div class="midHeader">
        <h1 class="headerTitle">RRDtool Wizard</h1>
      </div>

      <div class="subHeader">
        <span class="doNotDisplay">Navigation:</span>
	<?=navi_link('index.php', 'Home')?> |
	<?=navi_link('import.php', 'Import a RRD')?> |
	<?=navi_link('rrdcreate.php', 'Create a RRD')?> |
	<?=navi_link('rrdgraph.php', 'Graph a RRD')?> |
	<?=navi_link('resources.php', 'Resources')?>
      </div>
    </div>

    <!-- ##### Main Copy ##### -->

    <div id="main-copy">

