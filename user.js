$(document).ready(function () {
    // Fade-in effect on page load
    $("body").css("opacity", "1");

    // Toggle Orders Details on Click
    $(".order").click(function () {
        $(this).find("p").slideToggle(); // Slide toggle order details
    });

    // Add hover glow effect to Profile & Orders section
    $(".profile, .orders").hover(
        function () {
            $(this).css({
                "box-shadow": "0 0 15px rgba(76, 175, 80, 0.7)",
                "transform": "scale(1.05)"
            });
        },
        function () {
            $(this).css({
                "box-shadow": "none",
                "transform": "scale(1)"
            });
        }
    );

    // Smooth scrolling to user-info when page loads
    $("html, body").animate(
        { scrollTop: $(".user-info").offset().top - 50 },
        1000
    );

    // Simple AJAX example: check user name
    $("#check-name-btn").click(function() {
        var name = $("#user-name").val();
        if(name) {
            $.ajax({
                url: "html/user.php",
                type: "POST",
                data: { ajax_name: name },
                success: function(response) {
                    $("#ajax-response").text(response);
                }
            });
        }
    });
});
