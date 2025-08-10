function validateLoginForm() {
    let email = document.getElementById("email").value.trim(); // Changed from "mail" to "email"
    let password = document.getElementById("password").value.trim();

    let valid = true;

    const emailRegex = /^[a-zA-Z0-9._-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,5}$/;
    const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/;

    if (!emailRegex.test(email)) {
        document.getElementById("email_msg").innerText = "Invalid email format"; // Changed from "mail_msg" to "email_msg"
        valid = false;
    } else {
        document.getElementById("email_msg").innerText = "";
    }

    if (!passwordRegex.test(password)) {
        document.getElementById("password_msg").innerText = "Password must be at least 6 characters, include uppercase, lowercase, and a digit.";
        valid = false;
    } else {
        document.getElementById("password_msg").innerText = "";
    }

    return valid;
}

// Live validation as user types
function liveValidateEmail() {
  let email = document.getElementById("email").value.trim();
  const emailMsg = document.getElementById("email_msg");
  const emailRegex = /^[a-zA-Z0-9._-]+@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,5}$/;
  if (!email) {
    emailMsg.innerText = "Please enter your email address.";
    emailMsg.style.color = "#888";
  } else if (!emailRegex.test(email)) {
    emailMsg.innerText = "Invalid email format (e.g. user@example.com)";
    emailMsg.style.color = "#e3342f";
  } else {
    emailMsg.innerText = "Looks good!";
    emailMsg.style.color = "#10b981";
  }
}

function liveValidatePassword() {
  let password = document.getElementById("password").value.trim();
  const passwordMsg = document.getElementById("password_msg");
  const passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
  if (!password) {
    passwordMsg.innerText = "Password is required.";
    passwordMsg.style.color = "#888";
  } else if (!passwordRegex.test(password)) {
    passwordMsg.innerText = "At least 8 chars,1 Uppercase,1 Lowercase,1 digit";
    passwordMsg.style.color = "#e3342f";
  } else {
    passwordMsg.innerText = "Strong password!";
    passwordMsg.style.color = "#10b981";
  }
}


// Form submission validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login_form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
                e.preventDefault();
                return false;
            }
        });
    }
});