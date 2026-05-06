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

// Vérifie si le mot de passe est fort
function isStrongPassword(){
    const pass = document.getElementById("password1").value;
    if (pass.length < 8) return false;
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?{}[\]~]).+$/;
    return regex.test(pass);
}

// Affiche le message de force
function checkPasswordStrength() {
    const pass = document.getElementById("password1").value;
    const messageDiv = document.getElementById("passwordMessage");

    if (pass === "") { messageDiv.textContent = ""; return; }

    if (isStrongPassword()) {
        messageDiv.style.color   = "var(--green)";
        messageDiv.textContent   = "Strong password ✅";
    } else {
        messageDiv.style.color   = "red";
        messageDiv.textContent   = "Password must have: min 8 chars, uppercase, lowercase, number, special char ❌";
    }
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