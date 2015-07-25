<?
require_once('common.php');
require('header.php');
?>
<h1 id="motivation">Motivation</h1>
<p>I constantly forget the RRD command-line arguments and details, and I have to re-learn them every time I use it. Therefore, I decided to create this simple wizard which shows the basic RRD features.</p>

<h1 id="todo">Ideas to make this tool better</h1>
<dl>
	<dt>More sanity checks</dt>
			<dd>
			Before generating the RRD create command, every value has to be sanitized well.<br>
			For example, you cannot have a negative step.
			</dd>
	<dt>Sample graphs</dt>
			<dd>
			When a user has done their graph definition, generate some sample RRD and some values, then plot a sample graph as a preview.
			</dd>
	<dt>As commented by other users</dt>
			<dd>
			Tooltip (or "?" link) explanation on what is "Archives count".<br>
			Steps could be adjusted to always include the usual time intervals (whole hours, half hour, whole days, whole months).<br>
			Add an alternative listbox for "Rows", so that you can choose an usual interval from there, or enter the rows count yourself.
			</dd>
	<dt>Separate RPN wizard</dt>
			<dd>
			Which could work with some more complex use-cases and also with multiple RRD files as data sources.
			</dd>

</dl>
<?
require('footer.php');
