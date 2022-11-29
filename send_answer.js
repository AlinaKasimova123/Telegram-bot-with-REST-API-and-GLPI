let reason = {};
let another_reason;

function changeFunction(value) {
    another_reason = value;
}

function changeReason(value_another_reason) {
    reason = value_another_reason;
    if((value_another_reason) == "Другое") {
        document.getElementById("message_input").style.display="block";
    } else {
        document.getElementById("message_input").style.display="none";
    }
}

function send() {
    $.ajax({
		type: 'POST',
        url: "/costs/declined_cost.php",
        data: {reason:reason, another_reason:another_reason},
		success: function(response) {
            console.log(response);
            window.location.href = 'https://glpi-test.ru/costs/send_reason.php';
    }     }); 
}