<?php
require_once 'authorisation.php';

function printhd($title)
{
echo "
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
  <title>$title</title>
  <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\" />
  <meta name=\"author\" content=\"highcloud.net\" />
  <meta name=\"description\" content=\"Printable highcloud web page\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"printer.css\" media=\"screen\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"printer.css\" media=\"print\" />
</head>
<body>
";
}
function hchead()
{
echo "
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
  <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\" />
  <script src=\"javascript/jquery.min.js\" type=\"text/javascript\"></script>
";
}
function hchead_cont($title)
{
echo "
  <title>$title</title>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"styles/style.css\" media=\"screen\" />
</head>
<body>
";
}
function hcheader($title,$active,$titler)
{
  hchead();
  hchead_cont($title);
  hcheadbar($title,$active,$titler);
}
function hcheadbar($title,$active,$titler)
{
    if (!$comPk) {$comPk = 1;}
    $regPk=reqival('regPk');
  echo '
<header>
    <h1>II Open de Ala Delta de Pedro Bernardo</h1>
    <h2>Del miércoles 1 al sábado 4 de Mayo de 2013</h2>
    <a href="http://opendepb.com/presentacion.html" title="Presentación del Open"><img class="logo" src="images/logo.png"/></a>
     <nav>
      <ul>
        <li><a href="http://localhost/open">Noticias</a></li>
        <li><a href="presentacion.html">Presentación</a></li>
        <li><a href="reglamento.html">Reglamento</a></li>
        <li><a href="pilotos_2012.html">Pilotos</a></li>
        <li><a href="clasificacion_2012.html">Clasificación</a></li>
        <li class="active"><a href="/open/tietar_league.html">Tietar League</a></li>
        <li><a href="balizas.html">Balizas/Mapa</a></li>
        <li><a href="patrocinadores.html">Patrocinan</a></li>
        <li><a href="http://localhost/leonardoxc/index.php?name=leonardo&op=list_flights&sortOrder=FLIGHT_POINTS">LeonardoXC</a></li>
        <li><a href="archivo.html">Archivo</a></li>
    ';
    if ($regPk > 0) {
      echo "<li><a href=\"http://highcloud.net/xc/waypoint_map.php?regPk=$regPk\" title=\"Waypoints\"" . $clarr[3] . ">Waypoints</a></li>\n";
    };
echo '
      </ul>
    </nav>
</header>
<div class="article">
<article role="main">
<section>
';
}
function hcimage($link,$comPk)
{
    $image = "images/pilots.jpg";
    if (0+$comPk > 0)
    {
        $sql = "select comClass from tblCompetition where comPk=$comPk";
        $result = mysql_query($sql,$link);
        if ($row = mysql_fetch_array($result))
        {
            $comClass = $row['comClass'];
            if ($comClass != 'PG')
            {
                $image = "images/pilots_hg.jpg";
            }
        }
    }
    echo "<div id=\"image\"><img src=\"$image\" alt=\"Pilots Flying\"/></div>";
}
function hcsidebar($link)
{
echo "
    <div id=\"image\"><img src=\"images/pilots.jpg\" alt=\"Pilots Flying\"/></div>
    <div id=\"sideBar\">
      <h1><span>Longest 10</span></h1>
      <div id=\"comments\"><ol>";
$count = 1;
$sql = "SELECT T.*, P.* FROM tblTrack T, tblPilot P, tblComTaskTrack CTT where T.pilPk=P.pilPk and CTT.traPk=T.traPk order by T.traLength desc limit 10";
$result = mysql_query($sql,$link);
while($row = mysql_fetch_array($result))
{
    $id = $row['traPk'];
    $dist = round($row['traLength']/1000,2);
    $name = $row['pilFirstName'];
    echo "<span class=\"author\"><a href=\"tracklog_map.php?trackid=$id&comPk=5\"><li> $dist kms ($name)</a></span>\n";

    $count++;
}
echo "</ol>";
echo "
        <img src=\"images/comment_bg.gif\" alt=\"comment bottom\"/>
      </div>
      <h1><span>Recent 10</span></h1><ol>";
$count = 1;
$sql = "SELECT T.*, P.* FROM tblTrack T, tblPilot P, tblComTaskTrack CTT where T.pilPk=P.pilPk and CTT.traPk=T.traPk order by T.traDate desc limit 10";
$result = mysql_query($sql,$link);
while($row = mysql_fetch_array($result))
{
    $id = $row['traPk'];
    $dist = round($row['traLength']/1000,2);
    $date = $row['traDate'];
    $name = $row['pilFirstName'];
    echo "<a href=\"tracklog_map.php?trackid=$id&comPk=5\"><li> $dist kms ($name)</a><br>\n";

    $count++;
}

echo "</ol>";
echo "<img src=\"images/comment_bg.gif\" alt=\"comment bottom\"/>
    </div>\n";
}
function hcregion($link)
{
    echo "<h1><span>Tracks by Region</span></h1>\n";
    $sql = "select R.*, RW.* from tblRegion R, tblRegionWaypoint RW where R.regCentre=RW.rwpPk and R.regDescription not like '%test%'";
    $result = mysql_query($sql,$link);
    $regions = array();
    while($row = mysql_fetch_array($result))
    {
        $regPk=$row['regPk'];
        #$regions[] = "<a href=\"regional.php?${piln}regPk=$regPk\">" . $row['regDescription'] . "</a>";
        $regions[] = "<a href=\"regional.php?regPk=$regPk\">" . $row['regDescription'] . "</a>";
    }
    echo fnl($regions);
    //echo "<img src=\"images/comment_bg.gif\" alt=\"comment bottom\"/></div>\n";
}
function hcopencomps($link)
{
    echo "<h1><span>Open Competitions</span></h1>";
    $sql = "select * from tblCompetition where comName not like '%test%' and comDateTo > date_sub(now(), interval 1 day) order by comDateTo";
    $result = mysql_query($sql,$link);
    $comps = array();
    while($row = mysql_fetch_array($result))
    {
        // FIX: if not finished & no tracks then submit_track page ..
        // FIX: if finished no tracks don't list!
        $cpk = $row['comPk'];
        $comps[] = "<a href=\"comp_result.php?comPk=$cpk\">" . $row['comName'] . "</a>";
    }
    echo fnl($comps);
}
function hcclosedcomps($link)
{
    echo "<h1><span>Closed Competitions</span></h1>";
    $sql = "select * from tblCompetition where comName not like '%test%' and comDateTo < date_sub(now(), interval 1 day) order by comDateTo desc limit 15";
    $result = mysql_query($sql,$link);
    $comps = array();
    while($row = mysql_fetch_array($result))
    {
        // FIX: if not finished & no tracks then submit_track page ..
        // FIX: if finished no tracks don't list!
        $cpk = $row['comPk'];
        if ($row['comType'] == 'Route')
        {
            $comps[] = "<a href=\"compview.php?comPk=$cpk\">" . $row['comName'] . "</a>";
        }
        else
        {
            $comps[] = "<a href=\"comp_result.php?comPk=$cpk\">" . $row['comName'] . "</a>";
        }
    }
    echo fnl($comps);
}
function hcfooter()
{
  echo '
<script src="javascript/photo_sticker.js" type="text/javascript"></script>
        </section>
      </article>
    </div>
<footer>
      <nav>
      <ul>
        <li><a href="http://localhost/open">Noticias</a></li>
        <li><a href="presentacion.html">Presentación</a></li>
        <li><a href="reglamento.html">Reglamento</a></li>
        <li><a href="pilotos_2012.html">Pilotos</a></li>
        <li class="active"><a href="clasificacion_2012.html">Clasificación</a></li>
        <li><a href="balizas.html">Balizas</a></li>
        <li><a href="mapa.html">Mapa</a></li>
        <li><a href="alojamientos.html">Alojamiento</a></li>
        <li><a href="patrocinadores.html">Patrocinan</a></li>
        <li><a href="archivo.html">Archivo</a></li>
      </ul>
    </nav>

  <p>Sitio hecho con <a href="https://github.com/mojombo/jekyll" title="Para la gestión de sus contenidos">Jekyll</a>, <a href="http://rubyonrails.org/" title="Para guardar y servir las inscripciones">RubyOnRails</a>, AirScore, LeonardoXC, <a href="http://jquery.com/" title="Para tratar con JavaScript">jQuery</a>, <a href="http://www.jstween.org" title="Para la animación sorpresa">Tween</a> y <a href="http://disqus.com/" title="Para los comentarios">Disqus</a></p>
</footer>
</body>
</html>
';
}
function hcmapjs()
{
    //echo '<script src="http://maps.google.com/maps/api/js?v=3&sensor=false&key=ABQIAAAAPyz1XxP2rM79ZhAH2EmgwxQ1ylNcivz9k-2ubmbv1YwdT5nh3RQJsyJo_kuVL1UAWoydxDkwo_zsKw" type="text/javascript"></script>';
    echo '<script src="http://maps.googleapis.com/maps/api/js?sensor=false" type="text/javascript"></script>';
    echo "\n";
    echo '<script src="elabelv3.js" type="text/javascript"></script>';
    echo "\n";
    echo '<script src="einsertv3.js" type="text/javascript"></script>';
    echo "\n";
}
function hccss()
{
    echo '<link rel="stylesheet" type="text/css" href="green800.css" media="screen, print" />';
    echo "\n";
}
function hcscripts($arr)
{
    foreach ($arr as $ele)
    {
        echo "<script src=\"$ele\" type=\"text/javascript\" charset=\"UTF-8\"></script>\n";
    }
}
?>
