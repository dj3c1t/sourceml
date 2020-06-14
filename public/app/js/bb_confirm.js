$(document).ready(
    function() {
        $(".bb_confirm").each(
            function() {
                init_bb_confirm($(this));
            }
        );
    }
);

function init_bb_confirm(elt) {
    elt.click(
        function () {
            bootbox.confirm(
                elt.attr("data-message"),
                function(result) {
                    if(result) {
                        document.location.href = elt.attr("href");
                    }
                }
            )
            return false;
        }
    );
}
