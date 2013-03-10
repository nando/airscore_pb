<?php
require 'authorisation.php';
require 'hc_pb.php';
require 'format.php';
require 'xcdb.php';

$comPk = intval($_REQUEST['comPk']);

$usePk = check_auth('system');
$link = db_connect();
$tasPk = intval($_REQUEST['tasPk']);
$isadmin = 0;
if (!array_key_exists('pr', $_REQUEST))
{
    $isadmin = is_admin('admin',$usePk,$comPk);
}

$fdhv= '';
$classstr = '';
if (array_key_exists('class', $_REQUEST))
{
    $cval = intval($_REQUEST['class']);
    $carr = array (
        "'1/2'", "'2'", "'2/3'", "'competition'"       
        );
    $cstr = array ( "Fun", "Sport", "Serial", "Open", "Women" );
    $classstr = "<b>" . $cstr[intval($_REQUEST['class'])] . "</b> - ";
    if ($cval == 4)
    {
        $fdhv = "and P.pilSex='F'";
    }
    else
    {
        $fdhv = $carr[intval($_REQUEST['class'])];
        $fdhv = "and traDHV=$fdhv ";
    }
}

if (array_key_exists('score', $_REQUEST))
{
    $out = '';
    $retv = 0;
    exec(BINDIR . "task_score.pl $tasPk", $out, $retv);
}

if (array_key_exists('tarup', $_REQUEST))
{
    $tarPk = intval($_REQUEST['tarup']);
    $glider = addslashes($_REQUEST["glider$tarPk"]);
    $dhv = addslashes($_REQUEST["dhv$tarPk"]);
    $flown = addslashes($_REQUEST["flown$tarPk"]);
    $penalty = addslashes($_REQUEST["penalty$tarPk"]);
    $traPk = intval($_REQUEST["track$tarPk"]);
    $resulttype = 'lo';
    if ($flown == 'abs' || $flown == 'dnf' || $flown == 'lo')
    {
        $resulttype = $flown;
        $flown = 0;
    }
    else
    {
        $flown = $flown * 1000;
        if (0 + $flown == 0) 
        {
            $resulttype = 'dnf';
        }
    }

    $query = "update tblTaskResult set tarDistance=$flown, tarPenalty=$penalty, tarResultType='$resulttype' where tarPk=$tarPk";
    $result = mysql_query($query) or die('Task Result update failed: ' . mysql_error());
    $query = "update tblTrack set traGlider='$glider', traDHV='$dhv' where traPk=$traPk";
    $result = mysql_query($query) or die('Glider update failed: ' . mysql_error());
    # recompute every time?
    $out = '';
    $retv = 0;
    exec(BINDIR . "task_score.pl $tasPk", $out, $retv);
}

if (array_key_exists('addflight', $_REQUEST))
{
    $fai = intval($_REQUEST['fai']);
    if ($fai > 0)
    {
        $query = "select pilPk from tblPilot where pilHGFA=$fai";
        $result = mysql_query($query) or die('Query pilot (fai) failed: ' . mysql_error());
    }

    if (mysql_num_rows($result) == 0)
    {
        $fai = addslashes($_REQUEST['fai']);
        $query = "select P.pilPk from tblComTaskTrack T, tblTrack TR, tblPilot P 
                    where T.comPk=$comPk 
                        and T.traPk=TR.traPk 
                        and TR.pilPk=P.pilPk 
                        and P.pilLastName='$fai'";
        $result = mysql_query($query) or die('Query pilot (name) failed: ' . mysql_error());
    }

    if (mysql_num_rows($result) > 0)
    {
        $pilPk = mysql_result($result,0,0);
        $flown = floatval($_REQUEST["flown"]) * 1000;
        $penalty = intval($_REQUEST["penalty"]);
        $glider = addslashes($_REQUEST["glider"]);
        $dhv = addslashes($_REQUEST["dhv"]);
        $resulttype = addslashes($_REQUEST["resulttype"]);
        $tasPk = intval($_REQUEST["tasPk"]);
        if ($resulttype == 'dnf' || $resulttype == 'abs')
        {
            $flown = 0.0;
        }

        $query = "select tasDate from tblTask where tasPk=$tasPk";
        $result = mysql_query($query) or die('Task date failed: ' . mysql_error());
        $tasDate=mysql_result($result,0);

        $query = "insert into tblTrack (pilPk,traGlider,traDHV,traDate,traStart,traLength) values ($pilPk,'$glider','$dhv','$tasDate','$tasDate',$flown)";
        $result = mysql_query($query) or die('Track Insert result failed: ' . mysql_error());

        $maxPk = mysql_insert_id();

        #$query = "select max(traPk) from tblTrack";
        #$result = mysql_query($query) or die('Max track failed: ' . mysql_error());
        #$maxPk=mysql_result($result,0);

        $query = "insert into tblTaskResult (tasPk,traPk,tarDistance,tarPenalty,tarResultType) values ($tasPk,$maxPk,$flown,$penalty,'$resulttype')";
        $result = mysql_query($query) or die('Insert result failed: ' . mysql_error());

        $out = '';
        $retv = 0;
        exec(BINDIR . "task_score.pl $tasPk", $out, $retv);
    }
    else
    {
        echo "Unknown pilot: $fai<br>";
    }
}

$depcol = 'Dpt';
$row = get_comtask($link,$tasPk);
if ($row)
{
    $comName = $row['comName'];
    $comPk = $row['comPk'];
    $comTOffset = $row['comTimeOffset'] * 3600;
    $tasName = $row['tasName'];
    $tasDate = $row['tasDate'];
    $tasTaskType = $row['tasTaskType'];
    $tasTaskStart = substr($row['tasTaskStart'],11);
    $tasTaskFinish = substr($row['tasFinishTime'],11);
    $tasStartTime = substr($row['tasStartTime'],11);
    $tasCloseTime = substr($row['tasStartCloseTime'],11);
    $tasDistance = round($row['tasDistance']/1000,2);
    $tasShortest = round($row['tasShortRouteDistance']/1000,2);
    $tasQuality = round($row['tasQuality'],2);
    $tasDistQuality = round($row['tasDistQuality'],2);
    $tasTimeQuality = round($row['tasTimeQuality'],2);
    $tasLaunchQuality = round($row['tasLaunchQuality'],2);
    $tasInterval = $row['tasSSInterval'];
    $tasTotalDistanceFlown = round($row['tasTotalDistanceFlown']/1000,0);
    $tasPilotsLaunched = $row['tasPilotsLaunched'];
    $tasPilotsGoal = $row['tasPilotsGoal'];

    if ($row['tasDeparture'] == 'leadout')
    {
        $depcol = 'Ldo';
    }
}

$waypoints = get_taskwaypoints($link,$tasPk);

// incorporate $tasTaskType / $tasDate in heading?
$hdname =  "$tasName - $comName";
hcheader($hdname,2,"${classstr}${tasDate}");
?>
  <h2><?= $tasName ?> <a href="/airscore/route_map_pb.php?comPk=<?= $comPk ?>&tasPk=<?= $tasPk ?>">ver mapa</a></h2>

  <iframe class="task_map" scrolling="no"
          src="/airscore/route_map_pb.php?embed=1&comPk=<?= $comPk ?>&tasPk=<?= $tasPk ?>"></iframe>

<h3>Carrera a Gol (<?= strtoupper($tasTaskType) ?>) de <?= $tasShortest ?> km</h3>
<div class="fs_res">

    <table>
    <tbody>
    <tr>
      <th>Fecha</th><td><?= $tasDate ?></td>
      <th>Inicio</th><td><?= $tasTaskStart ?></td>
      <th>Fin</th><td><?= $tasTaskFinish ?></td>
    </tr>
    <tr>
      <th>Ventanas</th><td><?= $tasInterval ?> min.</td>
      <th>Primera</th><td><?= $tasStartTime ?></td>
      <th>Última</th><td><?= $tasCloseTime?></td>
    </tr>
    <tr>
      <th>Distancia</th><td><?= $tasShortest ?> km</td>
      <th>Dist.Blz.</th><td><?= $tasDistance ?> km</td>
      <th>Total V.</th><td><?= $tasTotalDistanceFlown ?> km</td>
    </tr>
    </tbody>
    </table>
<br/>
  <table>
  <thead>
      <tr>
        <th>#</th>
        <th>Id</th>
        <th>Descripción</th>
        <th>Tipo</th>
        <th>Radio</th>
        <th>Km.</th>
      </tr>
  </thead>
  <tbody>
<?

# Waypoints Info

foreach ($waypoints as $row)
{
  if($row['tawType']=='waypoint')
    $wptype = 'baliza';
  elseif($row['tawType']=='goal')
    $wptype = '<strong>gol</strong>';
  else
    $wptype = "<strong>".$row['tawType']."</strong>";
?>
      <tr>
        <td><?= $row['tawNumber'] ?></td>
        <td><strong><?= $row['rwpName'] ?></strong></td>
        <td style="text-align: left"><?= $row['rwpDescription'] ?></td>
        <td><?= $wptype ?></td>
        <td><?= $row['tawRadius'] . "m" ?></td>
        <td style="text-align:right"><strong><?= round($row['ssrCumulativeDist']/1000,1) ?></strong></td>
      </tr>
<?
}
?>
    </tbody>
    </table>
    
    <br/>

    <table>
    <tbody>
    <tr>
      <th>Total Vuelos</th><td><?= $tasPilotsLaunched ?></td>
      <th>Total Goles</th><td><?= $tasPilotsGoal ?></td>
      <th>Calidad Manga</th><td><?= $tasQuality ?></td>
    </tr>
    <tr>
      <th>Calidad Dist.</th><td><?= number_format($tasDistQuality,2) ?></td>
      <th>Calid.Tiempo</th><td><?= number_format($tasTimeQuality,2) ?></td>
      <th>Calid.Despeg.</th><td><?= number_format($tasLaunchQuality,2) ?></td>
    </tr>
    </tbody>
    </table>
<?


# Pilot Info
$pinfo = array();
# total, launched, absent, goal, es?

# Formula / Quality Info
$finfo = array();
# gap, min dist, nom dist, nom time, nom goal ?

# quality, dist, time, launch, available dist, available time, available lead, arrival
#$qinfo = array();
#$qinfo[] = array( fb("Quality"), fb("$tasQuality"));
#$qinfo[] = array( fb("Dist"), $tasDistQuality, fb("Time"), $tasTimeQuality, fb("Launch"), $tasLaunchQuality );
#echo ftable($qinfo, "border=\"2\" cellpadding=\"3\" cellspacing=\"0\" alternate-colours=\"yes\" valign=\"top\" align=\"right\"", array('class="d"', 'class="l"'), '');
#echo "<br><p>";


// FIX: Print out task quality information.

// add in country from tblCompPilot if we have entries ...
echo "<br>";
echo "<table class='result_list'>";
echo "<thead>";
echo "<tr class=\"h\"><th>Puesto</th><th>Nombre</th><th>Ala</th>";
echo "<th>SS</th><th>ES</th><th>Tiempo</th><th>Kms</th><th>Pen</th>";
echo "<th>$depcol</th><th>Arv<b></td><th>Spd</th><th>Dst</th><th>Total</th></tr>\n";
echo "</thead>";
$count = 1;

$sql = "select TR.*, T.*, P.* from tblTaskResult TR, tblTrack T, tblPilot P where TR.tasPk=$tasPk $fdhv and T.traPk=TR.traPk and P.pilPk=T.pilPk order by TR.tarScore desc, P.pilFirstName";
$result = mysql_query($sql,$link) or die('Task Result selection failed: ' . mysql_error());
$lastscore = 0;
$hh = 0;
$mm = 0;
$ss = 0;
while ($row = mysql_fetch_array($result))
{
    $pid = $row['pilPk'];
    $name = $row['pilFirstName'] . ' ' . $row['pilLastName'];
    $nation = $row['pilNationCode'];
    $tarPk = $row['tarPk'];
    $traPk = $row['traPk'];
    $dist = round($row['tarDistanceScore']);
    $dep = round($row['tarDeparture']);
    $arr = round($row['tarArrival']);
    $speed = round($row['tarSpeedScore']);
    $score = round($row['tarScore']);
    $resulttype = $row['tarResultType'];
    $start = $row['tarSS'];
    $end = $row['tarES'];
    $startf = 0;
    $endf = 0;
    if ($end)
    {
        $hh = floor(($end - $start) / 3600);
        $mm = floor((($end - $start) % 3600) / 60);
        $ss = ($end - $start) % 60;
        $timeinair = sprintf("%01d:%02d:%02d", $hh,$mm,$ss);
        $hh = floor(($comTOffset + $start) / 3600) % 24;
        $mm = floor((($comTOffset + $start) % 3600) / 60);
        $ss = ($comTOffset + $start) % 60;
        $startf = sprintf("%02d:%02d:%02d", $hh,$mm,$ss);
        $hh = floor(($comTOffset + $end) / 3600) % 24;
        $mm = floor((($comTOffset + $end) % 3600) / 60);
        $ss = ($comTOffset + $end) % 60;
        $endf = sprintf("%02d:%02d:%02d", $hh,$mm,$ss);
    }
    else
    {
        $timeinair = 0;
    }
    $time = ($end - $start) / 60;
    $tardist = round($row['tarDistance']/1000,2);
    $penalty = round($row['tarPenalty']);
    $glider = htmlspecialchars($row['traGlider']);
    $dhv = $row['traDHV'];
    if (0 + $tardist == 0)
    {
        $tardist = $resulttype;
    }

    if ($lastscore != $score)
    {
        $place = "$count";
    }
    else
    {
        $place = '';
    }
    $lastscore = $score;

    if ($count % 2 == 0)
    {
        $class = "d";
    }
    else
    {
        $class = "l";
    }

    echo "<tr class=\"$class fs_res_res_row\" onmouseover=\"this.className = 'hover'\" onmouseout=\"this.className='fs_res_res_row'\">";
    echo "<td align='center'><b>$place</b></td>";
    echo "<td><span class='pilot_id'>$pid</span><a href=\"tracklog_map_pb.php?trackid=$traPk&comPk=$comPk\">$name</a></td>";
    echo "<td>$glider ($dhv)</td>";
    echo "<td>$startf</td><td>$endf</td>";
    echo "<td>$timeinair</td><td>$tardist</td><td>$penalty</td>\n";
    echo "<td>$dep</td><td>$arr</td><td>$speed</td><td>$dist</td><td align=\"right\"><b>$score</b></td>";
    echo "</tr>\n";

    $count++;
}
echo "</table>";
echo "</div>";
hcfooter(); 
mysql_close($link);
?>
