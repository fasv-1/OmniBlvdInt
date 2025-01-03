<?php

declare(strict_types=1);

// Requirements
require_once('App/Connections/db_con.php');
require_once('App/Controllers/auth.php');
require_once('App/Controllers/customField.php');
require_once('App/Controllers/contacts.php');
require_once("App/Abstract/functionsLs.php");
require_once 'vendor/autoload.php'; // Predis Autoload 

// Redis instance
$redis = new Predis\Client();

// Configurations
define('LOG_FILE', 'pollSystem.log');
define('SLEEP_INTERVAL', 30); // Queue check interval (in seconds)

/**
 * Regists log messages on arquive.
 */
function logMessage(string $message): void
{
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

/**
 * Execute a polling from system.
 */
function pollSystem(string $locationId, string $accountId)
{
    try {
        $con = new con();

        // Checks credentials
        $stmt = $con->conn()->prepare("SELECT * FROM wl_credentials WHERE location = ?");
        $stmt->execute([$locationId]);
        $wlCredentials = $stmt->fetch();

        $stmt = $con->conn()->prepare("SELECT * FROM ls_credentials WHERE accountId = ?");
        $stmt->execute([$accountId]);
        $lsCredentials = $stmt->fetch();

        if (!$wlCredentials || !$lsCredentials) {
            throw new Exception("Credentials not found for location '$locationId' or account '$accountId'.");
        }
        $stmt = null;
        // Stops the loop if status is 0
        if ($wlCredentials['status'] != 1) {
            logMessage("Status inactive for location '$locationId'. Loop closed.");
            return $wlCredentials['status'];
        }

        // Checks last sale
        $stmt = $con->conn()->prepare("SELECT * FROM ls_lastsale WHERE accountId = ?");
        $stmt->execute([$accountId]);
        $lastSaleRecord = $stmt->fetch();

        $stmt = null;
        $abstLs = new AbstractLs();
        $urlS = "https://api.lightspeedapp.com/API/V3/Account/" . $lsCredentials['accountId'] . "/Sale.json?limit=5&sort=-completeTime&load_relations=all";
        $salesData = $abstLs->getSingleLsData($lsCredentials['token'], $urlS);

        if (!isset($salesData->Sale)) {
            throw new Exception("No sales recived from Ls API for account '$accountId'.");
        }

        $lastSale = $salesData->Sale[0];
        if (!$lastSale || $lastSale->Customer == NULL || ($lastSaleRecord && $lastSaleRecord['sale_date'] == $lastSale->completeTime && $lastSaleRecord['sale_value'] == $lastSale->total)) {
            logMessage("No sales found for account '$accountId'.");
        } else {
            // Update custom fields
            $customFields = [
                ['id' => $wlCredentials['customDate'], 'field_value' => $lastSale->completeTime],
                ['id' => $wlCredentials['customValue'], 'field_value' => $lastSale->total],
            ];

            $contacts = new Contacts();
            logMessage("Creating new contact for '$locationId'.");
            $contact = $contacts->addCustomContact($lastSale->Customer, $customFields, $wlCredentials['token'], $wlCredentials['location']);

            if (!isset($contact->contact)) {
                $contactId = $contact->meta->contactId;
                logMessage("Updating new contact for '$contactId'.");
                $contacts->updateCustomContact($customFields, $contactId, $wlCredentials['token']);
            }

            // Creats or updates DB
            $sql = $lastSaleRecord
                ? "UPDATE ls_lastsale SET sale_date = ?, sale_value = ? WHERE accountId = ?"
                : "INSERT INTO ls_lastsale (sale_date, sale_value, accountId) VALUES (?, ?, ?)";
            $stmt = $con->conn()->prepare($sql);
            $stmt->execute([$lastSale->completeTime, $lastSale->total, $lsCredentials['accountId']]);

            $stmt = null;
            logMessage($lastSaleRecord ? "Record updated for account '$accountId'." : "New record made for account '$accountId'.");
        }
        // Reinserts the task in the Redis queue to be processed again
        global $redis; // Accesses the Redis instance defined outside the role
        $taskData = json_encode(['id' => 'sales', 'location' => $locationId, 'account' => $accountId]);  // Task data
        $redis->rpush('polling_queue', $taskData); // Reinserts in line 
        logMessage("Task added to queued for location='$locationId', account='$accountId'.");
    } catch (Exception $e) {
        logMessage("Erro: " . $e->getMessage());
    }
}
/**
 * Execute a polling from Lightspeed Costumers to CRM Contacts. 
 */
function contactsPollSystem(string $locationId, string $accountId)
{
    try {
        $con = new con();

        // Checks credentials
        $stmt = $con->conn()->prepare("SELECT * FROM wl_credentials WHERE location = ?");
        $stmt->execute([$locationId]);
        $wlCredentials = $stmt->fetch();

        $stmt = $con->conn()->prepare("SELECT * FROM ls_credentials WHERE accountId = ?");
        $stmt->execute([$accountId]);
        $lsCredentials = $stmt->fetch();

        if (!$wlCredentials || !$lsCredentials) {
            throw new Exception("Credentials not found for location '$locationId' or account '$accountId'.");
        }
        $stmt = null;
        // Stops the loop if status is 0
        if ($wlCredentials['status_contacts'] != 1) {
            logMessage("Inactive status for '$locationId'. Loop closed.");
            return $wlCredentials['status_contacts'];
        }

        // Checks last sale
        $stmt = $con->conn()->prepare("SELECT * FROM ls_lastcontact WHERE accountId = ?");
        $stmt->execute([$accountId]);
        $lastCostumerRecord = $stmt->fetch();

        $stmt = null;
        $abstLs = new AbstractLs();
        $urlS = "https://api.lightspeedapp.com/API/V3/Account/" . $lsCredentials['accountId'] . "/Customer.json?limit=5&sort=-timeStamp&load_relations=all";
        $customerData = $abstLs->getSingleLsData($lsCredentials['token'], $urlS);

        if (!isset($customerData->Customer)) {
            throw new Exception("No costumer found from API for account '$accountId'.");
        }

        $lastCostumer = $customerData->Customer[0];
        if (!$lastCostumer || ($lastCostumerRecord && $lastCostumerRecord['timeStamp'] == $lastCostumer->timeStamp && $lastCostumerRecord['customerId'] == $lastCostumer->customerID)) {
            logMessage("No new costumers found for '$accountId'.");
        } else {

            $contacts = new Contacts();
            $contacts->addContacts($lastCostumer, $wlCredentials['token'], $wlCredentials['location']);

            // Creats or updates DB
            $sql = $lastCostumerRecord
                ? "UPDATE ls_lastcontact SET timeStamp = ?, customerId = ? WHERE accountId = ?"
                : "INSERT INTO ls_lastcontact (timeStamp, customerId, accountId) VALUES (?, ?, ?)";
            $stmt = $con->conn()->prepare($sql);
            $stmt->execute([$lastCostumer->timeStamp, $lastCostumer->customerID, $lsCredentials['accountId']]);

            $stmt = null;
            logMessage($lastCostumerRecord ? "Record updated for account '$accountId'." : "New record created for account '$accountId'.");
        }
        // Reinserts the task in the Redis queue to be processed again
        global $redis; // Accesses the Redis instance defined outside the role
        $taskData = json_encode(['id' => 'contacts', 'location' => $locationId, 'account' => $accountId]);  // Task data
        $redis->rpush('polling_queue', $taskData); // Reinserts in line 
        logMessage("Task 'contacts' reinserted on queue for location='$locationId', account='$accountId'.");
    } catch (Exception $e) {
        logMessage("Erro: " . $e->getMessage());
    }
}

/**
 * Consumer: Loop to check new queues
 */
while (true) {
    try {
        // Grabs a task from the Redis queue (blocking)
        $task = $redis->blpop('polling_queue', 0); // 'polling_queue' line name

        // If a task is received, it is processed.
        if ($task) {

            $taskData = json_decode($task[1], true);
            $location = $taskData['location'];
            $account = $taskData['account'];

            if ($taskData['id'] == 'sales') {
                logMessage("Processing task sales: location='$location', account='$account'.");

                $statusSales = pollSystem($location, $account);

                if ($statusSales === 0) {
                    logMessage("Status 0 detected. Loop closed for sales.");
                }
            }
            
            if($taskData['id'] == 'contacts'){
                logMessage("Processing task contacts: location='$location', account='$account'.");

                $statusContacts = contactsPollSystem($location, $account);

                if ($statusContacts === 0) {
                    logMessage("Status 0 detected. Loop closed for contacts.");
                }
            }
        }
    } catch (Exception $e) {
        logMessage("Erro: " . $e->getMessage());
    }

    // Sleep to avoid excessive usage of CPU
    sleep(SLEEP_INTERVAL);
}
