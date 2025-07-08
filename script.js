document.addEventListener("DOMContentLoaded", function () {
    const signInForm = document.querySelector(".login form");
    const signUpForm = document.querySelector(".signup form");

    function validateForm(event, redirectUrl) {
        event.preventDefault(); // Prevent form submission
        let email = event.target.querySelector("input[placeholder='EMAIL']");
        let password = event.target.querySelector("input[placeholder='PASSWORD']");

        if (email && !email.value.includes("@")) {
            alert("Please enter a valid email.");
            return;
        }

        if (password && password.value.length < 6) {
            alert("Password must be at least 6 characters long.");
            return;
        }

        // Redirect after successful validation
        window.location.href = redirectUrl;
    }

    if (signInForm) {
        signInForm.addEventListener("submit", function (event) {
            validateForm(event, "C:/xampp/htdocs/project/html/home.html");
        });
    }

    if (signUpForm) {
        signUpForm.addEventListener("submit", function (event) {
            validateForm(event, "C:/xampp/htdocs/project/html/signin.html");
        });
    }

    // Add any client-side enhancements here
    // For example, you could add input field focus effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.backgroundColor = '#f0f0f0';
        });
        input.addEventListener('blur', function() {
            this.style.backgroundColor = '#eae7e7';
        });
    });
});

