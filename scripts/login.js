// Login logic moved to bottom of file


// Gebruikersdata opslaan & ophalen via localStorage, deze is temporary en ga ik verwijderen nadat Bram de PHP vanuit thuis opzet.
function getUsers() {
  return JSON.parse(localStorage.getItem("users")) || [];
}

function saveUsers(users) {
  localStorage.setItem("users", JSON.stringify(users));
}

// -------------------- REGISTRATIE --------------------
const registerForm = document.getElementById("registerForm");
if (registerForm) {
  registerForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const name = document.getElementById("fullname").value.trim();
    const email = document.getElementById("email").value.trim();
    const user = document.getElementById("username").value.trim();
    const pass = document.getElementById("password").value.trim();
    const confirm = document.getElementById("confirmPassword").value.trim();
    const error = document.getElementById("registerError");

    error.textContent = "";

    if (!name || !email || !user || !pass || !confirm) {
      error.textContent = "Vul alle velden in.";
      return;
    }

    if (pass.length < 6) {
      error.textContent = "Wachtwoord moet minimaal 6 tekens bevatten.";
      return;
    }

    if (pass !== confirm) {
      error.textContent = "Wachtwoorden komen niet overeen.";
      document.getElementById("password").classList.add("invalid");
      document.getElementById("confirmPassword").classList.add("invalid");
      return;
    }

    fetch('../api/users/add_user.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ username: user, email: email, password: pass, role: 'user' })
    })
    .then(response => response.json())
    .then(data => {
      if (data.username) { // add_user.php returns the created user object on success (or check status code)
         alert("Account succesvol aangemaakt! Je kunt nu inloggen.");
         window.location.href = "../paginas/index.html";
      } else if (data.errors) {
         // Handle errors object
         let msg = "Fout bij registreren:\n";
         for (const key in data.errors) {
             msg += `- ${data.errors[key]}\n`;
         }
         error.textContent = msg;
      } else {
         // Fallback
         alert("Account succesvol aangemaakt! Je kunt nu inloggen.");
         window.location.href = "../paginas/index.html";
      }
    })
    .catch(err => {
      console.error('Error:', err);
      error.textContent = "Er is een fout opgetreden bij het registreren.";
    });
  });
}

// ...existing code...
// -------------------- LOGIN --------------------
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const user = document.getElementById("username").value.trim();
    const pass = document.getElementById("password").value.trim();
    const error = document.getElementById("loginError");

    error.textContent = "";

    if (!user || !pass) {
      error.textContent = "Vul alle velden in.";
      return;
    }

    fetch('../php/login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ username: user, password: pass })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        window.location.href = data.redirect || "../paginas/index.html";
      } else {
        error.textContent = data.message;
        document.getElementById("username").classList.add("invalid");
        document.getElementById("password").classList.add("invalid");
      }
    })
    .catch(err => {
      console.error('Error:', err);
      error.textContent = "Er is een fout opgetreden. Probeer het later opnieuw.";
    });
  });
}
