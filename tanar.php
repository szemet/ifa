<?
require_once('login.php');
require_once('fogado.inc.php');
require_once('tanar.class.php');

$TANAR = new Tanar($_REQUEST['id']);

switch ($_REQUEST['mod']) {
	# az egyes id�pontok m�dos�t�sa
	case 1:
		reset($_POST);
		pg_query("BEGIN TRANSACTION");
		while (list($key, $diak) = each($_POST)) {
			if ( ereg ("^r([0-9]+)$", $key, $match) ) {
				unset($q);
				$ido = $match[1];
				if (isset($TANAR->diak[$ido])) {
					if ($diak=="x") $q = "DELETE FROM Fogado WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
					elseif ($diak != $TANAR->diak[$ido])
						$q = "UPDATE Fogado SET diak=" . $diak . " WHERE fid=" . fid . " AND tanar=" . $TANAR->id . " AND ido=" . $ido;
				}
				else {
					if ($diak != "x") $q = "INSERT INTO Fogado VALUES (" . fid . ", " . $TANAR->id . ", " . $ido . ", " . $diak . ")";
				}
				if (isset($q)) {
					pg_query($q);
					ulog(0, $q);
				}
			}
		}
		pg_query("END TRANSACTION");
		break;

	# az intervallum b�v�t�se
	case 2:
		$UJ_min = $_REQUEST['kora'] +  $_REQUEST['kperc'];
		$UJ_max = $_REQUEST['vora'] +  $_REQUEST['vperc'];
		$tartam = $_REQUEST['tartam'];
		unset ($INSERT);

		while ($UJ_min%$tartam) $UJ_min++;

		/* Ha m�r van bejegyzett id�pontja, akkor a b�v�t�s az ez el�tti
		   �s az ez ut�ni id�kre vonatkozik */
		if ($TANAR->fogad) {
			for ($ido = $UJ_min; $ido < $TANAR->IDO_min; $ido++ ) {
				$INSERT[] = fid . "\t" . $TANAR->id . "\t$ido\t" . ($ido%$tartam?-1:0) . "\n";
			}
			for ($ido = $TANAR->IDO_max+1; $ido < $UJ_max; $ido++) {
				$INSERT[] = fid . "\t" . $TANAR->id . "\t$ido\t" . ($ido%$tartam?-1:0) . "\n";
			}
		}
		else { // m�g nem volt fogad��r�ja bejegyezve
			for ($ido = $UJ_min; $ido < $UJ_max; $ido++) {
				$INSERT[] = fid . "\t" . $TANAR->id . "\t$ido\t" . ($ido%$tartam?-1:0) . "\n";
			}
		}

		if ( pg_copy_from ($db, "fogado", &$INSERT) ) {
			ulog (0, $TANAR->tnev." b�v�t�s: $UJ_min -> $UJ_max ($tartam)" );
		}
		else {
			ulog (0, "SIKERTELEN B�V�T�S: " . $TANAR->tnev . "($UJ_min -> $UJ_max)" );
		}

		break;
}

$TANAR = new Tanar($_REQUEST['id']); # �jra beolvassuk az adatb�zisb�l

Head("Fogad��ra - " . $TANAR->tnev);

echo "\n<table width=100%><tr>\n"
	. "<td><h3>" . $TANAR->tnev .  " (" . $FA->datum . ")</h3>\n"
	. "<td align=right><a href='" . $_SERVER['PHP_SELF'] . "?id=" . $TANAR->id . "&kilep='> Kil�p�s </a>\n</table>\n";

# A k�ls� t�bl�zat els� cell�j�ban az id�pont-lista
$TABLA = "<table border=0><tr><td>\n";

if (ADMIN) {
	if ($TANAR->fogad) {
		$TABLA .= "<form method=post name=tabla>\n<table border=1>\n"
			. "<tr><th><th>A<th>B<th>C<th>D<th>E\n"
			. "    <td colspan=2 align=right><input type=hidden name=mod value=1>\n"
			. "       <input type=reset value='RESET'>\n"
			. "       <input type=submit value=' Mehet '>\n";
		for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido++) {
			$TABLA .= ($ido%2?"<tr class=paratlan>":"<tr>");
			$diak = $TANAR->diak[$ido];
			$TABLA .= "<td>" . FiveToString($ido);
			$TABLA .= "  <td class=foglalt><input type=radio name=r$ido value=x" . (!isset($diak)?" checked":"") . ">\n";
			$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=0" . ($diak=="0"?" checked":"") . ">\n";
			$TABLA .= "  <td class=szabad><input type=radio name=r$ido value=-1" . ($diak=="-1"?" checked":"") . ">\n";
			$TABLA .= "  <td class=szuloi><input type=radio name=r$ido value=-2" . ($diak=="-2"?" checked":"") . ">\n";
			if ($diak>0) {
				$TABLA .= "  <td class=sajat><input type=radio name=r$ido value=$diak checked><td><a class=diak href=fogado.php?"
					. "tip=diak&id=" . $diak . ">" . $TANAR->dnev[$ido] . "</a>\n";
			} else {
				$TABLA .= "  <td colspan=2>&nbsp;\n";
			}
		}
		$TABLA .= "<tr><td colspan=7 align=right><input type=hidden name=mod value=1>\n"
			. "       <input type=reset value='RESET'>\n"
			. "       <input type=submit value=' Mehet '>\n"
			. "</form>\n"
			. "</table>\n"
	# A k�ls� t�bl�zat m�sodik cell�ja
			. "<td>&nbsp;\n"
			. "<td valign=top>\n";
	}

	$TABLA .= "<br><b>Jelmagyar�zat:</b><ul>\n"
		. "   A: nincs itt<br>\n"
		. "   B: fogad� id�pont kezdete<br>\n"
		. "   C: - id�pont folytat�sa<br>\n"
		. "   D: sz�l�i �rtekezlet<br>\n"
		. "   E: m�r bejelentkezett di�k\n"
		. "</ul>\n"
		. "<script language=JavaScript><!--\n"
		. "function fivedel() {\n"
		. "  for (var i=0; i<document.tabla.length; i++) {\n"
		. "    o = document.tabla.elements[i]; // az �rlap elemeit veszi sorra\n"
		. "    if (o.value == '-1') {          // ha �ppen '-1'-es gombn�l tartunk\n"
		. "      ido = parseInt(o.name.substr(1,10));\n"
		. "      if (o.checked) eval ('document.tabla.' + o.name + '[1].checked = 1');\n"
		. "    }\n"
		. "  }\n"
		. "}\n"
		. "function nincs() {\n"
		. "  for (var i=0; i<document.tabla.length; i++) {\n"
		. "    o = document.tabla.elements[i]; // az �rlap elemeit veszi sorra\n"
		. "    if (o.value == 'x') o.checked = true;\n"
		. "  }\n"
		. "}\n"
		. "//--></script>\n"
		. "<form method=post>\n"
		. "  <input type=hidden name=mod value=2>\n";

	if ($TANAR->fogad) {
		$TABLA .= "<p class=center>B�v�t�s: "
			. SelectIdo("kora", "kperc", $TANAR->IDO_min) . " - \n"
			. SelectIdo("vora", "vperc", $TANAR->IDO_max) . "\n &nbsp; &nbsp;"
			. SelectTartam('tartam') . "\n"
			. "  <input type=submit value=' Uccu! '></p><br><br>\n"
			. "</form>\n"
			. "<p class=elso><i>Gombok gyors �ll�t�sa:</i>\n"
			. "  <li>Ha m�gsem fog fogadni (�sszes -> A):\n"
			. "      <br> &nbsp; &nbsp; <input type=button value=' Megjel�l ' onClick='nincs()'>\n"
			. "  <li>Ha az 5 percekben is fogadni akar (�sszes: C -> B):\n"
			. "      <br> &nbsp; &nbsp; <input type=button value=' Megjel�l ' onClick='fivedel()'>\n"
			. "  <br>(Ezek ut�n m�g kell a ,,Mehet'' gomb!)\n\n"
			. "</table>\n";
	}
	else {
		$TABLA .= "  Fogad: "
			. SelectIdo("kora", "kperc", $FA->IDO_min) . " - \n"
			. SelectIdo("vora", "vperc", $FA->IDO_max) . "\n &nbsp; &nbsp;"
			. SelectTartam('tartam') . "\n"
			. "  <input type=submit value=' Uccu! '><br>\n"
			. "</form>\n"
			. "</table>\n";
	}

} else {
	$elozo = 0;
	for ($ido = $TANAR->IDO_min; $ido<$TANAR->IDO_max; $ido+=(2-$TANAR->ODD)) {
		$ora = floor($ido/12);
		if ($ora != $elozo) { $elozo = $ora; $TABLA .= "<tr><td colspan=3><hr>\n"; }
		$TABLA .= ($ido%2?"<tr class=paratlan>":"<tr>");
		$diak = $TANAR->diak[$ido];
		$TABLA .= "<td" . ($diak=="-2"?" class=szuloi":"") . ">" . FiveToString($ido)
			. "<td> -- <td>" . ($diak>0?$TANAR->dnev[$ido]:"&nbsp;") . "\n";
	}
	$TABLA .= "</table>\n";

}

print $TABLA;

pg_close ($db);
Tail();

?>

