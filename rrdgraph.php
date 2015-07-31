<?
require_once('common.php');
$additional_head_html = '<script src="colorpicker/201a.js" type="text/javascript"></script>'."\n";
require('header.php');

function h_color_picker($name, $i, $value) {
?>
			<a href="javascript:onclick=show_color_picker('<?=$name?>_picker', '<?=$name?>_color_<?=$i?>','<?=$name?>_sample_<?=$i?>');">Select Color</a>:&nbsp;
			<input
				type="text" size="9"
				name="<?=$name?>_color_<?=$i?>"
				ID="<?=$name?>_color_<?=$i?>"
				value="<?=h($value)?>"
				onchange="document.getElementById('<?=$name?>_sample_<?=$i?>').style.backgroundColor=this.value">&nbsp;
			<input type="text" ID="<?=$name?>_sample_<?=$i?>" size="1" value="" style="background-color: <?=h($value)?>">
<?
}

function color_verify($s, $def) {
	if (!preg_match('/^\#?([a-f0-9]{6})$/i', $s, $m)) {
		return $def;
	}
	return $m[1];
}

// this outputs a leading ':' (if $s != '')
function elegend($s) {
	if ($s == '') return '';
	return ':'.strtr($s, array(':' => '\\:'));
}

function rrdgraph_cmd() {
	$s = array();

	$gname = gpost('graphfilename', '');
	if ($gname == '') $gname = 'graph.png';

	$rrdname = gpost('rrdfilename', '');
	if ($rrdname == '') $rrdname = 'db.rrd';

	$s[] = sprintf("rrdtool graph '%s'", esh($gname));
	
	$title = gpost('htitle', '');
	$vertical_label = gpost('vtitle', '');
	$width = gpost('width', '');
	$height = gpost('height', '');

	if ($title != '') {
		$s[] = sprintf("--title '%s'", esh($title));
	}
	if ($vertical_label != '') {
		$s[] = sprintf("--vertical-label '%s'", esh($vertical_label));
	}
	if (preg_match('/^\d+$/', $width)) {
		$s[] = sprintf("--width '%s'", esh($width));
	}
	if (preg_match('/^\d+$/', $height)) {
		$s[] = sprintf("--height '%s'", esh($height));
	}

	if (gpost('canvas_size', '') == 'whole image') {
		$s[] = "--full-size-mode";
	}
	if (gpost('strip_labels', '') == 'yes') {
		$s[] = "--only-graph";
	}

	$t = gpost('time_range', '');
	$s[] = "--start end-$t";

	for ($i = 0; $i < gpost('def_cnt', 'n'); ++$i) {
		if (gpost("def_vname_$i", '') == '') continue;
		$s[] = sprintf("'DEF:%s=%s:%s:%s'",
			esh(gpost("def_vname_$i", '')),
			esh($rrdname),
			esh(gpost("def_ds_$i", '')),
			esh(gpost("def_cf_$i", ''))
		);
	}

	for ($i = 0; $i < gpost('cdef_cnt', 'n'); ++$i) {
		if (gpost("cdef_vname_$i", '') == '') continue;
		$s[] = sprintf("'CDEF:%s=%s'",
			esh(gpost("cdef_vname_$i", '')),
			esh(gpost("cdef_formula_$i", ''))
		);
	}

	for ($i = 0; $i < gpost('vdef_cnt', 'n'); ++$i) {
		if (gpost("vdef_vname_$i", '') == '') continue;
		$s[] = sprintf("'VDEF:%s=%s,%s'",
			esh(gpost("vdef_vname_$i", '')),
			esh(gpost("vdef_ds_$i", '')),
			esh(gpost("vdef_function_$i", ''))
		);
		if (gpost("vdef_vline_$i", '') == 'no') continue;
		$color = color_verify(gpost("vdef_color_$i", ''), '000000');
		$legend = elegend(gpost("vdef_legend_$i", ''));
		$s[] = sprintf("'VRULE:%s#%s%s'",
			esh(gpost("vdef_vname_$i", '')),
			esh($color),
			esh($legend)
		);
	}

	for ($i = 0; $i < gpost('lines_cnt', 'n'); ++$i) {
		if (gpost("line_ds_$i", '') == '---') continue;
		$color = color_verify(gpost("line_color_$i", ''), '000000');
		$legend = elegend(gpost("line_legend_$i", ''));

		$type = sprintf('LINE%d', gpost("line_width_$i", ''));
		if (gpost("line_area_$i", '') == 'yes') $type = 'AREA';

		$s[] = sprintf("'%s:%s#%s%s'",
			esh($type),
			esh(gpost("line_ds_$i", '')),
			esh($color),
			esh($legend)
		);
	}

	for ($i = 0; $i < gpost('labels_cnt', 'n'); ++$i) {
		if (gpost("print_ds_$i", '') == '---') continue;
		$text = strtr(gpost("print_text_$i", ''), array('%' => '%%', ':' => '\\:'));
		if ($text != '') $text = ' '.$text;
		$format = gpost("print_vformat_$i", '').gpost("print_si_$i", '').$text.gpost("print_just_$i", '');

		$s[] = sprintf("'%sPRINT:%s:%s'",
			esh(gpost("print_inside_$i", '') == 'yes' ? 'G' : ''),
			esh(gpost("print_ds_$i", '')),
			esh($format)
		);
	}

	return join(" \\\n", $s);
}

$err = 0;
if (gpost('step', 'n', 0) <= 0) {
	$err = 1;
}

$ds_names = array();
$rra_cfs = array();
$vnames = array(); # DEF,CDEF and VDEF
$vdef_vnames = array(); # only VDEF

$stage = gpost('stage', 'n', 0);
$stage = isset($_POST['cdefdone']) && $stage < 3 ? 3 : $stage;
$stage = isset($_POST['vdefdone']) && $stage < 4 ? 4 : $stage;
$stage = isset($_POST['linesdone']) && $stage < 5 ? 5 : $stage;
$stage = isset($_POST['labelsdone']) && $stage < 6 ? 6 : $stage;

$stage_next_action = array(
	'Enter a RRD filename',
	'Define at least one DEF entry',
	'Optionally, define one or more CDEF entries',
	'Optionally, define one or more VDEF entries',
	'Define at least one LINE/AREA entry',
	'Optionally, define one or more LABEL entries',
);
?>
<script language='JavaScript'>
function setValue(id, value) {
	document.getElementById(id).value = value;
	return false;
}
function show_color_picker(divname, val1, val2) {
	// reset
	['line_picker', 'vdef_picker'].map(function(item) {
		var el = document.getElementsByName(item);
		el = el[0];
		el.id = '';
		el.className = '';
	});

	// set
	var el = document.getElementsByName(divname);
	el = el[0];
	el.id = 'colorpicker201';
	el.className = 'colorpicker201';

	showColorGrid2(val1, val2);
}
</script>

<p>
<form method="POST" id="form1" action="?nocache=<?=time()?>#bottom">

<? if ($err) { ?>

<h1 id="error" style="background-color: red">Error</h1>
<p>Please create the RRD first using the <a href="rrdcreate.php">Create a RRD</a> wizard.</p>
<p>Alternatively you can import the structure of an existing RRD using the <a href="import.php">Import a RRD</a> wizard.</p>

<? } else { ?>

<h1 id="info">RRD details</h1>
<dl>
<dt>Available data sources (input data)</dt>
	<dd>
	<table border="0">
<?
	printf('<input name="step" type="hidden" value="%s">'."\n", h(gpost('step', 'n')));

	printf('<input name="ds_rows" type="hidden" value="%s">'."\n", h(gpost('ds_rows', 'n')));
	for ($i = 0; $i < gpost('ds_rows', 'n'); ++$i) {
		$name = gpost("dsname_$i", '', '');
		$type = gpost("dstype_$i", '', '');
		printf('<input name="dsname_%d" type="hidden" value="%s">'."\n", $i, h($name));
		printf('<input name="dstype_%d" type="hidden" value="%s">'."\n", $i, h($type));
		if ($name == '') continue;
		$ds_names[] = $name;
?>
<tr>
	<td class="tdheader">Data source:</td>
	<td><?=h("$name ($type)")?></td>
</tr>
<?
	}
?>
	</table>
	</dd>
<dt>Available archives for each data source (this is what you can visualize)</dt>
	<dd>
	<table border="0">
<?
	$i = 0;
	printf('<input name="rra_rows" type="hidden" value="%s">'."\n", h(gpost('rra_rows', 'n')));
	for ($i = 0; $i < gpost('rra_rows', 'n'); ++$i) {
		$cf = gpost("rracf_$i", '');
		$steps = gpost("rrasteps_$i", '');
		$rows = gpost("rrarows_$i", '');
		printf('<input name="rracf_%d" type="hidden" value="%s">'."\n", $i, h($cf));
		printf('<input name="rrasteps_%d" type="hidden" value="%s">'."\n", $i, h($steps));
		printf('<input name="rrarows_%d" type="hidden" value="%s">'."\n", $i, h($rows));
		if ($steps <= 0) continue;
		$rra_cfs[] = $cf;
?>
<tr>
	<td class="tdheader"><?=h($cf)?>:</td>
	<td><?=h_rra_finalinfo(gpost('step', 'n'), $steps, $rows)?></td>
</tr>
<?
}
$rra_cfs = array_unique($rra_cfs);
?>
</table>
</dd>
</dl>

<h1 id="general">General options</h1>
<dl>
<dt>Enter your values</dt>
<dd>
<table border="1">
<tr>
	<td class="tdheader">Type</td>
	<td class="tdheader">Required</td>
	<td class="tdheader">Value</td>
</tr>
<?
$opts = array(
	'rrdfilename' => array('RRD filename', '', 'db.rrd', array(), 1),
	'graphfilename' => array('Graph filename', '', 'graph.png', array(), 1),
	'htitle' => array('Horizontal top title', '', '', array(), 0),
	'vtitle' => array('Vertical left title', '', '', array(), 0),
	'width' => array('Image width', 'n', '400', array(), 1),
	'height' => array('Image height', 'n', '100', array(), 1),
	'canvas_size' => array('Given image size is for', '', 'canvas only', array('canvas only', 'whole image'), 1),
	'strip_labels' => array('Strip all texts/labels (useful for thumbnails)', '', 'no', array('no', 'yes'), 1),
	'time_range' => array('Time range is from now back to', '', '1d', array('1d', '3d', '5d', '1w', '2w', '1m', '3m', '6m', '9m', '1y', '2y'), 1),
);
foreach ($opts as $name => $data) {
	$values = $data[3];
	$curr_value = gpost($name, $data[1], $data[2]);
	if ($name == 'rrdfilename') {
		if ($curr_value == '') {
			$stage = 0;
		} else {
			if ($stage < 1) $stage = 1;
		}
	}
?>
<tr>
	<td><?=h($data[0])?></td>
	<td><?=($data[4] ? '<b>Yes</b>' : 'No')?></td>
	<td>
		<?
			if (count($values) == 0) {
		?>
		<input type="text" name="<?=$name?>" value="<?=h($curr_value)?>">
		<?
			} else {
		?>
		<select name="<?=$name?>">
			<?echo_h_simpleoption($name, $data[2], $values)?>
		</select>
		<?
			}
		?>
	</td>
</tr>
<?
}
?>

<tr>
	<td>Limits and scaling</td>
	<td colspan="2">Not covered by this wizard at all</td>
</tr>

</table>

</dd>
<dt><input type="submit" value="Submit"></dt>
</dl>

<? if ($stage >= 1) { ?>
<h1 id="def-data-series">Standard data series definition (DEF)</h1>
<dl>
<dt>Use an existing data source:</dt>
<dd>
<table border="1">
<tr>
	<td class="tdheader">Virtual name</td>
	<td class="tdheader">Data source</td>
	<td class="tdheader">CF funtion</td>
</tr>

<?
	$got = 0;
	$ar = array();
	for ($i = 0; $i < count($ds_names)*count($rra_cfs); ++$i) {
		$vname = gpost("def_vname_$i", '', '');
		if ($vname != '') {
			$got = 1;
			$ar[] = $vname;
		}
?>
<tr>
	<td>
		<input type="text" name="def_vname_<?=$i?>" size="10" value="<?=h($vname)?>">
	</td>
	<td>
		<select name="def_ds_<?=$i?>">
			<?echo_h_simpleoption("def_ds_$i", $ds_names[0], $ds_names)?>
		</select>
	</td>
	<td>
		<select name="def_cf_<?=$i?>">
			<?echo_h_simpleoption("def_cf_$i", $rra_cfs[0], $rra_cfs)?>
		</select>
	</td>
</tr>
<?
	}
	$vnames = array_merge($vnames, $ar);
	if ($got && $stage < 2) $stage = 2;
	if (!$got) $stage = 1;
?>

</table>
<input type="hidden" name="def_cnt" value="<?=$i?>">
</dd>
</dt>
<dt><input type="submit" value="Submit"></dt>
</dl>
<? } ?>

<? if ($stage >= 2) { ?>
<h1 id="cdef-data-series">Dynamic data series definition (CDEF)</h1>
<dl>
<dt>Create in-memory data series by applying some math on the DEF data sources which you specified above</dt>
<dd>
<table border="1">
<tr>
	<td class="tdheader">Virtual name</td>
	<td class="tdheader">DEF source</td>
	<td class="tdheader">Sample formula</td>
	<td class="tdheader">Formula syntax (edit to suit your needs)</td>
</tr>

<?
	$max_num_cdefs = count($vnames) * /* count of possible formulas, let's assume something */ 3;
	if ($max_num_cdefs > 12) $max_num_cdefs = 12;
	$ar = array();
	for ($i = 0; $i < $max_num_cdefs; ++$i) {
		$vname = gpost("cdef_vname_$i", '', '');
		if ($vname != '') {
			$ar[] = $vname;
		}
?>
<tr>
	<td>
		<input type="text" name="cdef_vname_<?=$i?>" size="10" value="<?=h($vname)?>">
	</td>
	<td>
		<select name="cdef_ds_<?=$i?>" id="cdef_ds_<?=$i?>">
			<?echo_h_simpleoption("cdef_ds_$i", $vnames[0], $vnames)?>
		</select>
	</td>
	<td>
		<select onchange="if (this.value == '') return true;setValue('cdef_formula_<?=$i?>', document.getElementById('cdef_ds_<?=$i?>').value + ',' + this.value)">
			<option value="">---</option>
			<option value="8,*">* 8</option>
			<option value="1000,/">/ 1000</option>
			<option value="200,+">+ 200</option>
		</select>
	</td>
	<td>
		<input type="text" name="cdef_formula_<?=$i?>" id="cdef_formula_<?=$i?>" size="30" value="<?=h(gpost("cdef_formula_$i", '', ''))?>">
	</td>
</tr>
<?
	}
	$vnames = array_merge($vnames, $ar);
?>

</table>
<input type="hidden" name="cdef_cnt" value="<?=$i?>">
</dd>
<dt><input type="submit" value="Update"> <input type="submit" value="Done defining CDEFs" name="cdefdone"></dt>
</dl>
<? } ?>

<? if ($stage >= 3) { ?>
<h1 id="vdef-data-series">Dynamic labels definition (VDEF)</h1>
<dl>
<dt>Single aggregated data/time value for the whole <b>visualized</b> dataset</dt>
<dd>
<div name="vdef_picker"></div>
<table border="1">
<tr>
	<td class="tdheader">Virtual name</td>
	<td class="tdheader">DEF/CDEF source</td>
	<td class="tdheader">Funtion</td>
	<td class="tdheader">VLINE<b>*</b></td>
	<td class="tdheader">VLINE color</td>
	<td class="tdheader">VLINE optional legend</td>
</tr>

<? 
$max_num_vdefs = count($vnames) * /* count of possible functions, let's assume something */ 8;
if ($max_num_vdefs > 12) $max_num_vdefs = 12;
for ($i = 0; $i < $max_num_vdefs; ++$i) {
	$vname = gpost("vdef_vname_$i", '', '');
	if ($vname != '') {
		$vdef_vnames[] = $vname;
	}
?>
<tr>
	<td>
		<input type="text" name="vdef_vname_<?=$i?>" size="10" value="<?=h($vname)?>">
	</td>
	<td>
		<select name="vdef_ds_<?=$i?>" id="vdef_ds_<?=$i?>">
			<?echo_h_simpleoption("vdef_ds_$i", $vnames[0], $vnames)?>
		</select>
	</td>
	<td>
		<select name="vdef_function_<?=$i?>">
			<?echo_h_simpleoption("vdef_function_$i", 'LAST', array('MAXIMUM', 'MINIMUM', 'AVERAGE', 'LAST', 'FIRST', 'TOTAL'))?>
			<option value="STDEV">Standard deviation</option>
			<? foreach (array(95,10,20,30,40,50,60,70,80,90) as $p) { ?>
			<option value="<?=$p?>,PERCENT"><?=$p?>th percentile</option>
			<? } ?>
		</select>
	</td>
	<td>
		<select name="vdef_vline_<?=$i?>">
			<?echo_h_simpleoption("vdef_vline_$i", 'no', array('no', 'yes'))?>
		</select>
	</td>
	<td>
		<?=h_color_picker('vdef', $i, gpost("vdef_color_$i", '', ''))?>
	</td>
	<td>
		<input type="text" name="vdef_legend_<?=$i?>" size="30" value="<?=h(gpost("vdef_legend_$i", '', ''))?>">
	</td>
</tr>
<? } ?>
<tr>
	<td colspan="6"><b>* VLINE</b> = Place a vertical line where this value occurs</td>
</tr>

</table>
<input type="hidden" name="vdef_cnt" value="<?=$i?>">
</dd>
<dt><input type="submit" value="Update"> <input type="submit" value="Done defining VDEFs" name="vdefdone"></dt>
</dl>
<? } ?>

<? if ($stage >= 4) { ?>
<h1 id="lines">Data series visualization</h1>
<dl>
<dt>These are the LINES and AREAS that you typically see in your graph area</dt>
<dd>
	<div name="line_picker"></div>
	<table border="1">
	<tr>
		<td class="tdheader">Width</td>
		<td class="tdheader">Area</td>
		<td class="tdheader">Data serie</td>
		<td class="tdheader">Color</td>
		<td class="tdheader">Legend text (may be empty)</td>
	</tr>

	<? 
	$max_num_lines = count(array_merge($vnames, $vdef_vnames)) * 3;
	if ($max_num_lines > 16) $max_num_lines = 16;
	$entered_lines = 0;
	for ($i = 0; $i < $max_num_lines; ++$i) {
	?>
	<tr>
		<td>
			<select name="line_width_<?=$i?>">
				<?echo_h_simpleoption("line_width_$i", 1, array(1,2,3,4,5))?>
			</select>
		</td>
		<td>
			<select name="line_area_<?=$i?>">
				<?echo_h_simpleoption("line_area_$i", 'no', array('yes', 'no'))?>
			</select>
		</td>
		<td>
			<select name="line_ds_<?=$i?>">
				<?echo_h_simpleoption("line_ds_$i", '---', array_merge(array('---'), $vnames, $vdef_vnames))?>
			</select>
		</td>
		<td>
			<?=h_color_picker('line', $i, gpost("line_color_$i", '', ''))?>
		</td>
		<td>
			<input type="text" name="line_legend_<?=$i?>" size="30" value="<?=h(gpost("line_legend_$i", '', ''))?>">
		</td>
	</tr>
	<?
		if (
			isset($_POST["line_ds_$i"]) && $_POST["line_ds_$i"] != '---' &&
			isset($_POST["line_color_$i"]) && $_POST["line_color_$i"] != ''
		) ++$entered_lines;
	}
	?>

	</table>
	</dd>
<dt><input type="submit" value="Update"> <input type="submit" value="Done defining LINES" name="linesdone"></dt>
<input type="hidden" name="lines_cnt" value="<?=$i?>">
</dl>
<? } ?>

<? if ($stage >= 5) { ?>
<h1 id="lines">Labels visualization</h1>
<dl>
<dt>These are the LABELS that you typically see beneath your graph area. You can print only VDEF single-value sources.</dt>
	<dd>
	<table border="1">
	<tr>
		<td class="tdheader">Data source</td>
		<td class="tdheader">Value format</td>
		<td class="tdheader">Value auto-scale to SI</td>
		<td class="tdheader">Optional text to append</td>
		<td class="tdheader">Text alignment</td>
		<td class="tdheader">Print inside graph</td>
		<!--<td class="tdheader">Time format</td>-->
	</tr>

	<? 
	$max_num_lines = count($vdef_vnames);
	if ($max_num_lines > 16) $max_num_lines = 16;
	if ($max_num_lines == 0 && $stage < 6) {
		$stage = 6;
		$_POST['labels_cnt'] = 0;
	}

	$vformats = array(
		array('.X' => '%.1lf'),
		array('.XX' => '%.2lf'),
		array('.XXX' => '%.3lf'),
		array('.XXXX' => '%.4lf'),
		array('XXX.X' => '%3.1lf'),
		array('XXX.XX' => '%3.2lf'),
		array('XXX.XXX' => '%3.3lf'),
		array('XXX.XXXX' => '%3.4lf'),
		array('. (no floating point)' => '%.0lf'),
		array('XXX. (no floating point)' => '%3.0lf'),
		array('.Xe+Y' => '%.1le'),
		array('.XXe+Y' => '%.2le)'),
		array('.XXXe+Y' => '%.3le'),
		array('.XXXXe+Y' => '%.4le'),
		array('XXX.Xe+Y' => '%3.1le'),
		array('XXX.XXe+Y' => '%3.2le'),
		array('XXX.XXXe+Y' => '%3.3le'),
		array('XXX.XXXXe+Y' => '%3.4le'),
	);
	$def_vformat = each($vformats);
	$def_vformat = $def_vformat['value'];

	for ($i = 0; $i < $max_num_lines; ++$i) {
	?>
	<tr>
		<td>
			<select name="print_ds_<?=$i?>">
				<?echo_h_simpleoption("print_ds_$i", '---', array_merge(array('---'), $vdef_vnames))?>
			</select>
		</td>
		<td>
			<select name="print_vformat_<?=$i?>">
				<?echo_h_simpleoption("print_vformat_$i",
					$def_vformat,
					$vformats)?>
			</select>
		</td>
		<td>
			<select name="print_si_<?=$i?>">
				<?echo_h_simpleoption("print_si_$i", '',
					array(array('no' => ''), array('yes, independent' => '%s'), array('yes, same unit for all labels' => '%S')))?>
			</select>
		</td>
		<td>
			<input type="text" name="print_text_<?=$i?>" size="10" value="<?=h(gpost("print_text_$i", '', ''))?>">
		</td>
		<td>
			<select name="print_just_<?=$i?>">
				<?echo_h_simpleoption("print_just_$i", '',
					array(
						array('none' => ''), array('justified' => '\\j'), array('left aligned' => '\\l'),
						array('right aligned' => '\\r'), array('centered' => '\\c'),
					)
				)?>
			</select>
		</td>
		<td>
			<select name="print_inside_<?=$i?>">
				<?echo_h_simpleoption("print_inside_$i", 'no', array('no', 'yes'))?>
			</select>
		</td>
		<!--<td>
			<select name="print_tformat_<?=$i?>">
				<?echo_h_simpleoption("print_tformat_$i", 'Do not print the time at all', array('Do not print the time at all', 'no'))?>
			</select>
		</td>-->
	</tr>
	<? } ?>

	</table>
	</dd>
<dt>
<input type="hidden" name="labels_cnt" value="<?=$i?>">
<input type="submit" value="Update"> <input type="submit" value="Done defining LABELS" name="labelsdone"></dt>
</dl>
<? } ?>

<? if ($stage >= 6) { ?>
<?
	$err = check_vnames(array_merge($vnames, $vdef_vnames), 0);
	if ($err == '') {
		if ($entered_lines == 0) $err = '# ERROR: You defined no LINES/AREAS at "Data series visualization".';
	}

	if ($err != '') {
?>
	<h1 id="error" style="background-color: red">Error</h1>
	<p><?=h($err)?></p>
<?
	} else {
?>
		<a name="cmd"><h1 id="archives" style="background-color: green">RRD graph command</h1></a>
		<p><pre>
<?
		echo "# The command is escaped for Linux bash\n\n";
		#print_r($_POST);
		#echo "STAGE: $stage";
		echo rrdgraph_cmd();
	}
?>
</pre></p>
<? } else { ?>
	<h1 style="background-color: magenta">Next action</h1>
	<p><?=h($stage_next_action[$stage])?>.</p>
<? } // stage < 6 ?>

<a name="bottom"></a>
<input type="hidden" name="stage" value="<?=$stage?>">
<? } /* no error */ ?>
</form>
<?
require('footer.php');
