<?php
session_start();
$app_token = $_SESSION['app_token'];
$user_app_token = $_SESSION['user_app_token'];
$ticket_id = $_SESSION['ticket_id'];
$ID_of_user = $_SESSION['ID_of_user'];
$cost_description = $_SESSION['cost_description'];
$realname = $_SESSION['realname'];
$firstname = $_SESSION['firstname'];
$date = date('Y-m-d H:i:s');

if(isset($_POST['cost_is_approved'])) {
    echo "<h3>Спасибо за обратную связь!</h3>";
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
    $json1 = array(
        'input' => array(
            'itemtype' => 'Ticket',
            'items_id' => $ticket_id,
            'users_id' => $ID_of_user,
            'content' => 'Затрата подтверждена. Наименование: '.$cost_description. '. Время подтверждения: '. $date)
    );

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
} elseif(isset($_POST['cost_is_declined'])) { ?>
    <html>
    <body>
    <head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="send_answer.js"></script>
        <style>

            html {font-family: 'Montserrat', Arial, sans-serif;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;}body {
                                                                                                                              background: #fbfbf9;
                                                                                                                          }
            button {overflow: visible;}
            button, select {text-transform: none;}
            button, input, select, textarea {color: #5A5A5A;font: inherit;margin: 0;}
            input {line-height: normal;}
            textarea {overflow: auto;}
            #container {border: solid 3px #474544;max-width: 768px;margin: 60px auto;position: relative;}
            form {padding: 37.5px;margin: 50px 0;}
            h1 {color: #474544;font-size: 32px;font-weight: 700;letter-spacing: 7px;text-align: center;text-transform: uppercase;}
            input[type='text'], [type='email'], select, textarea {background: none;border: none;border-bottom: solid 2px #474544;color: #474544;font-size: 1.000em;
                font-weight: 400;letter-spacing: 1px;margin: 0em 0 1.875em 0;padding: 0 0 0.875em 0;text-transform: uppercase;width: 100%;-webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;-ms-box-sizing: border-box;-o-box-sizing: border-box;box-sizing: border-box;-webkit-transition: all 0.3s;-moz-transition: all 0.3s;
                -ms-transition: all 0.3s;-o-transition: all 0.3s;transition: all 0.3s;}
            input[type='text']:focus, [type='email']:focus, textarea:focus {outline: none;padding: 0 0 0.875em 0;}
            select {background: url('https://cdn4.iconfinder.com/data/icons/ionicons/512/icon-ios7-arrow-down-32.png') no-repeat right;outline: none;
                -moz-appearance: none;-webkit-appearance: none;}
            select::-ms-expand {display: none;}
            .subject {width: 100%;}
            .name {width: 100%;text-align: center;}
            textarea {line-height: 150%;resize: none;width: 100%;}
            ::-webkit-input-placeholder {color: #474544;}
            :-moz-placeholder {color: #474544;opacity: 1;}
            ::-moz-placeholder {color: #474544;opacity: 1;}
            :-ms-input-placeholder {color: #474544;}
            @media screen and (max-width: 768px) {  #container {margin: 20px auto;width: 95%;}  }
            @media screen and (max-width: 480px) {
                h1 {font-size: 26px;}  }
            @media screen and (max-width: 420px) {
                h1 {font-size: 18px;}
                input[type='text'], [type='email'], select, textarea {font-size: 0.875em;}  }
            .button_approved:hover{background:#78788c;color:#fff}
        </style>
    </head>
    <div id="container">
    <h3 style="text-align: center">Выберите причину отказа/доработки:</h3>
                <div class="subject">
                    <label for="subject"></label>
                    <select placeholder="Subject line" name="subject" id="subject_input" onchange="changeReason(value)" required>
                        <option disabled hidden selected>Выберите причину</option>
                        <option value="Причина 1">Причина 1</option>
                        <option value="Причина 2">Причина 2</option>
                        <option value="Другое">Другое</option>
                    </select>
                </div>
                <div class="message">
                    <label for="message"></label>
                    <textarea name="message" placeholder="Напишите свою причину" id="message_input" onchange="changeFunction(this.value)" cols="30" rows="2" style="display: none"></textarea>
                </div>
        <div class="submit" style="text-align: center;">
            <input type="submit" id="btn_declined" class="button_declined" name="button_send" value="Отправить" onclick="send()" style="color: #fff; text-decoration: none; user-select: none; background: rgb(206, 106, 81); padding: .7em 1.5em; outline: none;border:rgb(206, 106, 81); cursor: pointer;">
        </div>
</div>
    </body>
    </html>
<?php } ?>