<?php
session_start();
$app_token = $_SESSION['app_token'];
$user_app_token = $_SESSION['user_app_token'];
$ticket_id = $_SESSION['ticket_id'];
$ID_of_user = $_SESSION['ID_of_user'];
$cost_description = $_SESSION['cost_description'];
$reason = $_SESSION['reason'];
$date = date('Y-m-d H:i:s');
$val = $_POST['reason'];
$another_reason = $_POST['another_reason'];
echo $another_reason;
    echo "<h3>Спасибо! Заявка была отправлена в работу</h3>";
    // Инициализация пользовательской сессии
    if ($curl = curl_init()) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: user_token ' . $user_app_token,
            'App-Token: ' . $app_token));
        curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/initSession/');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        $session = json_decode($out, true);
        $session_token = $session["session_token"];
        curl_close($curl);
    }
    if($another_reason == "") {
    $json1 = array(
        'input' => array(
            'itemtype' => 'Ticket',
            'items_id' => $ticket_id,
            'users_id' => $ID_of_user,
            'content' => 'Затрата была отклонена. Наименование: '.$cost_description. '. Причина: '.$val.'. Время: '. $date)
    );
    } else {
        $json1 = array(
            'input' => array(
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'users_id' => $ID_of_user,
                'content' => 'Затрата была отклонена. Наименование: '.$cost_description. '. Причина: '.$another_reason.'. Время: '. $date)
        );
    }

    if ($curl = curl_init()) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
        curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/Ticket/' . $ticket_id . '/ITILFollowup');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json1));
        $out = curl_exec($curl);
        curl_close($curl);
    }

    if ($curl = curl_init()) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'App-Token: ' . $app_token, 'Session-token: ' . $session_token));
        curl_setopt($curl, CURLOPT_URL, 'https://glpi-test.ru/apirest.php/killSession');
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $out = curl_exec($curl);
        curl_close($curl);
    }



