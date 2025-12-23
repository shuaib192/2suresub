<?php
/**
 * 2SureSub - Purchase API (v1)
 * Handle Data, Airtime, Cable, and Electricity via API
 */
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/../../includes/InlomaxAPI.php';

$input = getApiInput();
$type = $input['type'] ?? '';

if (!$type) {
    apiResponse(false, 'Missing service type (data, airtime, cable, electricity)', null, 400);
}

$wallet = getUserWallet($user['id']);

switch ($type) {
    case 'data':
        $planId = (int)($input['plan_id'] ?? 0);
        $phone = cleanInput($input['phone'] ?? '');
        
        if (!$planId || !$phone) apiResponse(false, 'Missing plan_id or phone', null, 400);
        
        $plan = dbFetchOne("SELECT dp.*, n.name as network_name FROM data_plans dp JOIN networks n ON dp.network_id = n.id WHERE dp.id = ?", [$planId]);
        if (!$plan) apiResponse(false, 'Invalid data plan', null, 404);
        
        $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
        if ($wallet['balance'] < $price) apiResponse(false, 'Insufficient balance', null, 402);
        
        $reference = generateReference('DATA-API');
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            if (deductWallet($user['id'], $price, "Data API: {$plan['data_amount']} {$plan['network_name']}", $reference)) {
                $apiResponse = $inlomax->buyData($plan['plan_code'], $phone);
                $status = $apiResponse['status'] ?? 'failed';
                
                if ($status === 'success' || $status === 'processing') {
                    $dbStatus = ($status === 'success') ? 'completed' : 'processing';
                    dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, cost_price, plan_name, reference, status, api_response) VALUES (?, 'data', ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$user['id'], $plan['network_name'], $phone, $price, $plan['cost_price'], $plan['plan_name'], $reference, $dbStatus, json_encode($apiResponse)]);
                    apiResponse(true, "Transaction " . ucfirst($status), ['reference' => $reference]);
                } else {
                    creditWallet($user['id'], $price, "Refund: Data API failed", $reference . '-REF');
                    apiResponse(false, "API Error: " . ($apiResponse['message'] ?? 'Unknown error'), null, 502);
                }
            } else {
                apiResponse(false, 'Internal Error: Wallet deduction failed', null, 500);
            }
        } else {
            apiResponse(false, 'API not configured for live transactions', null, 503);
        }
        break;

    case 'airtime':
        $networkId = (int)($input['network_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $phone = cleanInput($input['phone'] ?? '');
        
        if (!$networkId || $amount < 50 || !$phone) apiResponse(false, 'Missing network_id, amount, or phone', null, 400);
        
        $network = dbFetchOne("SELECT * FROM networks WHERE id = ?", [$networkId]);
        if (!$network) apiResponse(false, 'Invalid network', null, 404);
        
        // Airtime is usually direct amount or with reseller discount
        $discount = ($user['role'] === 'reseller') ? 0.98 : 1.0; 
        $price = $amount * $discount;
        
        if ($wallet['balance'] < $price) apiResponse(false, 'Insufficient balance', null, 402);
        
        $reference = generateReference('AIR-API');
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            if (deductWallet($user['id'], $price, "Airtime API: {$network['name']} " . formatMoney($amount), $reference)) {
                $serviceId = $network['service_id_airtime'] ?? $network['code'];
                $apiResponse = $inlomax->buyAirtime($serviceId, $phone, $amount);
                $status = $apiResponse['status'] ?? 'failed';
                
                if ($status === 'success' || $status === 'processing') {
                    $dbStatus = ($status === 'success') ? 'completed' : 'processing';
                    dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, reference, status, api_response) VALUES (?, 'airtime', ?, ?, ?, ?, ?, ?)",
                        [$user['id'], $network['name'], $phone, $price, $reference, $dbStatus, json_encode($apiResponse)]);
                    apiResponse(true, "Transaction " . ucfirst($status), ['reference' => $reference]);
                } else {
                    creditWallet($user['id'], $price, "Refund: Airtime API failed", $reference . '-REF');
                    apiResponse(false, "API Error: " . ($apiResponse['message'] ?? 'Unknown error'), null, 502);
                }
            }
        }
        break;
        
    case 'cable':
        $planId = (int)($input['plan_id'] ?? 0);
        $iuc = cleanInput($input['iuc'] ?? '');
        
        if (!$planId || !$iuc) apiResponse(false, 'Missing plan_id or iuc', null, 400);
        
        $plan = dbFetchOne("SELECT cp.*, p.name as provider_name, p.code as provider_code FROM cable_plans cp JOIN cable_providers p ON cp.provider_id = p.id WHERE cp.id = ?", [$planId]);
        if (!$plan) apiResponse(false, 'Invalid cable plan', null, 404);
        
        $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
        if ($wallet['balance'] < $price) apiResponse(false, 'Insufficient balance', null, 402);
        
        $reference = generateReference('CABLE-API');
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            if (deductWallet($user['id'], $price, "Cable API: {$plan['provider_name']} {$plan['plan_name']}", $reference)) {
                $apiResponse = $inlomax->buyCable($plan['provider_code'], $iuc, $plan['plan_code']);
                $status = $apiResponse['status'] ?? 'failed';
                
                if ($status === 'success' || $status === 'processing') {
                    $dbStatus = ($status === 'success') ? 'completed' : 'processing';
                    dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, cost_price, plan_name, reference, status, api_response) VALUES (?, 'cable', ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$user['id'], $plan['provider_name'], $iuc, $price, $plan['cost_price'], $plan['plan_name'], $reference, $dbStatus, json_encode($apiResponse)]);
                    apiResponse(true, "Transaction " . ucfirst($status), ['reference' => $reference]);
                } else {
                    creditWallet($user['id'], $price, "Refund: Cable API failed", $reference . '-REF');
                    apiResponse(false, "API Error: " . ($apiResponse['message'] ?? 'Unknown error'), null, 502);
                }
            }
        }
        break;

    case 'electricity':
        $discoId = (int)($input['disco_id'] ?? 0);
        $meterNum = cleanInput($input['meter_number'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $meterType = $input['meter_type'] ?? 'prepaid';
        
        if (!$discoId || !$meterNum || $amount < 500) apiResponse(false, 'Missing disco_id, meter_number, or invalid amount', null, 400);
        
        $disco = dbFetchOne("SELECT * FROM electricity_providers WHERE id = ?", [$discoId]);
        if (!$disco) apiResponse(false, 'Invalid disco provider', null, 404);
        
        $price = $amount; // Usually no discount on electricity
        if ($wallet['balance'] < $price) apiResponse(false, 'Insufficient balance', null, 402);
        
        $reference = generateReference('ELEC-API');
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            if (deductWallet($user['id'], $price, "Electricity API: {$disco['name']} " . formatMoney($amount), $reference)) {
                $serviceId = $disco['service_id'] ?: $disco['code'];
                $apiResponse = $inlomax->buyElectricity($serviceId, $meterNum, $amount, $meterType);
                $status = $apiResponse['status'] ?? 'failed';
                
                if ($status === 'success' || $status === 'processing') {
                    $dbStatus = ($status === 'success') ? 'completed' : 'processing';
                    $token = $apiResponse['data']['token'] ?? '';
                    dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, reference, status, api_response) VALUES (?, 'electricity', ?, ?, ?, ?, ?, ?)",
                        [$user['id'], $disco['name'], $meterNum, $price, $reference, $dbStatus, json_encode($apiResponse)]);
                    apiResponse(true, "Transaction " . ucfirst($status), ['reference' => $reference, 'token' => $token]);
                } else {
                    creditWallet($user['id'], $price, "Refund: Electricity API failed", $reference . '-REF');
                    apiResponse(false, "API Error: " . ($apiResponse['message'] ?? 'Unknown error'), null, 502);
                }
            }
        }
        break;

    default:
        apiResponse(false, 'Unsupported service type. Use data, airtime, cable, or electricity.', null, 400);
}
