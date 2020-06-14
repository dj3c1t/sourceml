function app_message(level, elt, message) {
    $(elt).prepend("<div class=\"row\"><div class=\"alert alert-" + level + "\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>" + message + "</div>");
}

$(document).ready(
    function() {
        $(".btn-help-toggle").click(
            function () {
                $($(this).get(0).dataset.helpcontent).fadeToggle();
            }
        );
    }
);