<?php
if (isset($_POST['item']) && intval($_POST['item'] > 0))
    generate_sql($_POST['item']);
function url_get_contents ($item) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
	$url = "http://www.wowhead.com/item=".$item."&xml";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
function generate_sql($item = "19019")
{
    $result = url_get_contents($item);
    $dmg = explode(" - ",get_string_betweenTWO($result, "<!--dmg-->", "Damage"));

    $i = 1;
    $j = 1;
    $item = array(
        "entry" => $item,
        "class" => get_next_char($result, "class id=\"", 2),
        "subclass" => get_next_char($result, "subclass id=\"", 2),
        "name" => "'".addslashes(getstringbetween(get_string_between($result, "name"), "[", "]"))."'",
        "displayid" => get_next_char($result, "icon displayId=\"", 6),
        "Quality" => get_next_char($result, "quality id=\""),
        "SellPrice" => get_sellprice($result),
        "InventoryType" => get_next_char($result, "inventorySlot id=\"", 2),
        "ItemLevel" => get_string_between($result, "level"),
        "RequiredLevel" => get_next_char($result, "<!--rlvl-->", 3),
        "maxcount" => check_unique($result),
        "dmg_min1" => 0,
        "dmg_max1" => 0,
        "delay" => 0,
        "dmg_type1" => 0,
        "bonding" => check_bonding($result),
    );
    if ($speed = get_next_char($result, "<!--spd-->", 4))
        $item["delay"] = intval($speed) * 10;

    if (count($dmg) > 1){
        $item["dmg_min1"] = $dmg[0];
        $item["dmg_max1"] = $dmg[1];
    }
    foreach (get_resists($result) as $key => $resist)
       $item[$resist['res']] = intval(strtoINT($resist['res_val']));
    foreach (get_stats($result) as $key => $stat) {
        $str = "stat_type".$j;
        $item[$str] = intval(strtoINT($stat['stat_type']));
        $val = "stat_value".$j;
        $item[$val] = intval(strtoINT($stat['stat_value']));
        $j++;
    }
    $item['StatsCount'] = $j-1;
    ;
    echo '<div class="item">';
    echo '<img class="icon" src="http://wow.zamimg.com/images/wow/icons/large/'.get_icon($result, $item['displayid']).'.jpg" />';
    echo '<span class="name" style="color:#'.quality_color($item['Quality']).'">'.get_name($result).'</span>';
    echo '<span class="level">Item Level '.$item['ItemLevel'].'</span>';
    echo '<span class="left-padded">'.get_bind_string($item['bonding']).'</span>';
    if ($item['dmg_min1'] > 0)
        echo '<span class="left-padded">Damage '.$item['dmg_min1'].' - '.$item['dmg_max1'].'</span>';
    if ($item['delay'] > 0)
        echo '<span class="left-padded">Speed: '.$item['delay'].' ('.($item['delay'] / 1000).')</span>';
    echo '<span class="left-padded">Required Level: '.$item['RequiredLevel'].'</span>';
    echo '<span class="left-padded">Quality: '.$item['Quality'].'</span>';
    echo '<span class="left-padded">Class: '.$item['class'].'</span>';
    echo '<span class="left-padded">Subclass: '.$item['subclass'].'</span>';
    echo '<span class="left-padded">Unique: '.$item['maxcount'].'</span>';
    echo '<span class="left-padded">Inventory: '.$item['InventoryType'].'</span>';
    echo '<span class="left-padded">Display Id: '.$item['displayid'].'</span>';
    if ($item['SellPrice'] > 0)
        echo '<span class="left-padded">Sell Price: '.$item['SellPrice'].'</span>';
    echo "<BR><span class=\"left-padded\"><b>Item Resistances:</b></span>";
    foreach (get_resists($result) as $key => $resist)
        echo "<span class=\"left-padded\">".$resist['res'] . ": " . $resist['res_val'] . "</span>";
    echo "<BR><span class=\"left-padded\"><b>Item Stats:</b></span>";
    foreach (get_stats($result) as $stat) {
        echo "<span class=\"left-padded\">Stat Type ".$i." = ".$stat['stat_type']." Stat Value ". $i . " = ". $stat['stat_value']."</span>";
        $i++;
    }
    echo "</div>";

    $i = 1;
    $insert = "INSERT INTO `item_template`(";
    foreach($item as $key => $itm) {
        if ($i != count($item))
            $insert .= "`" . $key . "`, ";
        else
            $insert .= "`" . $key . "`) ";
        $i++;
    }
    $i = 1;
    $insert .= "VALUES (";
    foreach($item as $key => $itm) {
        if ($i != count($item))
            $insert .= $itm . ", ";
        else
            $insert .=  $itm.")";
        $i++;
    }
    $insert .= ";";


    $i = 1;
    $replace = "REPLACE INTO `item_template`(";
    foreach($item as $key => $itm) {
        if ($i != count($item))
            $replace .= "`" . $key . "`, ";
        else
            $replace .= "`" . $key . "`) ";
        $i++;
    }
    $i = 1;
    $replace .= "VALUES (";
    foreach($item as $key => $itm) {
        if ($i != count($item))
            $replace .= $itm . ", ";
        else
            $replace .=  $itm.")";
        $i++;
    }
    $replace .= ";";


    $i = 1;
    $update = "UPDATE `item_template` SET \n";
    foreach($item as $key => $itm) {
        if ($i != count($item))
            $update .= "`" . $key . "` = ".$itm.",\n";
        else
            $update .= "`" . $key . "` = ".$itm.") ";
        $i++;
    }
    $update .= ";";

    echo "<h1>INSERT QUERY</h1>";
    echo "<textarea>".$insert."</textarea>";
    echo "<h1>REPLACE QUERY</h1>";
    echo "<textarea>".$replace."</textarea>";
    echo "<h1>UPDATE QUERY</h1>";
    echo "<textarea>".$update."</textarea>";

    //$result = htmlspecialchars(get_string_between($result, "wowhead"));
    //echo '<BR><br><br>'.$result;

}
function get_name($result){
    $name = "Unknown Item";
    $getname = getstringbetween(get_string_between($result, "name"), "[", "]");
    if (strlen($getname)>1)
        $name = $getname;
    return $name;
}
function get_icon($str){
    $icon = "inv_misc_questionmark";
    $str = str_replace(substr($str, 0, strpos($str, 'inv')), "", $str);
    $str = substr($str, 0, strpos($str, "</icon>"));
    if (strlen($str) > 3)
        $icon = $str;
    return $icon;
}
function quality_color($quality){
    switch ($quality) {
        case 0:
            $qualitySTR = "9d9d9d";
            break;
        case 1:
            $qualitySTR = "ffffff";
            break;
        case 2:
            $qualitySTR = "1eff00";
            break;
        case 3:
            $qualitySTR = "0070dd";
            break;
        case 4:
            $qualitySTR = "a335ee";
            break;
        case 5:
            $qualitySTR = "ff8000";
            break;
        case 6:
            $qualitySTR = "ff8000";
            break;
        default:
            $qualitySTR = "DEB887";
            break;
    }
    return $qualitySTR;
}
function get_sellprice($str){
    $gold=0;$silver=0;$copper=0;
    $gold = strtoINT(get_string_betweenTWO($str, "<span class=\"moneygold\">", "</span>"));
    $silver = strtoINT(get_string_betweenTWO($str, "<span class=\"moneysilver\">", "</span>"));
    $copper = strtoINT(get_string_betweenTWO($str, "<span class=\"moneycopper\">", "</span>"));
    $sellprice = intval($copper) + (intval($silver) * 100) + (intval($gold) * 1000);
    return $sellprice;
}
function get_stats($str){
    $stat = array();
    for ($i=1; $i<=45; $i++){
        if (check_substring($str, "<!--stat".$i."-->"))
            array_push($stat, array(
                "stat_type" => $i,
                "stat_value" =>  get_next_char(get_string_betweenTWO($str, "<!--stat".$i."-->", "</span>"), "+", 8)
                )
            );
    }
    return $stat;
}
function get_resists($str){
    $rest = array();
    $res_names = array(
        "Holy Resistance" => "holy_res",
        "Fire Resistance" => "fire_res",
        "Nature Resistance" => "nature_res",
        "Frost Resistance" => "frost_res",
        "Shadow Resistance" => "shadow_res",
        "Arcane Resistance" => "arcane_res"
    );
    foreach ($res_names as $key => $res){
        if (check_substring($str, $key)) {
            array_push($rest, array(
                "res" => $res,
                "res_val" => get_everything_before($str, $key)
            ));
        }
    }
    return $rest;
}
function check_unique($str){
    if (check_substring($str, "Unique")){
        return 1;
    }
    return 0;
}
function get_bind_string($bind){
    switch($bind){
        case 0:
            $bind = "No bounds";
            break;
        case 1:
            $bind = "Binds when picked up";
            break;
        case 2:
            $bind = "Binds when equipped";
            break;
        case 3:
            $bind = "Binds when used";
            break;
        case 4:
            $bind = "Quest item";
            break;
        case 5:
            $bind = "Quest Item1";
            break;
        default:
            $bind = "No bounds";
            break;
    }
     return $bind;
}
function check_bonding($str){
    $bind = array(
        "No bounds" => 0,
        "Binds when picked up" => 1,
        "Binds when equipped" => 2,
        "Binds when used" => 3,
        "Quest item" => 4,
        "Quest Item1" => 5
    );
    foreach ($bind as $key => $bound){
        if (check_substring($str, $key))
            return $bound;
    }
    return 0;
}
function check_substring($string, $substring){
    if (strpos($string, $substring) !== false) {
        return true;
    }
    return false;
}

function getstringbetween($string, $start, $end){
    $r = explode($start, $string);
    if (isset($r[2])){
        $r = explode($end, $r[2]);
        return $r[0];
    }
    return '';
}
function get_string_between($string, $tag){
    $str = $string;
    $m = substr($str, strpos($str, '<'.$tag.'>')+7);
    $m = substr($m, 0, strpos($m, '</'.$tag.'>'));
    return $m;
}

function get_string_betweenTWO($string, $tag1, $tag2, $count = 10){
    $str = $string;
    $m = substr($str, strpos($str, $tag1) + $count);
    $m = substr($m, 0, strpos($m, $tag2));
    return $m;
}

function get_next_char($string, $before, $chars = 1){
    $str = substr($string, strpos($string, $before) + strlen($before), $chars);
    preg_match_all('!\d+!', $str, $matches);
    $str = implode('', $matches[0]);
    return $str;
}

function get_everything_before($string, $after){
    $str = substr($string, strpos($string, $after) - (strlen($after) - 12), 12);
    preg_match_all('!\d+!', $str, $matches);
    $str = implode('', $matches[0]);
    return $str;
}
function strtoINT($str){
    preg_match_all('!\d+!', $str, $matches);
    $str = implode('', $matches[0]);
    return $str;
}
?>