document.addEventListener("DOMContentLoaded", function () {
    // Handle "Add to Cart" button clicks
    const cartButtons = document.querySelectorAll(".add-to-cart-btn");
    
    cartButtons.forEach(button => {
        button.addEventListener("click", function () {
            const productName = this.parentElement.querySelector("h3").textContent;
            alert(`${productName} has been added to the cart!`);
        });
    });

    // Handle Search button click
    const searchButton = document.querySelector(".search-box button");
    const searchInput = document.querySelector(".search-box input");

    if (searchButton && searchInput) {
        searchButton.addEventListener("click", function () {
            console.log(`Searching for: ${searchInput.value}`);
        });
    }
});
