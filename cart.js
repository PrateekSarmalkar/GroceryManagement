    $(document).ready(function() {
    // Function to update the total price
    function updateTotal() {
        let total = 0;
        $(".cart-table tbody tr").each(function() {
            let price = parseFloat($(this).find("td:eq(2)").text().replace("Rs. ", ""));
            let qty = parseInt($(this).find(".qty").text());
            let itemTotal = price * qty;
            $(this).find("td:eq(4)").text("Rs. " + itemTotal);
            total += itemTotal;
        });
        $(".cart-total h3").text("Total: Rs. " + total);
    }
    // Increase and Decrease quantity
    $(".qty-btn:contains('+')").click(function() {
        let qtyElement = $(this).siblings(".qty");
        let qty = parseInt(qtyElement.text());
        qtyElement.text(qty + 1);
        updateTotal();
    });
    $(".qty-btn:contains('-')").click(function() {
        let qtyElement = $(this).siblings(".qty");
        let qty = parseInt(qtyElement.text());
        if (qty > 1) {
            qtyElement.text(qty - 1);
            updateTotal();
        }
    });
    // Remove item from cart
    $(".remove-btn").click(function() {
        $(this).closest("tr").remove();
        updateTotal();
    });
    // Initial total calculation
    updateTotal();
    // AJAX: Update cart item quantity without reloading
    $(".quantity-form").on("submit", function(e) {
        e.preventDefault();
        var $form = $(this);
        var productId = $form.find("input[name='product_id']").val();
        var quantity = $form.find("input[name='quantity']").val();

        $.ajax({
            url: "cart.php",
            type: "POST",
            data: {
                ajax_update_cart: true,
                product_id: productId,
                quantity: quantity
            },
            success: function(response) {
                // Show confirmation message
                $form.closest(".cart-item").find(".price").after(
                    "<div class='ajax-msg' style='color:green;'>Updated!</div>"
                );
                setTimeout(function() {
                    $form.closest(".cart-item").find(".ajax-msg").fadeOut(500, function() { $(this).remove(); });
                }, 1500);
                // Optionally, update the total price on the page
                if ($(".cart-total h3").length) {
                    $(".cart-total h3").text("Total: Rs. " + response);
                }
            }
        });
    });
    });
