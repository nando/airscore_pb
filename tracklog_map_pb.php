<?php
require 'authorisation.php';
require 'format.php';
require 'hc_pb.php';

if($_REQUEST["fullscreen"]==1)
  $map_class = 'fullscreen';
else
  $map_class = 'no-fullscreen';


require 'plot_track.php';
hchead();
hcmapjs();
hcscripts(array('json2.js', 'sprintf.js', 'plot_trackv3_pb.js'));
echo '<script type="text/javascript">';
sajax_show_javascript();
echo "</script>\n";
?>
<script type="text/javascript">
var map;
//<![CDATA[
function initialize() 
{
    var moptions =
        {
            zoom: 12,
            center: new google.maps.LatLng(-37, 143.644),
            mapTypeId: google.maps.MapTypeId.TERRAIN,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
            },
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL
            },
            panControl: false,
            scaleControl: false
        };
    map = new google.maps.Map(document.getElementById("map"), moptions);
    //map.setMapTypeId(google.maps.MapTypeId.TERRAIN);

<?php
//echo "map.addControl(new GSmallMapControl());\n";
//echo "map.addControl(new GMapTypeControl());\n";
//echo "map.addControl(new GScaleControl());\n";

$usePk = check_auth('system');
$link = db_connect();
$trackid = reqival('trackid');
$comPk = reqival('comPk');
$tasPk = reqival('tasPk');
$trackok = reqsval('ok');
$isadmin = is_admin('admin',$usePk,$comPk);
$interval = reqival('int');
$action = reqsval('action');
$extra = 0;

$comName='Highcloud OLC';
$tasName='';
$offset = 0;
if ($tasPk > 0 || $trackid > 0)
{
    if ($tasPk > 0)
    {
        $sql = "SELECT C.*, T.*,T.regPk as tregPk FROM tblCompetition C, tblTask T where T.tasPk=$tasPk and C.comPk=T.comPk";
    }
    else
    {
        $sql = "SELECT CTT.tasPk as ctask,C.*,CTT.*,T.*,T.regPk as tregPk FROM tblCompetition C, tblComTaskTrack CTT left outer join tblTask T on T.tasPk=CTT.tasPk where C.comPk=CTT.comPk and CTT.traPk=$trackid";
    }

    $result = mysql_query($sql,$link) or die('Query failed: ' . mysql_error());
    if ($row = mysql_fetch_array($result))
    {
        if ($tasPk == 0)
        {
            $tasPk = $row['ctask'];
        }
        if ($comPk == 0)
        {
            $comPk = $row['comPk'];
        }
        $comName = $row['comName'];
        $tasName = $row['tasName'];
        $tasDate = $row['tasDate'];
        $tasType = $row['tasTaskType'];
        $regPk = $row['tregPk'];
        $offset = $row['comTimeOffset'];
        if(!$tasName)
          $tasName = 'Open de Pedro Bernardo';
    }
}

if (($tasPk > 0) and ($tasType == 'race' || $tasType == 'speedrun' || $tasType == 'speedrun-interval' || $tasType == 'airgain' ))
{
    if ($trackid > 0)
    {
        echo "do_add_track($trackid);\n";
    }

    if ($tasType == 'airgain')
    {
            echo "do_add_region($regPk,$trackid);\n";
    }
    else
    {
        if ($isadmin)
        {
            echo "do_award_task($tasPk,$trackid);\n";
        }
        else
        {
            echo "do_add_task($tasPk);\n";
        }
    }
}
else if ($trackid > 0)
{
    echo "do_add_track_wp($trackid);\n";
    echo "do_add_track($trackid);\n";
    $sql = "SELECT max(trlTime) - min(trlTime) FROM tblTrackLog where traPk=$trackid";
    $result = mysql_query($sql,$link) or die('Time query failed: ' . mysql_error());
    $gtime = mysql_result($result, 0, 0);
}

if ($tasPk > 0)
{
    $sql = "select TR.*, T.*, P.* from tblTaskResult TR, tblTrack T, tblPilot P where TR.tasPk=$tasPk and T.traPk=TR.traPk and P.pilPk=T.pilPk order by TR.tarScore desc limit 10";
    $result = mysql_query($sql,$link) or die('Task Result selection failed: ' . mysql_error());
    $addable = Array();
    $addable['Añade otro vuelo'] = '0';
    while ($row = mysql_fetch_array($result))
    {
      if($row['traPk']!=$trackid)
        $addable[$row['pilFirstName'].' '.$row['pilLastName']] = $row['traPk'];
      else {
        $pilotName = $row['pilFirstName'].' '.$row['pilLastName'];
      }
    }
    
}
?>
}
google.maps.event.addDomListener(window, 'load', initialize);

    //]]>
</script>

<?
$title = 'Tracks '.$tasName;

hchead_cont($title.' - '.$comName,2);
hcheadbar($title,$active,$titler);
?>
  <h2><?= $title ?> <a href="/airscore/task_result_pb.php?comPk=<?= $comPk ?>&tasPk=<?= $tasPk ?>">ver resultados de la manga</a></h2>
<div id="pilot_track_info">
<a href="/leonardoxc/index.php?name=leonardo&op=show_flight&flightID=35"><? echo $pilotName ?></a>
</div>
<div id="map_view" class="<?= $map_class ?>"> 
<?
echo "<div id=\"tracks_controls\">";
echo "<div>";
if ($tasPk > 0)
{
    echo fselect('trackid', '', $addable);
}
else if ($trackid > 0)
{
    $sql = "select T2.*, P.* from tblTrack T, tblTrack T2, tblPilot P where T2.traStart>date_sub(T.traStart, interval 6 hour) and T2.traStart<date_add(T.traStart, interval 6 hour) and T.traPk=$trackid and P.pilPk=T2.pilPk order by T2.traLength desc limit 10";
    $result = mysql_query($sql,$link) or die('Task Result selection failed: ' . mysql_error());
    $addable = Array();
    while ($row = mysql_fetch_array($result))
    {
      if($row['traPk']!=$trackid)
        $addable[$row['pilFirstName'].' '.$row['pilLastName']] = $row['traPk'];
    }

    echo fselect('trackid', '', $addable);
}
else
{
    echo "<input type=\"text\" name=\"trackid\" id=\"trackid\" size=\"8\"\">";
}
echo "<input type=\"hidden\" name=\"foo\" id=\"foo\">";
echo "</div>";
echo "<div>";
echo "<a href=\"/BACK\" onclick=\"back(); return false;\" title=\"Un paso hacia atrás\"><img src=\"/airscore/images/back.png\"/></a>";
echo "<a href=\"/PLAY\" id=\"pause\" onclick=\"pause_map(); return false;\" title=\"Iniciar animación\"><img id=\"play_or_pause\" src=\"/airscore/images/play.png\"/></a>";
echo "<a href=\"/FORWARD\" onclick=\"forward(); return false;\" title=\"Un paso hacia adelante\"><img src=\"/airscore/images/forward.png\"/></a>";
echo "&nbsp;";
echo "<a href=\"/RESET\" onclick=\"reset_map(); return false;\" title=\"Reiniciar para ver de nuevo\"><img src=\"/airscore/images/reset.png\"/></a>";
echo "</div>";
echo "<div>";
if($_REQUEST["fullscreen"]==1)
  echo "<a id=\"toggle_fullscreen\" href=\"/airscore/tracklog_map_pb.php?trackid=$trackid&comPk=$comPk\" title=\"Ver en pantalla normal\"><img src=\"/airscore/images/no-fullscreen.png\" alt=\"Vista normal\" /></a>";
else
  echo "<a id=\"toggle_fullscreen\" href=\"/airscore/tracklog_map_pb.php?trackid=$trackid&comPk=$comPk&fullscreen=1\" title=\"Ver en pantalla completa\"><img src=\"/airscore/images/fullscreen.png\" alt=\"Pantalla completa\" /></a>";
echo "</div>";
echo "</div>";
echo "<div id=\"map\" class=\"$map_class\"></div>";
echo "</div>";
hcfooter(); 
mysql_close($link);
?>
