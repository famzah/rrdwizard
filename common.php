<?
error_reporting(E_ALL);
ini_set('display_errors', '1');

function is_valid_vname($s) {
	return preg_match('/^[a-zA-Z0-9_]{1,19}$/', $s);
}

// you have to put the result in SINGLE quotes!
function esh($s) {
	return strtr($s, array("'" => "'\\''", "\n" => " ", "\r" => " "));
}

function hr_sec($t) { // month is assumed to be (365/12) days
	if ($t == 0) return '---';

	$hr = array('year', 'month', 'day', 'hour', 'min', 'sec');
	$data[0] = floor($t / (60*60*24*365));
	$t -= $data[0] * 60*60*24*365;
	$data = array_merge($data, explode(' ',gmdate("\U z H i s", $t))); # day hour min sec
	$mon = floor($data[2] / (365/12)); // construct month
	$data[2] = $data[2] - $mon*(365/12);
	$data[1] = $mon;
	foreach ($data as $key => $value) {
		$value = preg_replace('/^0+/', '', $value); # strip leading zeroes
		if ($value === NULL) trigger_error("preg_replace() failed", E_USER_ERROR);
		if ($value == '') $value = 0;
		$data[$key] = $value;
	}
	$allow_next = 1;
	$s = array();
	foreach ($data as $key => $value) {
		if ($value != 0) {
			$allow_next = 0;
		}
		if ($value == 0 && $allow_next) continue;
		$s[] = sprintf('%d%s', $value, $hr[$key]);
	}
	$s = array_reverse($s);
	foreach ($s as $sk => $sv) {
		$got = 0;
		foreach ($hr as $value) { // strip 0sec, ... from the end of $s[]
			if ($sv == "0$value") {
				$got = 1;
				unset($s[$sk]);
				break;
			}
		}
		if (!$got) break; // cut only from the end
	}
	$s = array_reverse($s);
	return join(' ', $s);

}

function h($s) {
	return htmlspecialchars($s);
}

function gpost($key, $type, $def_value = NULL) {
	$need_def_value = 0;

	if (!isset($_POST[$key])) {
		$need_def_value = 1;
	} else {
		if ($type == 'n') {
			if (!is_numeric($_POST[$key])) {
				$need_def_value = 1;
			}
		} elseif ($type == '') {
			// nothing special for this type
		} else {
			trigger_error("gpost(): Bad type: $type", E_USER_ERROR);
		}
	}

	if ($need_def_value) {
		if ($def_value === NULL) {
			trigger_error("gpost(): Request for POST variable '$key' of type '$type' but is unset", E_USER_ERROR);
		}
		$_POST[$key] = $def_value;
	}

	$_POST[$key] = trim($_POST[$key]);
	return $_POST[$key];
}

function h_rra_finalinfo($step, $steps, $rows) {
	$rra_every_t = $step*$steps;
	$rra_archive_t = $rra_every_t * $rows;
	return sprintf("Archive point is saved every <b>%s</b>, archive is kept for <b>%s</b> back.", h(hr_sec($rra_every_t)), h(hr_sec($rra_archive_t)));
}

function echo_h_simpleoption($select_name, $select_def_value, $opt_array) {
	foreach ($opt_array as $v) {
		if (!is_scalar($v)) {
			if (!is_array($v)) trigger_error("not a scalar and not an array -> not supported", E_USER_ERROR);
			$data = each($v);
			$v = $data['value'];
			$k = $data['key'];
		} else {
			$k = $v;
		}
		$selected = ($v == gpost($select_name, '', $select_def_value));
	?>
		<option<?=($selected ? ' selected' : '')?> value="<?=h($v)?>"><?=h($k)?></option>
	<?
	}
}

function check_vnames($vnames, $mode) {
	sort($vnames, SORT_STRING);
	$l = '';
	foreach ($vnames as $vname) {
		if (!is_valid_vname($vname)) {
			return sprintf("# ERROR: Virtual name '%s' contains invalid symbols.", $vname);
		}
		if ($l == $vname) {
			return sprintf("# ERROR: Duplicate virtual name found: %s. All virtual names in all %s sections _must_ be unique.",
				$vname,
				(!$mode ? 'DEF, CDEF and VDEF' : 'DS')
			);
		}
		$l = $vname;
	}
	return '';
}
