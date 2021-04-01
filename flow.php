<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require_once('includes/db.inc.php');


if (array_key_exists('product', $_GET) && is_numeric($_GET['product']) && $_GET['product']) {
    $product=$_GET['product'];
} else {
    $product=12003;
}

$activity=1;

if (array_key_exists('reaction', $_GET) && is_numeric($_GET['reaction'])) {
    if ($_GET['reaction']) {
    $activity="1,11";
    }
}

$sql='select typename,typeid from invTypes where invTypes.published=1 and marketgroupid is not null';

$stmt = $dbh->prepare($sql);

$stmt->execute();
$typeidlookup=array();
$typenamelookup=array();
while ($row = $stmt->fetchObject()) {
    $typeidlookup[$row->typename]=$row->typeid;
    $typenamelookup[$row->typeid]=$row->typename;
}




function lookupMaterials($dbh,$product,$typenamelookup,$activity) {
    $productblueprintsql='select typeid,quantity from industryActivityProducts where productTypeID=:product and activityid in ('.$activity.')';
    $materialblueprintsql='select materialtypeid,quantity,activityid from industryActivityMaterials where typeid=:blueprinttype and activityid in ('.$activity.')';

    $stmt = $dbh->prepare($productblueprintsql);

    $productdata=array();

    $stmt->execute(array(':product'=>$product));
    while ($row = $stmt->fetchObject()) {
        $matstmt = $dbh->prepare($materialblueprintsql);
        $matstmt->execute(array(':blueprinttype'=>$row->typeid));
        $productdata[$product]=array();
        while ($matrow = $matstmt->fetchObject()) {
            $materials=lookupMaterials($dbh,$matrow->materialtypeid,$typenamelookup,$activity);
            array_push($productdata[$product],array("material"=>$matrow->materialtypeid,"name"=>$typenamelookup[$matrow->materialtypeid],"quantity"=>$matrow->quantity,"activity"=>$matrow->activityid,"materials"=>$materials));
        }
    }
    return $productdata;
}


$flow=lookupMaterials($dbh,$product,$typenamelookup,$activity);

print json_encode($flow)

?>
