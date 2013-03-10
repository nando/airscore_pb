<?php
require 'authorisation.php';
require 'hc2.php';

$link = db_connect();

$tasPk = intval($_REQUEST['tasPk']);
//$sr = intval($_REQUEST['sr']);

$sql = "SELECT T.regPk, C.comName, T.tasName, T.tasDistance, C.comPk FROM tblCompetition C, tblTask T where T.tasPk=$tasPk and C.comPk=T.comPk";
$result = mysql_query($sql,$link) or die('Query failed: ' . mysql_error());
if (mysql_num_rows($result) > 0)
{
    $regPk = mysql_result($result,0,0);
    $comName = mysql_result($result,0,1);
    $tasName = mysql_result($result,0,2);
    $tasDistance = mysql_result($result,0,3);
    $comPk = mysql_result($result,0,4);
}

hchead();
echo "<title>Mapa - $tasName - $comName</title>";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/style.css\" media=\"screen\" />";

hcmapjs();
?>
<script type="text/javascript">
    //<![CDATA[
function load() 
{
    if (GBrowserIsCompatible()) 
    {
<?php
echo "var map = new GMap2(document.getElementById(\"map\"));\n";
echo "map.addMapType(G_PHYSICAL_MAP);\n";
echo "map.setMapType(G_PHYSICAL_MAP);\n";
echo "map.addControl(new GSmallMapControl());\n";

if ($tasPk > 0)
{
    $sql = "SELECT W.* FROM tblTaskWaypoint T, tblRegionWaypoint W where T.tasPk=$tasPk and W.rwpPk=T.rwpPk order by T.tawNumber";
    $result = mysql_query($sql,$link) or die('Query failed: ' . mysql_error());
    $prefix='rwp';
    $first = 1;
}

echo "var polyline = new GPolyline(["; 
while($row = mysql_fetch_array($result))
{
    if ($first)
    {
        $first = 0;
        $clat = $row["${prefix}LatDecimal"];
        $clong = $row["${prefix}LongDecimal"];
    }
    else
    {
        echo ",\n";
    }
    echo "new GLatLng(" . $row["${prefix}LatDecimal"] . "," . $row["${prefix}LongDecimal"] . ")";
}
echo "],\"#0000ff\", 2, 1);\n";

$sr = 0;
if ($tasPk > 0)
{
    $sql = "SELECT T.* FROM tblShortestRoute T where T.tasPk=$tasPk order by T.ssrNumber";
    $result = mysql_query($sql,$link) or die('Query failed: ' . mysql_error());
    $minlat = 99.0;
    $maxlat = -99.0;
    $minlong = 99.0;
    $maxlong = -99.0;
    $prefix = 'ssr';
    $first = 1;

    echo "var sroute = new GPolyline(["; 
    while($row = mysql_fetch_array($result))
    {
        $sr = 1;
        $lat = $row["${prefix}LatDecimal"];
        $long = $row["${prefix}LongDecimal"];
        if($long<$minlong) $minlong = $long;
        if($long>$maxlong) $maxlong = $long;
        if($lat<$minlat) $minlat = $lat;
        if($lat>$maxlat) $maxlat = $lat;
        if ($first)
        {
            $first = 0;
        }
        else
        {
            echo ",\n";
        }
        echo "new GLatLng($lat,$long)";
    }
    echo "],\"#333333\", 2, 1);\n";

    $longdiff = $maxlong-$minlong;
    $clong = $minlong + ($longdiff)/2;
    $clat = $minlat + ($maxlat-$minlat)/2;
    if($_REQUEST['embed']=='1') 
      $zoom = 11;
    else
      $zoom = 12;
    if($longdiff > 0.31)
      $zoom--;
}

echo "map.setCenter(new GLatLng($clat, $clong), $zoom);\n";
echo "map.addOverlay(polyline);\n\n";
if ($sr > 0)
{
    echo "map.addOverlay(sroute);\n\n";
}


if ($tasPk > 0)
{
    $sql = "SELECT T.tawRadius, W.* FROM tblTaskWaypoint T, tblRegionWaypoint W where T.tasPk=$tasPk and W.rwpPk=T.rwpPk order by T.tawNumber";
    $result = mysql_query($sql,$link) or die('Query failed: ' . mysql_error());
    $prefix='rwp';
    $first = 1;
    while($row = mysql_fetch_array($result))
    {
        $clat = $row["${prefix}LatDecimal"];
        $clong = $row["${prefix}LongDecimal"];
        $cname = $row["${prefix}Name"];
        $crad = $row["tawRadius"];

        // An ELabel with all optional parameters in use 
        echo "var pos = new GLatLng($clat,$clong);\n";
        echo "var label = new ELabel(pos, \"$cname\", \"waypoint\", new GSize(-40,0), 60);\n";
        echo "map.addOverlay(label);\n";

        // add a radius circle
        echo "var sz = GSizeFromMeters(map, pos, $crad*2,$crad*2);\n";
        echo "map.addOverlay(new EInsert(pos, \"circle.png\", sz, $zoom));\n";
        #echo "map.addOverlay(new EInsert(pos, \"circle.png\", sz, 13));\n";
    }
}

?>
    }
}

    //]]>
</script>
</head>
<body style="background: #FAFAFC !important;" onload="load()" onunload="GUnload()">
<? if($_REQUEST['embed']<>'1') { ?>
<div style="position: fixed; top: 10px; left: 0px; width: 100%; text-align: right;">
<a style="margin: 10px; border-bottom: none;" title="Volver a la página de la manga" href="/airscore/task_result_pb.php?comPk=<?= $comPk ?>&tasPk=<?= $tasPk ?>"><img src="/airscore/images/logo.png"/></a>
</div>
<? } ?>
  <div id="map" style="position: absolute; top: 0px; left: 0px; width: 100%; height: 100%"></div>
<?php
mysql_close($link);
?>
</body>
</html>

