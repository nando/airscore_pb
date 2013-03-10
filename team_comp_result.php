<?php
function taskcmp($a, $b)
{
    if ($a['tname'] == $b['tname']) 
    {
        return 0;
    }
    return ($a['tname'] < $b['tname']) ? -1 : 1;
}
function team_comp_result($comPk, $how, $param)
{
    $sql = "select TK.*,TR.*,P.* from tblTeamResult TR, tblTask TK, tblTeam P, tblCompetition C where C.comPk=$comPk and TR.tasPk=TK.tasPk and TK.comPk=C.comPk and P.teaPk=TR.teaPk order by P.teaPk, TK.tasPk";
    $result = mysql_query($sql) or die('Task result query failed: ' . mysql_error());
    $results = array();
    while ($row = mysql_fetch_array($result))
    {
        $score = round($row['terScore']);
        $validity = $row['tasQuality'] * 1000;
        $pilPk = $row['teaPk'];
        $tasName = $row['tasName'];
    
        if (!$results[$pilPk])
        {
            $results[$pilPk] = array();
            $results[$pilPk]['name'] = $row['teaName'];
        }
        //echo "pilPk=$pilPk tasname=$tasName, result=$score<br>\n";
        $perf = 0;
        if ($how == 'ftv') 
        {
            $perf = 0;
            if ($validity > 0)
            {
                $perf = round($score / $validity, 3) * 1000;
            }
        }
        else
        {
            $perf = round($score, 0);
        }
        $results[$pilPk]["${perf}${tasName}"] = array('score' => $score, 'validity' => $validity, 'tname' => $tasName);
    }

    // Do the scoring totals (FTV/X or Y tasks etc)
    $sorted = array();
    foreach ($results as $pil => $arr)
    {
        krsort($arr, SORT_NUMERIC);

        $pilscore = 0;
        if ($how != 'ftv')
        {
            # Max rounds scoring
            $count = 0;
            foreach ($arr as $perf => $taskresult)
            {
                if ($perf == 'name') 
                {
                    continue;
                }
                if ($count < $param)
                {
                    $arr[$perf]['perc'] = 100;
                    $pilscore = $pilscore + $taskresult['score'];
                }
                else
                {
                    $arr[$perf]['perc'] = 0;
                }
                $count++;
                
            }
        }
        else
        {
            # FTV scoring
            $pilvalid = 0;
            foreach ($arr as $perf => $taskresult)
            {
                if ($perf == 'name') 
                {
                    continue;
                }

                //echo "pil=$pil perf=$perf valid=", $taskresult['validity'], " score=", $taskresult['score'], "<br>";
                if ($pilvalid < $param)
                {
                    $gap = $param - $pilvalid;
                    $perc = 0;
                    if ($taskresult['validity'] > 0)
                    {
                        $perc = $gap / $taskresult['validity'];
                    }
                    if ($perc > 1)
                    {
                        $perc = 1;
                    }
                    $pilvalid = $pilvalid + $taskresult['validity'] * $perc;
                    $pilscore = $pilscore + $taskresult['score'] * $perc;
                    $arr[$perf]['perc'] = $perc * 100;
                }
            }   
        }
        // resort arr by task?
        uasort($arr, "taskcmp");
        #echo "pil=$pil pilscore=$pilscore<br>";
        foreach ($arr as $key => $res)
        {
            if ($key != 'name')
            {
                $arr[$res['tname']] = $res;
                unset($arr[$key]);
            }
        }
        $pilscore = round($pilscore,0);
        $sorted["${pilscore}!${pil}"] = $arr;
    }

    krsort($sorted, SORT_NUMERIC);
    return $sorted;
}

function team_agg_result($comPk, $teamsize)
{
    $query = "select TM.teaPk,TK.tasPk,TK.tasName,TM.teaName,P.pilLastName,P.pilFirstName,P.pilPk,TR.tarScore*TP.tepModifier as tepscore from tblTaskResult TR, tblTask TK, tblTrack K, tblPilot P, tblTeam TM, tblTeamPilot TP, tblCompetition C where TP.teaPk=TM.teaPk and P.pilPk=TP.pilPk and C.comPk=TK.comPk and K.traPk=TR.traPk and K.pilPk=P.pilPk and TR.tasPk=TK.tasPk and TM.comPk=C.comPk and C.comPk=$comPk order by TM.teaPk,TK.tasPk,TR.tarScore*TP.tepModifier desc";
    $result = mysql_query($query) or die('Team aggregate query failed: ' . mysql_error());
    $row = mysql_fetch_array($result);
    $htable = array();
    $hres = array();
    $sorted = array();
    $teaPk = 0;
    $tasPk = 0;
    $tastot = 0;
    $total = 0;
    $size = 0;
    while ($row)
    {
        //$tasName = $row['tasName'];
        if ($tasPk != $row['tasPk'])
        {
            if ($size != 0)
            {
                $arr["${tasName}"] = array('score' => round($tastotal,0), 'perc' => 100, 'tname' => $tasName);
            }
            $tasName = $row['tasName'];
            $size = 0;
            $tastotal = 0;
            $tasPk = $row['tasPk'];
            //$arr = array();
        }
        if ($teaPk != $row['teaPk'])
        {
            if ($teaPk == 0)
            {
                $teaPk = $row['teaPk'];
                $tasPk = $tow['tasPk'];
                $arr = array();
                $arr['name'] = $row['teaName'];
            }
            else
            {
                // wrap up last one
                $total = round($total,0);
                $sorted["${total}!${teaPk}"] = $arr;
                $tastotal = 0;
                $total = 0;
                $size = 0;
                $arr = array();
                $arr['name'] = $row['teaName'];
                $teaPk = $row['teaPk'];
            }
        }

        if ($size < $teamsize)
        {
            $total = round($total + $row['tepscore'],2);
            $tastotal = round($tastotal + $row['tepscore'],2);
            $size = $size + 1;
        }
        $row = mysql_fetch_array($result);
    }

    // wrap up last one
    $total = round($total,0);
    $arr["${tasName}"] = array('score' => round($tastotal,0), 'perc' => 100, 'tname' => $tasName);
    $sorted["${total}!${teaPk}"] = $arr;

    krsort($sorted, SORT_NUMERIC);
    return $sorted;
}

require 'authorisation.php';
require 'format.php';
require 'hc.php';

$comPk = intval($_REQUEST['comPk']);
$start = reqival($_REQUEST['start']);
if ($start < 0)
{
    $start = 0;
}
$link = db_connect();
$title = 'highcloud.net';

$query = "SELECT T.*,F.* FROM tblCompetition T left outer join tblFormula F on F.comPk=T.comPk where T.comPk=$comPk";
$result = mysql_query($query) or die('Comp query failed: ' . mysql_error());
$row = mysql_fetch_array($result);
if ($row)
{
    $comName = $row['comName'];
    $title = $row['comName'];
    $comDateFrom = substr($row['comDateFrom'],0,10);
    $comDateTo = substr($row['comDateTo'],0,10);
    #$comPk = $row['comPk'];
    $comOverall = $row['comOverallScore'];
    $comOverallParam = $row['comOverallParam'];
    $comDirector = $row['comMeetDirName'];
    $comLocation = $row['comLocation'];
    $comFormula = $row['forClass'] . ' ' . $row['forVersion'];
    $comSanction = $row['comSanction'];
    $comOverall = $row['comOverallScore'];
    $comTeamScoring = $row['comTeamScoring'];
    $comTeamSize = $row['comTeamSize'];
    $comCode = $row['comCode'];
    $comType = $row['comType'];
    $forNomGoal = $row['forNomGoal'];
    $forMinDistance = $row['forMinDistance'];
    $forNomDistance = $row['forNomDistance'];
    $forNomTime = $row['forNomTime'];
}

$embed = reqsval($_REQUEST['embed']);
if ($embed == '')
{
    hcheader($title, 2, "$comDateFrom - $comDateTo");

    echo "<div id=\"content\">";
    echo "<div id=\"text\">";
}
else
{
    echo "<link HREF=\"$embed\" REL=\"stylesheet\" TYPE=\"text/css\">";
    echo "</head><body>";
}


//echo "<h1>Details</h1>";
// Determine scoring params / details ..

$tasTotal = 0;
$query = "select count(*) from tblTask where comPk=$comPk";
$result = mysql_query($query); // or die('Task total failed: ' . mysql_error());
if ($result)
{
    $tasTotal = mysql_result($result, 0, 0);
}
if ($comOverall == 'all')
{
    # total # of tasks
    $comOverallParam = $tasTotal;
    $overstr = "All rounds";
}
else if ($comOverall == 'round')
{
    $overstr = "$comOverallParam rounds";
}
else if ($comOverall == 'round-perc')
{
    $comOverallParam = round($tasTotal * $comOverallParam / 100, 0);
    $overstr = "$comOverallParam rounds";
}
else if ($comOverall == 'ftv')
{
    $sql = "select sum(tasQuality) as totValidity from tblTask where comPk=$comPk";
    $result = mysql_query($sql) or die('Task validity query failed: ' . mysql_error());
    $totalvalidity = round(mysql_result($result, 0, 0) * $comOverallParam * 10,0);
    $overstr = "FTV $comOverallParam% ($totalvalidity pts)";
    $comOverallParam = $totalvalidity;
}

//echo ftable($detarr, '', '', '');

echo "<h1>Results</h1>";

echo "<table border=\"2\" cellpadding=\"1\" alternate-colours=\"yes\" align=\"center\">";
echo "<tr class=\"h\"><td><b>Res</b></td><td><b>Team</b></td><td><b>Total</b></td>";

# find each task details
$alltasks = array();
$taskinfo = array();
$sorted = array();
if ($tasTotal > 0)
{
    $query = "select T.* from tblTask T where T.comPk=$comPk order by T.tasDate";
    $result = mysql_query($query) or die('Task query failed: ' . mysql_error());
    while ($row = mysql_fetch_array($result))
    {
        $alltasks[] = $row['tasName'];
        $taskinfo[] = $row;
    }

    if ($comTeamScoring == "aggregate")
    {
        $sorted = team_agg_result($comPk, $comTeamSize);
    }
    else
    {
        $sorted = team_comp_result($comPk, $comOverall, $comOverallParam);
    }

    foreach ($taskinfo as $row)
    {
        $tasName = $row['tasName'];
        $tasPk = $row['tasPk'];
        $tasDate = substr($row['tasDate'],0,10);
        echo "<td><b><a href=\"team_task_result.php?comPk=$comPk&tasPk=$tasPk\">$tasName</a></b></td>";
    }
    echo "</tr>\n";
    echo "<tr class=\"h\"><td></td><td></td><td></td>";
    foreach ($taskinfo as $row)
    {
        $tasPk = $row['tasPk'];
        echo "<td><a href=\"route_map.php?comPk=$comPk&tasPk=$tasPk\">Map</a></td>";
    }
    echo "</tr>\n";
    $count = 1;
    foreach ($sorted as $pil => $arr)
    {
        if ($count % 2)
        {
            $class = "d";
        }
        else
        {
            $class = "l";
        }
        echo "<tr class=\"$class\"><td>${count}</td><td>";
        echo $arr['name'] . "</td>";
        $tot = 0 + $pil;
        echo "<td><b>$tot</b></td>";
        foreach ($alltasks as $num => $name)
        {
            $score = $arr[$name]['score'];
            $perc = round($arr[$name]['perc'], 0);
            if (!$score)
            {
                $score = 0;
            }
            if ($perc == 100)
            {
                echo "<td><b>$score</b></td>";
            }
            else if ($perc > 0)
            {
                echo "<td><b>$score</b> $perc%</td>";
            }
            else
            {
                echo "<td>$score</td>";
            }
        }
        echo "</tr>\n";
        $count++;
    }
}
elseif ($comType != 'RACE')
{
    $top = 25;
    if (!$comOverallParam)
    {
        $comOverallParam = 4;
    }
    $restrict = '';
    if ($comPk > 1)
    {
        $restrict = " and CTT.comPk=$comPk";
    }
    //FIX:$sorted = olc_result($link,$comOverallParam, $restrict);
    $size = sizeof($sorted);

    #echo "<tr class=\"h\"><td><b>Place</b></td><td><b>Team</b></td><td><b>Total</b></td><td><b>Task 1</b1></td><td><b>Task 2</b></td><td><b>Task 3</b></td><td><b>Task 4</b></td></tr>\n";
    $count = $start+1;
    $sorted = array_slice($sorted,$start,$top);
    foreach ($sorted as $total => $row)
    {
        if ($count % 2)
        {
            echo "<tr class=\"d\">";
        }
        else
        {
            echo "<tr class=\"l\">";
        }
        $name = $row['firstname'] . " " . $row['lastname'];
        $key = $row['pilpk'];
        $total = round($total/1000,2);
        echo "<td>$count</td><td><a href=\"index.php?pil=$key\">$name</a></td><td>$total</td>\n";
        foreach ($row['tasks'] as $task)
        {
            $score = round($task['traScore']/1000,1);
            $id = $task['traPk'];
            echo "<td><a href=\"tracklog_map.php?comPk=$comPk&trackid=$id\">$score</a></td>";
        }
        $count++;
        if ($count >= $top + $start) 
        {
            break;
        }
        echo "</tr>";
    }
    echo "</table>";
    
    if ($count == 1)
    {
        echo "<b>No tracks submitted for $comName yet.</b>\n";
    }
    else 
    {
        if ($start >= 0)
        {
            echo "<b class=\"left\"><a href=\"team_comp_result.php?comPk=$comPk&start=" . ($start-$top) . "\">Prev 25</a></b>\n";
        }
        if ($count < $size)
        {
            echo "<b class=\"right\"><a href=\"team_comp_result.php?comPk=$comPk&start=" . ($count) . "\">Next 25</a></b>\n";
        }
    }

}

echo "</table>";
if ($comOverall == 'ftv')
{
    echo "1. Click <a href=\"ftv.php?comPk=$comPk\">here</a> for an explanation of FTV<br>";
    echo "2. Scores in bold 100%, or indicated %, other scores not counted<br>";
}
if ($embed == '')
{
    // Only emit image if results table is "narrow"
    echo "</div>";
    hcimage($link,$comPk);
    echo "<div id=\"sideBar\">";

    echo "<h1>Comp Details</h1>";
    $detarr = array(
        array("<b>Location</b> ", "<i>$comLocation</i>"),
        array ("<b>Director</b> ", "<i>$comDirector</i>"),
        array("<i>$comDateFrom</i>", "<i>$comDateTo</i>")
    );

    if ($comType == 'RACE')
    {
        $scarr = array();
        $scarr[] = array("<b>Type</b> ", "<i>$comType ($comFormula)</i>");
        $scarr[] = array("<b>Scoring</b> ","<i>$overstr</i>");
        $scarr[] = array("<b>Min Dist</b>", "<i>$forMinDistance kms</i>");
        $scarr[] = array("<b>Nom Dist</b>", "<i>$forNomDistance kms</i>");
        $scarr[] = array("<b>Nom Time</b>", "<i>$forNomTime mins</i>");
        $scarr[] = array("<b>Nom Goal</b>", "<i>$forNomGoal%</i>");
        echo ftable($detarr, 'border="0" cellpadding="0" width="185"', '', array('', 'align="right"'));
        echo "<h1>Scoring Parameters</h1>";
        echo ftable($scarr, 'border="0" cellpadding="0" width="185"', '', array('', 'align="right"'));
    }
    else
    {
        $detarr[] = array("<b>Type</b> ", "<i>$comType ($comFormula)</i>");
        $detarr[] = array("<b>Scoring</b> ","<i>$overstr</i>");
        echo ftable($detarr, 'border="0" cellpadding="0" width="185"', '', array('', 'align="right"'));
    }


    hcopencomps($link);
    hcclosedcomps($link);
    echo "</div>";
    hcfooter();
}
?>
</body>
</html>

