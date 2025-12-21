<?php
/**
 * 2SureSub - Services API (v1)
 */
require_once __DIR__ . '/api.php';

$type = $_GET['type'] ?? 'data';

if ($type === 'data') {
    $networks = dbFetchAll("SELECT * FROM networks WHERE status = 'active'");
    $plans = dbFetchAll("SELECT dp.*, n.name as network FROM data_plans dp JOIN networks n ON dp.network_id = n.id WHERE dp.status = 'active' ORDER BY n.name, dp.price_user");
    
    apiResponse(true, 'Data services retrieved', [
        'networks' => $networks,
        'plans' => $plans
    ]);
} elseif ($type === 'cable') {
    $providers = dbFetchAll("SELECT * FROM cable_providers WHERE status = 'active'");
    $plans = dbFetchAll("SELECT cp.*, p.name as provider FROM cable_plans cp JOIN cable_providers p ON cp.provider_id = p.id WHERE cp.status = 'active' ORDER BY p.name, cp.price_user");
    
    apiResponse(true, 'Cable services retrieved', [
        'providers' => $providers,
        'plans' => $plans
    ]);
} elseif ($type === 'exams') {
    $exams = dbFetchAll("SELECT * FROM exam_types WHERE status = 'active'");
    apiResponse(true, 'Exam services retrieved', ['exams' => $exams]);
} else {
    apiResponse(false, 'Invalid service type', null, 400);
}
