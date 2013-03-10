<?php
function hcheader($title,$active)
{
echo "
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<!--
 ____________________________________________________________
|                                                            |
|    DESIGN + Pat Heard { http://fullahead.org }             |
|      DATE + 2005.11.30                                     |
| COPYRIGHT + Free use if this notice is left in place       |
|____________________________________________________________|
-->
<head>
  <title>$title</title>
  <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\" />
  <meta name=\"author\" content=\"fullahead.org\" />
  <meta name=\"keywords\" content=\"Open Web Design, OWD, Free Web Template, Greenery, Fullahead\" />
  <meta name=\"description\" content=\"A free web template designed by Fullahead.org and hosted on OpenWebDesign.org\" />
  <meta name=\"robots\" content=\"index, follow, noarchive\" />
  <meta name=\"googlebot\" content=\"noarchive\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"green800.css\" media=\"screen, print\" />
</head>
<body>
";
    hcheadbar($title,$active);
}
function hcheadbar($title,$active)
{
echo "
  <div id=\"header\">
    <div id=\"menu\">
      <ul>\n
";
    $clarr = array     
    (
        '', '', '', '', '', '', '', ''
    );
    $clarr[$active] = ' class="active"';
    $comPk=intval($_REQUEST['comPk']);
    echo "<li><a href=\"index.php?comPk=$comPk\" title=\"About\"" . $clarr[0]. ">About</a></li>\n";
    if (!$comPk)
    {
        $comPk = 1;
    }
    echo "<li><a href=\"submit_track.php?comPk=$comPk\" title=\"Submit\"" . $clarr[1] . ">Submit</a></li>\n";
    echo "<li><a href=\"comp_result.php?comPk=$comPk\" title=\"Results\"" . $clarr[2] . ">Results</a></li>\n";
    $regPk=intval($_REQUEST['regPk']);
    if ($regPk > 0)
    {
    echo "<li><a href=\"http://highcloud.net/xc/waypoint_map.php?regPk=$regPk\" title=\"Waypoints\"" . $clarr[3] . ">Waypoints</a></li>\n";
    }
    //echo "<li><a href=\"track.php\" title=\"submit tracks\"" . $clarr[4] . ">Tracks</a></li>";
echo "</ul>\n
      <div id=\"title\">
        <h1>$title</h1>\n
      </div>
    </div>
  </div>
";
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
$sql = "SELECT T.*, P.* FROM tblTrack T, tblPilot P, tblComTaskTrack CTT where T.pilPk=P.pilPk and CTT.traPk=T.traPk order by T.traStart desc limit 10";
$result = mysql_query($sql,$link);
while($row = mysql_fetch_array($result))
{
    $id = $row['traPk'];
    $dist = round($row['traLength']/1000,2);
    //$date = $row['traDate'];
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
        $regions[] = "<a href=\"regional.php?${piln}regPk=$regPk\">" . $row['regDescription'] . "</a>";
    }
    echo fnl($regions);
    //echo "<img src=\"images/comment_bg.gif\" alt=\"comment bottom\"/></div>\n";
}
function hcopencomps($link)
{
    echo "<h1><span>Open Competitions</span></h1>";
    $sql = "select * from tblCompetition where comName not like '%test%' and comDateTo > now() order by comDateTo";
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
    $sql = "select * from tblCompetition where comName not like '%test%' and comDateTo <= now() order by comDateTo desc";
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
    echo "<div id=\"footer\">
      <a href=\"http://fullahead.org\" title=\"designed by fullahead.org\" class=\"fullAhead\"></a>
      <p>Valid <a href=\"http://validator.w3.org/check?uri=referer\" title=\"validate XHTML\">XHTML</a> &amp; <a href=\"http://jigsaw.w3.org/css-validator\" title=\"validate CSS\">CSS</a></p>
    </div>
  </div>\n";
}
function hcmapjs()
{
    echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=%GMAPKEY%" type="text/javascript"></script>';
    echo "\n";
    echo '<script src="elabel.js" type="text/javascript"></script>';
    echo "\n";
    echo '<script src="einsert.js" type="text/javascript"></script>';
    echo "\n";
}
function hccss()
{
    echo '<link rel="stylesheet" type="text/css" href="green800.css" media="screen, print" />';
    echo "\n";
}
function hchead()
{
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:v=\"urn:schemas-microsoft-com:vml\">\n";
    echo "<head>\n";
    echo '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>';
    echo "\n";
}
function hcscripts($arr)
{
    foreach ($arr as $ele)
    {
        echo "<script src=\"$ele\" type=\"text/javascript\"></script>\n";
    }
}
?>
