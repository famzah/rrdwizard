<?
require_once('common.php');
require('header.php');

function alink($url, $desc) {
	return sprintf('<a href="%s" target="_blank">%s</a>', h($url), h($desc));
}
?>
<h1 id="links">Support inquiries</h1>
<dl>
	<dt>support</dt>
			<dd><?=alink('http://rrdwizard.appspot.com/resources.php', 'RRDtool Support page')?></dd>
</dl>

<h1 id="links">The following resources helped me to understand RRDtool better</h1>
<dl>
	<dt>rrdtutorial</dt>
			<dd><?=alink('http://oss.oetiker.ch/rrdtool/tut/rrdtutorial.en.html', 'Alex van den Bogaerdt\'s RRDtool tutorial')?></dd>
	<dt>rrd-beginners</dt>
			<dd><?=alink('http://oss.oetiker.ch/rrdtool/tut/rrd-beginners.en.html', 'RRDtool Beginners\' Guide')?></dd>
	<dt>The man pages of rrdtool</dt>
			<dd>
			man rrdtool, man rrdcreate, man rrdinfo, man rrdgraph, man rrdgraph_data, man rrdgraph_rpn, man rrdgraph_graph, man rrdgraph_examples<br>
			The online man pages can be found <?=alink('http://oss.oetiker.ch/rrdtool/doc/index.en.html', 'here')?>.
			</dd>
</dl>

<h1 id="links">This project is open-source</h1>
<dl>
	<dt>repository</dt>
			<dd><?=alink('https://github.com/famzah/rrdwizard', 'You can contribute at GitHub')?></dd>
</dl>
<?
require('footer.php');
