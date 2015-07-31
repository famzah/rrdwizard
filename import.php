<?
require_once('common.php');
require('header.php');

$err = '';

$ds = array();
$rra = array();
$step = -1;
$got_data = 0;

$ds_p = array();
$rra_p = array();

function rq($s) {
	if (preg_match('/^"(.*)"$/', $s, $m)) {
		return $m[1];
	}
	return $s;
}

function parse_input() {
	global $ds, $rra, $step, $got_data, $ds_p, $rra_p;

	$s = gpost('text', '', '');

	if ($s != '') {
		$text = explode("\n", $s);
		foreach ($text as $line) {
			$line = trim($line);
			if (preg_match('/^(ds|rra)\[([a-zA-Z0-9_]+)\]\.(\S+) = (.+)$/', $line, $m)) {
				if ($m[1] == 'ds') {
					$ds[$m[2]][$m[3]] = $m[4];
				} elseif ($m[1] == 'rra') {
					$rra[$m[2]][$m[3]] = $m[4];
				} else {
					trigger_error('oops', E_USER_ERROR);
				}
			} elseif (preg_match('/^step = (\d+)$/', $line, $m)) {
				$step = $m[1];
			} else {
				// let's not be too strict and do not warn about unknown lines
			}
		}

		if ($step == -1) {
			return "Unable to find the step value.";
		}
		if (count($ds) == 0) {
			return "No data sources found.";
		}
		if (count($rra) == 0) {
			return "No RRAs found.";
		}

		foreach ($ds as $name => $data) {
			if (!array_key_exists('type', $data)) {
				return "Type for DS '$name' is unknown";
			}
			$ds_p[] = array(
				'name' => rq($name),
				'type' => rq($data['type']),
			);
		}
		foreach ($rra as $name => $data) {
			if (!array_key_exists('cf', $data)) {
				return "CF for RRA '$name' is unknown";
			}
			if (!array_key_exists('rows', $data)) {
				return "Rows for RRA '$name' are unknown";
			}
			if (!array_key_exists('pdp_per_row', $data)) {
				return "pdp_per_row for RRA '$name' is unknown";
			}
			$rra_p[] = array(
				'cf' => rq($data['cf']),
				'steps' => rq($data['pdp_per_row']),
				'rows' => rq($data['rows']),
			);
		}

		$got_data = 1;
	}
	return '';
}

function dump_p($ar, $name) {
	foreach ($ar as $i => $data) {
		foreach ($data as $k => $v) {
		?>
		<input name="<?=$name?><?=$k?>_<?=$i?>" type="hidden" value="<?=h($v)?>">
		<?
		}
	}
}

$err = parse_input();
?>

<? if ($err != '') { ?>
<h1 id="error" style="background-color: red">ERROR</h1>
<p><?=h($err)?></p>
<? } ?>

<? if ($got_data) { ?>
<h1 id="ok" style="background-color: green">Parsing successful</h1>
<dl>
	<dt>
	<form method="post" action="rrdgraph.php">
		<input name="step" type="hidden" value="<?=h($step)?>">
		<input name="ds_rows" type="hidden" value="<?=h(count($ds_p))?>">
		<input name="rra_rows" type="hidden" value="<?=h(count($rra_p))?>">
		<?dump_p($ds_p, 'ds')?>
		<?dump_p($rra_p, 'rra')?>
		<input type="submit" value="Proceed to Graph a RRD">
	</form>
	</dt>
</dl>
<? } ?>

<form method="post" action="?">
<h1 id="links">This section allows you to import and then create a graph for an existing RRD</h1>
<dl>
	<dt>Please execute `rrdtool info <b>filename.rrd</b>` on your RRD file and then paste the whole output below:</dt>
	<dd>
		<textarea name="text" cols="80" rows="30"><?=h(gpost('text', '', ''))?></textarea><br>
		<input type="submit" value="Submit">
	</dd>
</dl>
</form>
<?
require('footer.php');
