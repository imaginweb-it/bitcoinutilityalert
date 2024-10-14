<?php
if (PHP_SAPI !== 'cli') { //prevent access direct to this file
    die();
}

require('/home/user/bitcoinutilityalert.example.com/tk.php');

// Set the database file location
$dbFile = '/home/user/bitcoinutilityalert.example.com/db/database.db';

// Create a new PDO object
$dbh = new PDO('sqlite:' . $dbFile);

// Set the error mode to exception
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = $dbh->query('SELECT * FROM btcuser WHERE alertfee = 1 AND valuefee IS NOT ""');
$rowsf = $result->fetchAll(PDO::FETCH_ASSOC);

if (count($rowsf)>0) {
    $data = file_get_contents('https://mempool.space/api/v1/fees/recommended');
    $fees = json_decode($data, true);
    $fee["h"] = isset($fees['fastestFee']) ? $fees['fastestFee'] : null;
    $fee["m"] = isset($fees['halfHourFee']) ? $fees['halfHourFee'] : null;
    $fee["l"] = isset($fees['hourFee']) ? $fees['hourFee'] : null;

    $priority = array ("h" => "High Priority", "m" => "Medium Priority", "l" => "Low Priority");

    foreach ($rowsf as $row) {
        $p = substr($row['valuefee'], 0, 1);
        $f = substr($row['valuefee'], 1);
        switch($row['optionfee']) {
            case "Above":
                    if ($fee[$p]>$f) {
                        SendMsg($row['chatid'],"Le Fee per <b>".$priority[$p]."</b> hanno superato <b>$f</b> sat/vB.");
                        $dbh->exec("UPDATE btcuser SET alertfee = 0, optionfee = '', valuefee = ''  WHERE id = ".$row['id']);
                    } elseif ($fee[$p]==$f) {
                        SendMsg($row['chatid'],"Le Fee per <b>".$priority[$p]."</b> sono proprio <b>$f</b> sat/vB.");
                        $dbh->exec("UPDATE btcuser SET alertfee = 0, optionfee = '', valuefee = ''  WHERE id = ".$row['id']);
                    }
                break;
            case "Below":
                    if ($fee[$p]<$f) {
                        SendMsg($row['chatid'],"Le Fee per <b>".$priority[$p]."</b> sono scese sotto <b>$f</b> sat/vB.");
                        $dbh->exec("UPDATE btcuser SET alertfee = 0, optionfee = '', valuefee = ''  WHERE id = ".$row['id']);
                    } elseif ($fee[$p]==$f) {
                        SendMsg($row['chatid'],"Le Fee per <b>".$priority[$p]."</b> sono proprio <b>$f</b> sat/vB.");
                        $dbh->exec("UPDATE btcuser SET alertfee = 0, optionfee = '', valuefee = ''  WHERE id = ".$row['id']);
                    }
                break;
        }
    }
}

$result = $dbh->query('SELECT * FROM btcuser WHERE alertprice = 1 AND valueprice IS NOT ""');
$rowsp = $result->fetchAll(PDO::FETCH_ASSOC);

if (count($rowsp)>0) {
    $data = file_get_contents('https://mempool.space/api/v1/prices');
    $prices = json_decode($data, true);
    $price["u"] = isset($prices['USD']) ? $prices['USD'] : null;
    $price["e"] = isset($prices['EUR']) ? $prices['EUR'] : null;
    $price["g"] = isset($prices['GBP']) ? $prices['GBP'] : null;
    $currency = array ("u" => "U.S.Dollar", "e" => "Euro", "g" => "Sterling");
    
    foreach ($rowsp as $row) {
        $c = substr($row['valueprice'], 0, 1);
        $v = substr($row['valueprice'], 1);
        switch($row['optionprice']) {
            case "Above":
                    if ($price[$c]>=$v) {
                        SendMsg($row['chatid'],"Il prezzo del Bitcoin ha superato <b>".number_format($v, 0, '', '.')."</b> $currency[$c].");
                        $dbh->exec("UPDATE btcuser SET alertprice = 0, optionprice = '', valueprice = ''  WHERE id = ".$row['id']);
                    }
                break;
            case "Below":
                    if ($price[$c]<=$v) {
                        SendMsg($row['chatid'],"Il prezzo del Bitcoin è sceso sotto <b>".number_format($v, 0, '', '.')."</b> $currency[$c].");
                        $dbh->exec("UPDATE btcuser SET alertprice = 0, optionprice = '', valueprice = ''  WHERE id = ".$row['id']);
                    }
                break;
        }
    }
}

function SendMsg($chatId, $txt) {
    $url = WEBSITE."/sendMessage?chat_id=$chatId&parse_mode=HTML&text=".urlencode($txt);
    file_get_contents($url);
}

// Close the database connection
$dbh = null;
