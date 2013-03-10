<?php
function fselect($name,$selected,$options,$extra='')
{
    $resarr = array();
    $resarr[] = "<select name=\"$name\" id=\"$name\"$extra>";
    foreach ($options as $key => $value)
    {
        if (is_int($key))
        {
            $key = $value;
        }
        if ($selected == $value)
        {
            $resarr[] = "<option value=\"$value\" selected>$key</option>";
        }
        else
        {
            $resarr[] = "<option value=\"$value\">$key</option>";
        }
    }
    $resarr[] = "</select>";
    return implode("\n", $resarr);
}
function fwaypoint($link,$tasPk,$name,$selected)
{
    $query="select distinct RW.* from tblTask T, tblRegion R, tblRegionWaypoint RW where T.tasPk=$tasPk and RW.regPk=R.regPk and R.regPk=T.regPk order by RW.rwpName";
    $result = mysql_query($query) or die('Waypoint select failed: ' . mysql_error());
    $waypoints = array();
    while($row = mysql_fetch_array($result))
    {
        $rwpPk = $row['rwpPk'];
        $rname = $row['rwpName'];
        $waypoints[$rname] = $rwpPk;
    }
    //ksort($waypoints);
    return fselect($name,$selected,$waypoints);
}
function frow($cellarr, $cdec)
{
    $count = 0;
    foreach ($cellarr as $cell)
    {
        if (is_array($cdec))
        {
            $tdc = $cdec[$count];
            $count = ($count + 1) % count($cdec);
        }
        else
        {
            $tdc = $cdec;
        }

        if ($tdc != '')
        {
            $allcols[] = "<td $tdc>" . $cell;
        }
        else
        {
            $allcols[] = "<td>" . $cell;
        }
    }
    return implode("</td>\n", $allcols) . "</td>\n";
}
function ftable($rowarr, $tdec, $rdec, $cdec)
{
    $allrows = array();
    foreach ($rowarr as $row)
    {
        $allrows[] = frow($row, $cdec);
    }

    if ($rdec != '')
    {
        $count = 0;
        if (is_array($rdec))
        {
            $resrows = array();
            foreach ($allrows as $row)
            {
                $rsel = $rdec[$count];
                $count = ($count + 1) % count($rdec);
                $resrows[] = "<tr $rsel>" . $row . "</tr>";
            }
            $result = implode("\n", $resrows);
        }
        else
        {
            $result = "<tr $rdec>" . implode("</tr><tr $rdec>", $allrows) . "</tr>\n";
        }
    }
    else
    {
        $result = "<tr>" . implode("</tr><tr>", $allrows) . "</tr>\n";
    }

    if ($tdec != '')
    {
        $result = "<table $tdec>" . $result . "</table>";
    }
    else
    {
        $result = "<table>" . $result . "</table>";
    }

    return $result;
}
function fb($str)
{
    return "<b>$str</b>";
}
function fib($type,$name,$value,$size)
{
    $type = "type=\"$type\"";
    $name = " name=\"$name\"";

    if ($value != '')
    {
        $value = " value=\"$value\"";
    }
    
    if ($size != '')
    {
        $size = " size=\"$size\"";
    }

    return "<input $type$name$value$size>";
}
function fin($name,$value,$size)
{
    return fib('text',$name,$value,$size);
}
function fis($name,$value,$size)
{
    return fib('submit',$name,$value,$size);
}
function fbut($type,$name,$value,$text)
{
    return "<button type=\"$type\" name=\"$name\" value=\"$value\">$text</button>";
}
function flist($list)
{
    $result = '';
    foreach ($list as $item)
    {
        $result = $result . "<li>$item<br>\n";
    }
    return $result;
}
function fol($list)
{
    $result = "<ol>\n";
    $result = $result . flist($list);
    $result = $result . "</ol>\n";
    return $result;
}
function ful($list)
{
    $result = "<ul>\n";
    $result = $result . flist($list);
    $result = $result . "</ul>\n";
    return $result;
}
function fnl($list)
{
    $result = '';
    foreach ($list as $item)
    {
        $result = $result . "$item<br>\n";
    }
    return $result;
}
function ftime($sec)
{
    if ($sec == '')
    {
        return '';
    }
    $hh = floor($sec / 3600);
    $mm = floor(($sec % 3600) / 60);
    $ss = $sec % 60;
    if ($hh > 0)
    {   
        $timeinair = "${hh}h${mm}m{$ss}s";
    }
    else
    {   
        $timeinair = "${mm}m{$ss}s";
    }
    return $timeinair;
}
?>
