#!/usr/bin/php
<?php
require_once('utils.php');


$client = new Client($rpc_host, $rpc_port, $rpc_user, $rpc_pass);
$mysqli = new Mysqli($db_host, $db_user, $db_pass, $db_name);

if ($client->getBalance($hot_account_main) == 0)
    $client->jsonrpc->move('', "zelles($hot_account_main)", 100);

// Test initial hot balances
wait_msg("Move funds to main account, then press Enter...");
$main_bal = floatval($client->getBalance($hot_account_main));
$pre_wait_bal = floatval($client->getBalance($hot_account_wait));
my_assert($main_bal > 0, '', 'Main balance not > 0');
my_assert(!is_null($pre_wait_bal), '', 'Wait balance is null');

// Test hot balances after invoicing
wait_msg("Create a new invoice, then press Enter...");
$wait_bal = floatval($client->getBalance($hot_account_wait));
my_assert($wait_bal > 0, '', 'Wait balance <= 0');
$diff_bal = $main_bal - floatval($client->getBalance($hot_account_main));
my_assert($diff_bal > 0, '', 'Main balance is the same as before');
my_assert($wait_bal == $pre_wait_bal + $diff_bal, '', 'wait and diff balances !=');

// Simulate confirmation
$invoice = $mysqli->query('select * from invoices order by -id;')->fetch_assoc();
$pre_user_bal = $client->getBalance($invoice['user']);
$client->credit($invoice['user'], $diff_bal);
$user_bal = $client->getBalance($invoice['user']);
my_assert($pre_user_bal != $user_bal, '', 'User balance unchanged by credit');
my_assert($user_bal == $pre_user_bal + $diff_bal, '', "User balance is wrong amount: $user_bal");

$accounts = $client->jsonrpc->listaccounts();
foreach ($accounts as $acc => $bal) {
    if ($acc)
        my_assert($bal >= 0, '', "$acc balance ($bal) not >= 0");
}

echo "Test passes!\n";
?>