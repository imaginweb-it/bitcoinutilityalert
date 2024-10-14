<?php
$content = \file_get_contents('php://input');
$update = \json_decode($content, true);

if(!$update) {
  exit;
}

require('tk.php');

// da cancellare $contentraw = $content;

$message = isset($update['message']) ? $update['message'] : null;
$messageId = isset($message['message_id']) ? $message['message_id'] : null;
$chatId = isset($message['chat']['id']) ? $message['chat']['id'] : null;
$firstname = isset($message['chat']['first_name']) ? $message['chat']['first_name'] : null;
$lastname = isset($message['chat']['last_name']) ? $message['chat']['last_name'] : null;
$username = isset($message['chat']['username']) ? $message['chat']['username'] : null;
$date = isset($message['date']) ? $message['date'] : null;
$text = isset($message['text']) ? $message['text'] : null;

$text = trim($text);
$text = strtolower($text);
header("Content-Type: application/json");

//impostazioni variabili iniziali
$response = "";
$keyboard = "";
$afee = array("h" => "High", "m" => "Medium", "l" => "Low");
$aprice = array("u" => "U.S.Dollar", "e" => "Euro", "s" => "Sterling");
$com1 = substr($text, 0, 1);
$com2 = substr($text, 1);

// impostazioni database
$dbFile = './db/database.db';
$dbh = new PDO('sqlite:' . $dbFile);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$result = $dbh->query("SELECT * FROM btcuser WHERE chatid = $chatId");
$row = $result->fetchAll(PDO::FETCH_ASSOC);

if (count($row)==0) {
    $dbh->exec("INSERT INTO btcuser (chatid, alertfee, alertprice, optionfee, optionprice, valuefee, valueprice, position) VALUES ($chatId, 0, 0, '', '', '', '','')");
}

$setPosition = isset($row[0]["position"]) ? $row[0]["position"] : '';

switch($text){
    case "/start":
        $dbh->exec("UPDATE btcuser SET position = '' WHERE chatid = $chatId");
        $response = "Ciao <b>$firstname</b>, benvenuto!\nQuesto Bot può fare inviarti degli Alert in determinate condizioni.\nSegui il menù in basso.\n/start per iniziare.\n/help per una guida più dettagliata.";
        $keyboard = '{ "keyboard": [["Alert Fees", "Alert Price"], ["Current Fees", "Current Prices"]], "resize_keyboard": true, "one_time_keyboard": false}';
        break;
    case "/help":
        $response = "Ciao <b>$firstname</b>!\nLo scopo di questo Bot è mandarti degli Alert sotto forma di messaggi Telegram inerenti al mondo Bitcoin, al verificarsi di determinate condizioni, come per esempio un check sul prezzo di Bitcoin, oppure un controllo sulle Fee.\nInoltre puoi interrogarlo per sapere quali sono le Fee che i miner richiedono attualmente per confermare una transazione oppure richiedere il prezzo corrente di Bitcoin in Dollari, Euro o Sterline.\n<i>Tutte le informazioni sono ricavate dalle API di Mempool.space.</i>.\nSegui il menù in basso per i vari comandi.\n/start per il menù iniziale.";
        break;
    case "/cancel":
        switch($setPosition){
            case "AF":
            case "FA":
            case "FB":
                $dbh->exec("UPDATE btcuser SET alertfee = 0, optionfee = '', valuefee = '' WHERE chatid = $chatId");
                $response = "<b>Alert annullato</b>";
                break;
            case "AP":
            case "PA":
            case "PB":
                $dbh->exec("UPDATE btcuser SET alertprice = 0, optionprice = '', valueprice = ''  WHERE chatid = $chatId");
                $response = "<b>Alert annullato</b>";
                break;
            default;
                $response = "<b>Operazione annullata</b>";
        }
        $keyboard = '{ "keyboard": [["Alert Fees", "Alert Price"], ["Current Fees", "Current Prices"]], "resize_keyboard": true, "one_time_keyboard": false}';
        break;
    case "alert fees":
    case "alert price":
        $keyboard = '{ "keyboard": [["Above", "Below"], ["/cancel", "/start"]], "resize_keyboard": true, "one_time_keyboard": true}';
        $response = '<b>:: Imposta un alert</b>';
        switch($text){
            case "alert fees":
                $dbh->exec("UPDATE btcuser SET position = 'AF' WHERE chatid = $chatId");
                if ($row[0]["alertfee"]==1 && !empty($row[0]["valuefee"])) {
                    SendMsg($chatId, "Alert Fee: <b>Attivo</b>\n| Stato: <b>".$row[0]["optionfee"]."</b> - Query: <b>".$afee[substr($row[0]["valuefee"],0,1)]."</b> priority <b>".substr($row[0]["valuefee"],1)."</b> sat/vB");
                } else {
                    SendMsg($chatId, "Alert Fee: Disattivato");
                }
                break;
            case "alert price":
                $dbh->exec("UPDATE btcuser SET position = 'AP' WHERE chatid = $chatId");
                if ($row[0]["alertprice"]==1 && !empty($row[0]["valueprice"])) {
                    SendMsg($chatId, "Alert Prezzo: <b>Attivo</b>\n| Stato: <b>".$row[0]["optionprice"]."</b> - Valuta: <b>".$aprice[substr($row[0]["valueprice"],0,1)]."</b> valore <b>".number_format(substr($row[0]["valueprice"],1),0,",",".")."</b>");
                } else {
                    SendMsg($chatId, "Alert Prezzo: Disattivato");
                }
                break;
        }
	    break;
    case "current fees":
        $data = file_get_contents('https://mempool.space/api/v1/fees/recommended');
        $fees = json_decode($data, true);
        $fastestfee = isset($fees['fastestFee']) ? $fees['fastestFee'] : null;
        $halfhourfee = isset($fees['halfHourFee']) ? $fees['halfHourFee'] : null;
        $hourfee = isset($fees['hourFee']) ? $fees['hourFee'] : null;
        $economyfee = isset($fees['economyFee']) ? $fees['economyFee'] : null;
        $minimumfee = isset($fees['minimumFee']) ? $fees['minimumFee'] : null;
        $response = "High Priority:      <b>$fastestfee</b> sat/vB\nMedium Priority: <b>$halfhourfee</b> sat/vB\nLow Priority:       <b>$hourfee</b> sat/vB\nNo Priority:         $economyfee sat/vB\nMinimum Fee:    $minimumfee sat/vB";
        break;
    case "current prices":
        $data = file_get_contents('https://mempool.space/api/v1/prices');
        $prices = json_decode($data, true);
        $usd = isset($prices['USD']) ? number_format($prices['USD'], 0, '', '.') : null;
        $eur = isset($prices['EUR']) ? number_format($prices['EUR'], 0, '', '.') : null;
        $gbp = isset($prices['GBP']) ? number_format($prices['GBP'], 0, '', '.') : null;
        $response = "U.S.Dollar: <b>$$usd</b>\nEuro:         <b>€$eur</b>\nSterling:    <b>£$gbp</b>";
        break;
    case "above":
        switch($setPosition){
            case "AF":
                $dbh->exec("UPDATE btcuser SET position = 'FA', alertfee = 1, optionfee = 'Above' WHERE chatid = $chatId");
                SendMsg($chatId, ":: Imposta un Alert se le Fee superano una determinata soglia per una specifica priorità <b>H</b>igh, <b>M</b>edium e <b>L</b>ow priority.\nEs. scrivendo <b>H50</b>, imposterai un Alert quando la Fee per alta priorità supereranno i 50 sat/vB\n /cancel per annullare\n /start per andare alla schermata iniziale");
                break;
            case "AP":
                $dbh->exec("UPDATE btcuser SET position = 'PA', alertprice = 1, optionprice = 'Above' WHERE chatid = $chatId");
                SendMsg($chatId, ":: Imposta un Alert quando il Prezzo Fiat del Bitcoin in una valuta a scelta tra <b>U</b>.S.Dollar, <b>E</b>uro e <b>S</b>terling salirà oltre una determinata soglia.\nEs. scrivendo <b>E30000</b>, imposterai un Alert quando il prezzo di Bitcoin supererà i 30.000 Euro\n /cancel per annullare\n /start per andare alla schermata iniziale");
                break;
        }
        break;
    case "below":
        switch($setPosition){
            case "AF":
                $dbh->exec("UPDATE btcuser SET position = 'FB', alertfee = 1, optionfee = 'Below' WHERE chatid = $chatId");
                SendMsg($chatId, ":: Imposta un Alert se le Fee scendono sotto una determinata soglia per una specifica priorità <b>H</b>igh, <b>M</b>edium e <b>L</b>ow priority.\nEs. scrivendo <b>H50</b>, imposterai un Alert quando la Fee per alta priorità scenderanno sotto i 50 sat/vB\n /cancel per annullare\n /start per andare alla schermata iniziale");
                break;
            case "AP":
                $dbh->exec("UPDATE btcuser SET position = 'PB', alertprice = 1, optionprice = 'Below' WHERE chatid = $chatId");
                SendMsg($chatId, ":: Imposta un Alert quando il Prezzo Fiat del Bitcoin in una valuta a scelta tra <b>U</b>.S.Dollar, <b>E</b>uro e <b>S</b>terling scenderà sotto una determinata soglia.\nEs. scrivendo <b>E30000</b>, imposterai un Alert quando il prezzo di Bitcoin scenderà sotti i 30.000 Euro\n /cancel per annullare\n /start per andare alla schermata iniziale");
                break;
        }
        break;
    default:
        switch($setPosition){
            case "FA":
            case "FB":
                if(array_key_exists($com1, $afee) && is_numeric($com2)) {
                    $dbh->exec("UPDATE btcuser SET valuefee = '$text', position = '' WHERE chatid = $chatId");
                    $keyboard = '{ "keyboard": [["Alert Fees", "Alert Price"], ["Current Fees", "Current Prices"]], "resize_keyboard": true, "one_time_keyboard": false}';
                    $response = "<b>Fee impostata</b>";
                } else {
                    $response = "Non so cosa vuoi specificare. Per piacere segui le istruzioni";
                }
                break;
            case "PA":
            case "PB":
                if(array_key_exists($com1, $aprice) && is_numeric($com2)) {
                    $dbh->exec("UPDATE btcuser SET valueprice = '$text', position = '' WHERE chatid = $chatId");
                    $keyboard = '{ "keyboard": [["Alert Fees", "Alert Price"], ["Current Fees", "Current Prices"]], "resize_keyboard": true, "one_time_keyboard": false}';
                    $response = "<b>Prezzo impostato</b>";
                } else {
                    $response = "Non so cosa vuoi specificare. Per piacere segui le istruzioni";
                }
                break;
            default;
                $response = "Non so cosa vuoi fare. Per piacere segui il menù";
        }
}

$parameters = array('chat_id' => $chatId, "text" => $response, "parse_mode" => "HTML");
$parameters["reply_markup"] = $keyboard;
$parameters["method"] = "sendMessage";

// Close the database connection
$dbh = null;

function SendMsg($chatId, $txt) {
    $url = WEBSITE."/sendMessage?chat_id=$chatId&parse_mode=HTML&text=".urlencode($txt);
    file_get_contents($url);
}

echo json_encode($parameters);
