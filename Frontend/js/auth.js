function togglePassword(inputId, iconId){
    let input = document.getElementById(inputId);
    let icon = document.getElementById(iconId);
    if(input.type === "password"){
        input.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }else{
        input.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }
}

function checkPasswordStrength() {
  const pass = document.getElementById("password1").value;
  const list = document.getElementById("passwordChecklist");

  if (pass === "") { list.style.display = "none"; return; }
  list.style.display = "block";

  const checks = {
    "chk-len":   pass.length >= 8,
    "chk-lower": /[a-z]/.test(pass),
    "chk-upper": /[A-Z]/.test(pass),
    "chk-num":   /[0-9]/.test(pass),
    "chk-spec":  /[^a-zA-Z0-9]/.test(pass),  // tout ce qui n'est pas lettre/chiffre
  };

  const labels = {
    "chk-len":   "Min. 8 characters",
    "chk-lower": "Lowercase letter (a-z)",
    "chk-upper": "Uppercase letter (A-Z)",
    "chk-num":   "Number (0-9)",
    "chk-spec":  "Special character (!@#$...)",
  };

  const allOk = Object.values(checks).every(Boolean);

  if (allOk) {
    list.style.display = "none";
    // afficher "Strong password ✅" dans un div séparé
    let strong = document.getElementById("strongMsg");
    if (!strong) {
      strong = document.createElement("div");
      strong.id = "strongMsg";
      strong.className = "password-msg";
      list.parentNode.insertBefore(strong, list.nextSibling);
    }
    strong.style.color = "#16c451";
    strong.textContent = "Strong password ✅";
    return;
  }

  // Cacher le message "Strong" si on revient en arrière
  const strong = document.getElementById("strongMsg");
  if (strong) strong.textContent = "";

  for (const [id, ok] of Object.entries(checks)) {
    const el = document.getElementById(id);
    el.textContent = (ok ? "✅ " : "⬜ ") + labels[id];
    el.style.color = ok ? "#16c451" : "#888";
  }
}

function isStrongPassword() {
  const pass = document.getElementById("password1").value;
  return pass.length >= 8 &&
    /[a-z]/.test(pass) && /[A-Z]/.test(pass) &&
    /\d/.test(pass)    && /[!@#$%^&*()_\-+=<>?{}[\]~]/.test(pass);
}
function checkPasswordMatch() {
    const pass1    = document.getElementById("password1").value;
    const pass2    = document.getElementById("password2").value;
    const matchDiv = document.getElementById("matchMessage");

    if (pass2 === "") { matchDiv.textContent = ""; return; }

    if (pass1 === pass2) {
        matchDiv.style.color = "var(--green)";
        matchDiv.textContent = "Passwords match ✅";
    } else {
        matchDiv.style.color = "red";
        matchDiv.textContent = "Passwords do not match ❌";
    }
}
 
//verifier les donnees ds signup.html avant de les envoyer
document.getElementById("signupForm")?.addEventListener("submit", function(e) {
    if (!isStrongPassword()) {
        e.preventDefault();
        checkPasswordStrength();
        document.getElementById("matchMessage").textContent = "";
        return;
    }

    const pass1 = document.getElementById("password1").value;
    const pass2 = document.getElementById("password2").value;
    if (pass1 !== pass2) {
        e.preventDefault();
        checkPasswordMatch();
        return;
    }
});

const params = new URLSearchParams(window.location.search);
const error = params.get("error");
if (error === "email_taken" || error === "server_error") {
    const msg = document.getElementById("errorMsg");
    if (msg) {
        msg.textContent = error === "email_taken"
            ? "This email has already been taken."
            : "An error occurred. Please try again.";
        msg.classList.remove("d-none");
    }
}

// Gestion erreurs signin
if (error === "invalid") {
    const msg = document.getElementById("errorMsg");
    if (msg) {
        msg.textContent = "Email or password incorrect.";
        msg.classList.remove("d-none");
    }
}
if (error === 'use_google') {
    const msg = document.getElementById("errorMsg");
    if (msg) {
        msg.textContent = "This account uses Google Sign-In. Please use the Google button below.";
        msg.classList.remove("d-none");
    }
}

// Gestion redirect après login
const redirectParam = params.get('redirect');
if (redirectParam) {
    const redirectInput = document.getElementById('redirectInput');
    if (redirectInput) redirectInput.value = redirectParam;
}