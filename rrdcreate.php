<?
require_once('common.php');
require('header.php');

function step_acronym() {
	return acronym('step', 'Time interval with which data will be fed by an update script');
}

function echo_h_steplist($name, $def_value, $i, $steps_array, $select_gets_t) {
?>
			<select id="<?=$name?>_sel_<?=$i?>" onchange="setValue('<?=$name?>_<?=$i?>', this.value)">
				<?
					foreach ($steps_array as $multi) {
						$t = $multi * gpost('step', 'n');
						$h_label = h(sprintf('%.1f step%s = %s', $multi, ($multi > 1 ? 's' : ''), hr_sec($t)));
						if ($t == 0) {
							$h_label = '---';
						}
						if ($select_gets_t) {
							$cmp_val = $t;
						} else {
							$cmp_val = $multi;
						}
						$selected = ($cmp_val == gpost("{$name}_{$i}", 'n', $def_value));
				?>
				<option value="<?=($select_gets_t ? $t : $multi)?>"<?=($selected ? ' selected' : '')?>><?=$h_label?></option>
				<?
					}
				?>
			</select>
			<input type="text" name="<?=$name?>_<?=$i?>" id="<?=$name?>_<?=$i?>"
				value="<?=h(gpost("{$name}_{$i}", 'n', $def_value))?>" size="5">
<?
}

function rrdcreate_cmd() {
	$ds_c = 0;
	$rra_c = 0;

	$s = array();
	$s[] = "rrdtool create filename.rrd";
	$s[] = sprintf("--step '%d'", esh(gpost('step', 'n')));

	$start = gpost('start', '');
	if ($start != '') {
		$s[] = sprintf("--start '%d'", esh($start));
	}

	$names = array();

	for ($i = 0; $i < gpost('ds_rows', 'n'); ++$i) {
		$name = gpost("dsname_$i", '');
		if ($name == '') continue;
		if (!is_valid_vname($name)) {
			return sprintf("# ERROR: Name '%s' contains invalid symbols.", $name);
		}
		$min = gpost("dsmin_$i", '');
		$max = gpost("dsmax_$i", '');
		if ($min == '') $min = 'U';
		if ($max == '') $max = 'U';
		$type = gpost("dstype_$i", '');
		$heartbeat = gpost("dsheartbeat_$i", '');
		if ($heartbeat <= 0) $heartbeat = gpost('step', 'n') * 2;
		$s[] = sprintf("'DS:%s:%s:%s:%s:%s'", esh($name), esh($type), esh($heartbeat), esh($min), esh($max));
		$names[] = $name;
		++$ds_c;
	}

	$err = check_vnames($names, 1);
	if ($err != '') return $err;

	for ($i = 0; $i < gpost('rra_rows', 'n'); ++$i) {
		$cf = gpost("rracf_$i", '');
		$xff = gpost("rraxff_$i", '');
		$steps = gpost("rrasteps_$i", '');
		$rows = gpost("rrarows_$i", '');

		if ($steps <= 0) continue;
		if ($steps <= 0) $steps = 1;
		if ($rows <= 0) $rows = 1;

		$s[] = sprintf("'RRA:%s:%s:%s:%s'", esh($cf), esh($xff), esh($steps), esh($rows));
		++$rra_c;
	}

	if ($ds_c == 0) return "# ERROR: You defined no Data sources.";
	if ($rra_c == 0) return "# ERROR: You defined no Archives.";

	return join(" \\\n", $s);
}

gpost('step', 'n', 0);
?>
<script language='JavaScript'>
function setValue(id, value) {
	document.getElementById(id).value = value;
	return false;
}
</script>

<p>
<div style="display:<?=(gpost('step', 'n') > 0 ? 'block' : 'none')?>">
<a href="#ds">&rsaquo; Data Sources (DS)</a><br>
<a href="#rra">&rsaquo; Archives (RRA)</a><br>
<a href="#cmd">&rsaquo; RRD create command</a><br>
<a href="#graph">&rsaquo; RRD graph wizard</a>
</div>

<!--<form method="POST" id="form1" action='?nocache=<?=time()?>#bottom'>-->
<form method="POST" id="form1" action='?'>

<h1 id="step-and-general">Step</h1>
<table border="0">
<tr>
	<td>Time interval in <em>seconds</em> with which data will be fed by an update script (<b>step</b>):</td>
	<td>
		<select id="step_sel" onchange="setValue('step', this.value)">
			<? foreach (array(0, 30, 60, 120, 300, 600, 900, 1800, 3600, 7200, 10800, 21600, 43200, 86400) as $t) { ?>
			<option value="<?=$t?>"<?=($t == gpost('step', 'n') ? ' selected' : '')?>><?=h(hr_sec($t))?></option>
			<? } ?>
		</select>
	</td>
	<td><input type="text" name="step" id="step" value="<?=h(gpost('step', 'n'))?>" size="5"></td>
</tr>
<tr>
	<td>Start time:</td>
	<td>&nbsp;</td>
	<td>
		<select name="start">
			<?
			$ar = array();
			$ar[] = array('now' => '');
			$startm = gmdate('n');
			for ($y = gmdate("Y"); $y >= gmdate("Y")-2; --$y, $startm=12) {
				for ($m = $startm; $m >= 1; --$m) {
					$s = sprintf("%02d/%d", $m, $y);
					$ar[] = array($s => gmmktime(0, 0, 0, $m, 1, $y));
				}
			}
			echo_h_simpleoption("start", '', $ar);
			?>
		</select>
	</td>
</tr>
<tr><td>Data sources count:</td><td>&nbsp;</td><td><input type="text" name="ds_rows" value="<?=h(gpost('ds_rows', 'n', 4))?>" size="5"></td></tr>
<tr><td>Archives count:</td><td>&nbsp;</td><td><input type="text" name="rra_rows" value="<?=h(gpost('rra_rows', 'n', 10))?>" size="5"></td></tr>
<tr><td colspan="3"><input type="submit" value="Submit"></td>
</table>

<div style="display:<?=(gpost('step', 'n') > 0 ? 'block' : 'none')?>">
<a name="ds"><h1 id="data-sources">Data Sources (DS)</h1></a>
<dl>
<dt>Name</dt>
	<dd>Format: [a-zA-Z0-9_]{1,19}</dd>
<dt>Types</dt>
	<dd>
	GAUGE: This is simple values, <b>not</b> rate per second. All others are <b>rate</b> per second types!<br>
	COUNTER: Continuous incrementing counters, <b>never</b> decreasing unless on overflow. Rate/second.<br>
	DERIVE: Counters which may decrease too. You cannot catch overflows. Could be used to measure the change rate of a GAUGE. Rate/second.<br>
	ABSOLUTE: Counters which get reset upon reading (ie. start from zero after the reading because you reset them).
		This is used for fast counters which tend to overflow. Rate/second.<br>
	COMPUTE: Storing the result of a formula applied to other data sources. This is not covered by this wizard.
	</dd>
<dt>Heartbeat</dt>
	<dd>
	Maximum number of <em>seconds</em> that may pass between two updates of this data source before the value of the data source is assumed to be UNKNOWN.<br> 
	A typical value is "2.0 x step".
	</dd>
<dt>Min/Max</dt>
	<dd>
	Limit the <em>processed</em> value. You may leave this empty if you don't know the limits.<br>
	For GAUGE - the min/max <b>value</b>.<br>
	For the other types - the min/max <b>rate/second</b>.
	</dd>
<dt>Enter your values</dt>
	<dd>
	<table border="1">
	<tr>
		<td class="tdheader">Name</td>
		<td class="tdheader">Type</td>
		<td class="tdheader">Heartbeat</td>
		<td class="tdheader">Min</td>
		<td class="tdheader">Max</td>
	</tr>

	<? for ($i = 0; $i < gpost('ds_rows', 'n'); ++$i) { ?>
	<tr>
		<td><input type="text" name="dsname_<?=$i?>" value="<?=h(gpost("dsname_{$i}", '', ''))?>"></td>
		<td>
			<select name="dstype_<?=$i?>">
			<?=echo_h_simpleoption("dstype_$i", 'GAUGE', array('GAUGE', 'COUNTER', 'DERIVE', 'ABSOLUTE'))?>
			</select>
		</td>
		<td>
			<?echo_h_steplist('dsheartbeat', 0, $i, array(0, 0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 5.0, 10.0), 1)?>
		</td>
		<td><input type="text" name="dsmin_<?=$i?>" value="<?=h(gpost("dsmin_$i", '', ''))?>" size="5"></td>
		<td><input type="text" name="dsmax_<?=$i?>" value="<?=h(gpost("dsmax_$i", '', ''))?>" size="5"></td>
	</tr>
	<? } ?>

	</table>

	</dd>
<dt><input type="submit" value="Submit"></dt>
</dl>

<a name="rra"><h1 id="archives">Archives (RRA)</h1></a>
<dl>
<dt>Consolidation functions (CF):</dt>
	<dd>
	AVERAGE: Average value for the <?=step_acronym()?> period.<br>
	MIN: Min value for the <?=step_acronym()?> period.<br>
	MAX: Max value for the <?=step_acronym()?> period.<br>
	LAST: Last value for the <?=step_acronym()?> period which got inserted by the update script.<br>
	</dd>
<dt>xff</dt>
	<dd>What percentage of UNKOWN data is allowed so that the consolidated value is still regarded as known: 0% - 99%. Typical is 50%.</dd>
<dt>Steps</dt>
	<dd>
	How many <?=step_acronym()?> values will be used to build a <b>single</b> archive entry. 
	This defines the granularity of your archive, ie. its zoom level.<br>
	If you define a small number here, you will be able to see the details for every <?=step_acronym()?>.<br> 
	If you define a large number here, you will have some aggregated info for the last year, for example, 
	but with less details and much more "zoom out" in regards to <b>time</b> on the X-axis while visualising this.
	</dd>
<dt>Rows</dt>
	<dd>How many rows will be kept back in the database. This determines how much disk space your RRD database will use and for how much time back you will have data.</dd>
<dt>Enter your values</dt>
	<dd>
	<table border="1">
	<tr>
		<td class="tdheader">CF</td>
		<td class="tdheader">xff</td>
		<td class="tdheader">Steps (&gt;=1)</td>
		<td class="tdheader">Rows (&gt;=1)</td>
		<td class="tdheader">Calculated value (submit to refresh)</td>
	</tr>

	<? for ($i = 0; $i < gpost('rra_rows', 'n'); ++$i) { ?>
	<tr>
		<td>
			<select name="rracf_<?=$i?>">
			<?=echo_h_simpleoption("rracf_$i", 'AVERAGE', array('AVERAGE', 'MIN', 'MAX', 'LAST'))?>
			</select>
		</td>
		<td>
			<select name="rraxff_<?=$i?>">
				<?
				foreach (array(0.0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 0.99) as $v) {
					$sv = sprintf('%d%%', $v*100);
					$selected = ($v == gpost("rraxff_$i", 'n', 0.5));
				?>
				<option value="<?=$v?>"<?=($selected ? ' selected' : '')?>><?=$sv?></option>
				<?
				}
				?>
			</select>
		</td>
		<td>
			<?echo_h_steplist('rrasteps', 0, $i, array(0, 1, 2, 3, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30, 40, 50, 100, 200, 288, 300), 0)?>
		</td>
		<td>
			<input type="text" name="rrarows_<?=$i?>" id="rrarows_<?=$i?>" value="<?=h(gpost("rrarows_$i", 'n', 1))?>" size="5">
		</td>
		<td><?=h_rra_finalinfo(gpost('step', 'n'), gpost("rrasteps_$i", 'n'), gpost("rrarows_$i", 'n'))?></td>
	</tr>
	<? } ?>

	</table>
	</dd>
<dt><input type="submit" value="Submit"></dt>
</dl>

<a name="cmd"><h1 id="archives">RRD create command</h1></a>
<p><pre><?=h(rrdcreate_cmd())?></pre></p>

<a name="graph"><h1 id="graph_wizard">RRD graph wizard</h1></a>
<p><input value="Start graph wizard" type="button" onclick="f=document.getElementById('form1');f.action='rrdgraph.php';f.submit()"></p>

<!--<a name="bottom"></a>-->

</div>

</form>
<?
require('footer.php');
