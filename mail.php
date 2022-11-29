<html>
<?php
session_start();
    // Токены для подключения к GLPI
    $app_token = 'XXXXXXXXXXX';
    $user_app_token ='XXXXXXXXX';
    // Данные для ДБ glpi-test
    $pass = "XXXXXXXX";
    $user = "XXXXXXXX";
    // Данные для ДБ затрат
    $pass_costs = "XXXXXXXX";
    $user_costs = "XXXXXXXX";
    $id = "";
    foreach($_GET as $k => $v){
        $id = $k;
    }
    // Получаю данные из БД costs
    $dbh_costs = new PDO('mysql:host=localhost;dbname=office_glpi', $user_costs, $pass_costs);
    $conn_email_cost = $dbh_costs->prepare('SELECT id, users_id, email, ticket_id, description FROM email_users WHERE id=:id');
    $conn_email_cost->bindParam(":id", $id);
    $conn_email_cost->execute();
    $result_email_cost = $conn_email_cost->fetchAll();
    $cost_id = $result_email_cost[0]['id'];
    $ID_of_user = $result_email_cost[0]['users_id'];
    $ticket_id = $result_email_cost[0]['ticket_id'];
    $cost_description = $result_email_cost[0]['description'];

    $dbh = new PDO('mysql:host=localhost;dbname=office_glpi', $user, $pass);
    $conn = $dbh->prepare('SELECT realname, firstname FROM glpi_users WHERE id=:id');
    $conn->bindParam(":id", $ID_of_user);
    $conn->execute();
    $result = $conn->fetchAll();
    $realname = $result[0]['realname'];
    $firstname = $result[0]['firstname'];

    $_SESSION['app_token'] = $app_token;
    $_SESSION['user_app_token'] = $user_app_token;
    $_SESSION['ticket_id'] = $ticket_id;
    $_SESSION['ID_of_user'] = $ID_of_user;
    $_SESSION['cost_description'] = $cost_description;
    $_SESSION['realname'] = $realname;
    $_SESSION['firstname'] = $firstname;
    // Получаю из БД информацию о затрате
    $conn_cost_info = $dbh->prepare('SELECT name, comment, actiontime, cost_time, cost_fixed, cost_material FROM glpi_ticketcosts WHERE tickets_id=:ticket_id AND name=:name');
    $conn_cost_info->bindParam(":ticket_id", $ticket_id);
    $conn_cost_info->bindParam(":name", $cost_description);
    $conn_cost_info->execute();
    $result_cost_info = $conn_cost_info->fetchAll();
    $cost_name = $result_cost_info[0]['name'];
    $cost_comment = $result_cost_info[0]['comment'];
    $cost_actiontime = $result_cost_info[0]['actiontime'];
    $cost_cost_time = $result_cost_info[0]['cost_time'];
    $cost_cost_fixed = $result_cost_info[0]['cost_fixed'];
    $cost_cost_material = $result_cost_info[0]['cost_material'];
?>
<head><title>Подтверждение затраты</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        textarea {line-height: 150%;height: 150px;resize: none;width: 100%;}
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
<body>
<!--    <p>Подтвердите затрату: <br>-->
<!--        Наименование: <p id="name_of_cost">Общие работы</p>. <br>Комментарий: Настройка сервера. <br>Продолжительность: 6300. <br>Стоимость 1 часа: 110.0000. <br>Фиксированная стоимость: 10.0000. <br>Стоимость материалов: 1.0000</p>-->
<!--    <input type="submit" id="btn_approved" class="button_approved" name="cost_is_approved" onclick="cost_is_approved()" value="Подтвердить выполнение работы" style="color: #fff; text-decoration: none; user-select: none; background: rgb(127, 206, 81); padding: .7em 1.5em; outline: none;border:rgb(127, 206, 81);cursor: pointer;">-->
<!--    <input type="submit" id="btn_declined" class="button_declined" name="cost_is_declined" onclick="cost_is_declined()" value="Необходимы доработки" style="color: #fff; text-decoration: none; user-select: none; background: rgb(206, 106, 81); padding: .7em 1.5em; outline: none;border:rgb(206, 106, 81); cursor: pointer;">-->
<div id="container">
    <h3 style="text-align: center">Подтвердите затрату</h3>
    <!--    <div class="underline">-->
    <!--    </div>-->
    <form action="https://glpi-test.ru/costs/costs.php" method="post" id="contact_form">
        <div class="name">
            <p><h4>Подтвердите затрату: <br>
                Наименование: <?php print $cost_name ?>. <br>Комментарий: <?php print $cost_comment ?>. <br>Продолжительность: <?php print $cost_actiontime ?>. <br>Стоимость 1 часа: <?php print $cost_cost_time ?>. <br>Фиксированная стоимость: <?php print $cost_cost_fixed ?>. <br>Стоимость материалов: <?php print $cost_cost_material ?></h4></p>
        </div>
        <div class="submit" style="text-align: center;">
            <input type="submit" id="btn_approved" class="button_approved" name="cost_is_approved" onclick="cost_is_approved()" value="Подтвердить выполнение работы" style="color: #fff; text-decoration: none; user-select: none; background: rgb(127, 206, 81); padding: .7em 1.5em; outline: none;border:rgb(127, 206, 81);cursor: pointer;">
            <input type="submit" id="btn_declined" class="button_declined" name="cost_is_declined" onclick="cost_is_declined()" value="Необходимы доработки" style="color: #fff; text-decoration: none; user-select: none; background: rgb(206, 106, 81); padding: .7em 1.5em; outline: none;border:rgb(206, 106, 81); cursor: pointer;">
        </div>
    </form>
</div>
</body>
</html>
