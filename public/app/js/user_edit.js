$(document).ready(
    function() {
        $(".change_password").click(
            function() {
                if($(this).is(':checked')) {
                    $("#password_div").slideDown();
                }
                else {
                    $("#password_div").slideUp();
                }
            }
        );
    }
);
