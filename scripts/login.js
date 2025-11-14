document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const user = document.getElementById("username").value.trim();
  const pass = document.getElementById("password").value.trim();

  if (user && pass) {
    alert("Welkom, " + user + "! Je bent succesvol ingelogd.");
  } else {
    alert("Vul alle velden in.");
  }
});

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

    const users = getUsers();
    if (users.find((u) => u.username === user)) {
      error.textContent = "Gebruikersnaam bestaat al.";
      return;
    }

    users.push({ fullname: name, email, username: user, password: pass });
    saveUsers(users);

    alert("Account succesvol aangemaakt! Je kunt nu inloggen.");
    window.location.href = "../paginas/index.html";
  });
}

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

    const users = getUsers();
    const found = users.find((u) => u.username === user && u.password === pass);

    if (!found) {
      error.textContent = "Ongeldige gebruikersnaam of wachtwoord.";
      document.getElementById("username").classList.add("invalid");
      document.getElementById("password").classList.add("invalid");
      return;
    }

    alert(`Welkom terug, ${found.fullname}!`);
    loginForm.reset();
    window.location.href = "../paginas/index.html";
  });
}
