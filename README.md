# Bitcoin Utility Alert
Telegram Bot Alert di informazioni su Bitcoin realizzato in PHP e SQLITE.

## Help
Lo scopo di questo **Bot Telegram** è quello di inviare Alert sotto forma di messaggi Telegram inerenti a Bitcoin, al verificarsi di determinate condizioni.  
Ad esempio si può fare `Query` sul prezzo di Bitcoin "sopra" o "sotto" una determinata soglia, oppure una ***Query*** sulle Fee delle transazioni di mining.

Inoltre puoi interrogarlo per sapere quali sono le Fee che i miner richiedono attualmente oppure richiedere il prezzo corrente di Bitcoin in Dollari, Euro o Sterline.

> **Nota:** Tutte le informazioni sono ricavate dalle API di **Mempool.space**.

## Installazione
Dopo aver creato il Bot con tramite [BotFather](https://telegram.me/BotFather) e aver registrato l'Url (con ***/webhook.php*** finale) dello spazio hosting dove saranno posizionati i files, copiare `tk.php` e `webhook.php` all'interno della root, avendo cura di inserire il proprio `Token` nella variabile `$BOT_TOKEN` di tk.php.

Per l'esecuzione dei comandi automatici previsti dal Bot, personalizzare il file `sendmsg_bot.php` e posizionarlo in una cartella del proprio Hosting.  
Poi impostare un Processo Cron con l'intervallo che si desidera. Es.
```bash
0,30 *  *  *  *  /usr/local/bin/php /home/utente/script_cron/sendmsg_bot.php 2>&1
```

## Uso Bot esistente
Richiamare il Bot dall'indirizzo https://t.me/bitcoinutilityalertbot
