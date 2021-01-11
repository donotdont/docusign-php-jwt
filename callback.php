<?php

ini_set('max_execution_time', 600);
set_time_limit(600);

// [0] Settings
$accountId = "xxxxxxxxxxxx";
$powerFormId = "11111111-1234-5678-bc9d-xxxxxxxxxxxx";
$templateId = "2222222-1234-5678-99ca-xxxxxxxxxxxx";
$clientId = '33333333-1234-5678-9579-xxxxxxxxxxxx';
$clientUsernameId = '44444444-1234-5678-a9cd-xxxxxxxxxxxx';
// Keypair ID: 55555555-8ac0-4203-9d3c-xxxxxxxxxxxx
$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
...
-----END PUBLIC KEY-----
EOD;
$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
...
-----END RSA PRIVATE KEY-----
EOD;
$redirectUri = "http://localhost:8080/callback.php";
$urlServer = 'account.docusign.net';
$url = 'https://'.$urlServer;
$urlOauth = 'eu.docusign.com';
$urlToken = 'https://'.$urlOauth.'/oauth/token';

// Helper Function
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// [1] Authenticate with the JWT Grant
// Ref. https://developers.docusign.com/platform/auth
// Ref. https://developers.docusign.com/platform/auth/jwt/jwt-get-token
$now = time();
$nextHour = date($now + (3600 - $now % 3600));

$jwtHeader = base64url_encode(json_encode(array(
    "alg" => "RS256",
    "typ" => "JWT"
)));

/**
* RSASHA256(
*  base64_encode(-----BEGIN PUBLIC KEY-----
* MIIBIj...IDAQAB
* -----END PUBLIC KEY-----) . "." .
*  base64_encode(-----BEGIN RSA PRIVATE KEY-----
* MIIEpQ...9ZwKY=
* -----END RSA PRIVATE KEY-----)
**/

// Base64url encoded JSON claim set
$jwtClaim = base64url_encode(json_encode(array(
    "iss" => $clientId,
    "sub" => $clientUsernameId,
    "aud" => $urlServer,
    "iat" => $now,
    "exp" => $nextHour,
    "scope" => "signature impersonation",
)));

/* The base string for the signature: {Base64url encoded JSON header}.{Base64url encoded JSON claim set}
* $algo = "sha256WithRSAEncryption";
* openssl_sign(base64_encode($publicKey). "." .base64_encode($privateKey), $jwtSig, $privateKey, $algo);
**/
openssl_sign(
    $jwtHeader . "." . $jwtClaim,
    $jwtSig,
    $privateKey,
    "sha256WithRSAEncryption"
);
$jwtSign = base64url_encode($jwtSig);

// {Base64url encoded JSON header}.{Base64url encoded JSON claim set}.{Base64url encoded signature}
$jwtAssertion = $jwtHeader . "." . $jwtClaim . "." . $jwtSign;

/** curl --data "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&
* assertion=YOUR_JSON_WEB_TOKEN" --request POST https://account-d.docusign.com/oauth/token
**/
$data = array(
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwtAssertion,
);
$query = http_build_query($data);
$header = array(
    "Content-Type: application/x-www-form-urlencoded",
    "Content-Length: " . strlen($query),
);

$options = array(
    'http' => array(
        'header'  => implode("\r\n", $header),
        'method'  => 'POST',
        'content' => $query
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, FALSE, $context);

// Decode Token
$key = json_decode($result, TRUE);

/**
* https://app.docusign.com/api/accounts/b8ec5160-7b30-43da-858c-xxxxxxxxxxxx/envelopes/fa48eb18-90b4-4425-99ca-f80c1370be29?include=recipients,powerform,folders,custom_fields,tabs
* https://app.docusign.com/api/accounts/{$clientAccountId}/envelopes/{$$templateId}?include=recipients,powerform,folders,custom_fields,tabs
* */

// [2] Get all envelops
// Ref. https://developers.docusign.com/docs/esign-rest-api/reference/envelopes/envelopes/liststatuschanges
$url = "https://{$urlOauth}/restapi/v2.1/accounts/{$accountId}/envelopes";
$day = 120;
$from_date = date("c", (time() - ($day * 24 * 60 * 60)));

$data = array(
    'from_date' => $from_date,
    'to_date' => date("c", time()),
    'include' => 'custom_fields,documents,attachments,extensions,folders,recipients,powerform,payment_tabs,tabs',
    'exclude' => 'recipients,powerform,folders',
);

$query = http_build_query($data);
$url .= "?" . $query;

$header = array(
    "Content-Type: application/json",
    "Authorization: Bearer " . $key['access_token']
);

$options = array(
    'http' => array(
        'header'  => implode("\r\n", $header),
        'method'  => 'GET',
        'content' => json_encode(array()),
    )
);
$context = stream_context_create($options);
$result = file_get_contents($url, FALSE, $context);

$envelopes = json_decode($result, TRUE);

// [3] Insert all envelops to MySQL Database
$host = "localhost";
$port = 3306;
$con = new PDO("mysql:host=$host;port=$port", 'root', '');
$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   // PDO error mode is set to exception

foreach ($envelopes['envelopes'] as $envelope_key => $envelope_value) {
    $envelopeId = $envelope_value['envelopeId'];
    if(isset($envelopeId))
    foreach ($envelope_value['recipients']['signers'] as $recipient_key => $recipient_value) {
        $newRecipientData = array();
        $newRecipientData['envelopeId'] = $envelopeId;
        $newRecipientData['templateAccessCodeRequired'] = isset($recipient_value['templateAccessCodeRequired']) ? boolval($recipient_value['templateAccessCodeRequired']) : null;
        $newRecipientData['creationReason'] = isset($recipient_value['creationReason']) ? $recipient_value['creationReason'] : null;
        $newRecipientData['canSignOffline'] = isset($recipient_value['canSignOffline']) ? boolval($recipient_value['canSignOffline']) : null;
        $newRecipientData['requireUploadSignature'] = isset($recipient_value['requireUploadSignature']) ? boolval($recipient_value['requireUploadSignature']) : null;
        $newRecipientData['name'] = isset($recipient_value['name']) ? $recipient_value['name'] : null;
        $newRecipientData['email'] = isset($recipient_value['email']) ? $recipient_value['email'] : null;
        $newRecipientData['recipientId'] = isset($recipient_value['recipientId']) ? intval($recipient_value['recipientId']) : null;
        $newRecipientData['recipientIdGuid'] = isset($recipient_value['recipientIdGuid']) ? $recipient_value['recipientIdGuid'] : null;
        $newRecipientData['requireIdLookup'] = isset($recipient_value['requireIdLookup']) ? boolval($recipient_value['requireIdLookup']) : null;
        $newRecipientData['userId'] = isset($recipient_value['userId']) ? $recipient_value['userId'] : null;
        $newRecipientData['routingOrder'] = isset($recipient_value['routingOrder']) ? intval($recipient_value['routingOrder']) : null;
        $newRecipientData['roleName'] = isset($recipient_value['roleName']) ? $recipient_value['roleName'] : null;
        $newRecipientData['status'] = isset($recipient_value['status']) ? $recipient_value['status'] : null;
        $newRecipientData['completedCount'] = isset($recipient_value['completedCount']) ? intval($recipient_value['completedCount']) : null;
        $newRecipientData['declinedReason'] = isset($recipient_value['declinedReason']) ? $recipient_value['declinedReason'] : null;
        $newRecipientData['signedDateTime'] = isset($recipient_value['signedDateTime']) ? (new DateTime($recipient_value['signedDateTime']))->format('Y-m-d h:i:s') : null;
        $newRecipientData['deliveredDateTime'] = isset($recipient_value['deliveredDateTime']) ? (new DateTime($recipient_value['deliveredDateTime']))->format('Y-m-d h:i:s') : null;
        $newRecipientData['deliveryMethod'] = isset($recipient_value['deliveryMethod']) ? $recipient_value['deliveryMethod'] : null;
        $newRecipientData['templateLocked'] = isset($recipient_value['templateLocked']) ? boolval($recipient_value['templateLocked']) : null;
        $newRecipientData['templateRequired'] = isset($recipient_value['templateRequired']) ? boolval($recipient_value['templateRequired']) : null;
        $newRecipientData['recipientType'] = isset($recipient_value['recipientType']) ? $recipient_value['recipientType'] : null;

        $newRecipientData['accessCode'] = null;

        /**
         *  `envelopeId`, `templateAccessCodeRequired`, `creationReason`, `canSignOffline`, `requireUploadSignature`, `name`, `email`,
         *  `recipientId`, `recipientIdGuid`, `requireIdLookup`, `userId`, `routingOrder`, `roleName`, `status`, `completedCount`,
         *  `declinedReason`, `signedDateTime`, `deliveredDateTime`, `deliveryMethod`, `templateLocked`, `templateRequired`, `recipientType`,
         *  `accessCode`
         * */

        $sql = 'INSERT IGNORE into docusign.recipients VALUES(:envelopeId, :templateAccessCodeRequired, :creationReason, :canSignOffline, :requireUploadSignature, :name, :email, :recipientId, :recipientIdGuid, :requireIdLookup, :userId, :routingOrder, :roleName, :status, :completedCount, :declinedReason, :signedDateTime, :deliveredDateTime, :deliveryMethod, :templateLocked, :templateRequired, :recipientType, :accessCode)';

        $stmt = $con->prepare($sql);
        $result = $stmt->execute($newRecipientData);
    }
}

//[4] Check recipient if not exists accessCode from MySQL Database
$sql = 'SELECT envelopeId, email FROM docusign.recipients WHERE accessCode IS NULL';
$stmt = $con->query($sql);
$recipients = $stmt->fetchAll();

foreach ($recipients as $recipients_kay => $recipients_value) {
    // [5] Get accessCode in envelope
    // Ref. https://developers.docusign.com/docs/esign-rest-api/reference/envelopes/envelopes/get
    $envelopeId = $recipients_value['envelopeId'];
    $url = "https://{$urlOauth}/restapi/v2.1/accounts/{$accountId}/envelopes/{$envelopeId}";

    $data = array(
        'include' => 'recipients,powerform,folders,custom_fields,tabs',
    );

    $query = http_build_query($data);
    $url .= "?" . $query;

    $header = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $key['access_token']
    );

    $options = array(
        'http' => array(
            'header'  => implode("\r\n", $header),
            'method'  => 'GET',
            'content' => json_encode(array()),
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, FALSE, $context);

    $envelopes = json_decode($result, TRUE);

    if (isset($envelopes['recipients']['signers']) && count($envelopes['recipients']['signers']) > 0 && isset($envelopes['recipients']['signers'][0]['accessCode'])) {
        // [6] Update accessCode to Database
        $updateData = array(
            'accessCode' => $envelopes['recipients']['signers'][0]['accessCode'],
            'envelopeId' => $envelopeId
        );

        $sql = 'UPDATE docusign.recipients SET accessCode=:accessCode WHERE envelopeId=:envelopeId';
        $stmt = $con->prepare($sql);
        $result = $stmt->execute($updateData);
    }

    sleep(3);
}
