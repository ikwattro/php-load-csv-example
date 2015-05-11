<?php

include_once(__DIR__.'/vendor/autoload.php');

use Neoxygen\NeoClient\ClientBuilder;

$neoClient = ClientBuilder::create()
    ->addConnection('default', 'http', 'localhost', 7474, true, 'neo4j', 'password')
    ->setAutoFormatResponse(true)
    ->build();
$commitSize = 1000;

$transaction = $neoClient->prepareTransaction();

$linesToCommit = 0;
$csvLines = array_map('str_getcsv', file(__DIR__.'/satellites.csv'));
foreach ($csvLines as $i => $line) {
    if (0 !== $i) { // 0 is headers line
        // Let's say you want the label based on the 3rd column name, here Alt
        $label = trim($line[2]);
        $query = 'CREATE (a:' . $label . ') SET a.name = {sat_name}';
        $params = ['sat_name' => trim($line[0])];
        $transaction->pushQuery($query, $params);
        if ($linesToCommit >= $commitSize) { // If there is 1000 or more lines to commit
            // we commit the transaction and recreate a new prepared tx
            $transaction->commit();
            $linesToCommit = 0;
            $transaction = $neoClient->prepareTransaction();
        }
        $linesToCommit++;
    }
}
$transaction->commit(); // commit the remaining lines