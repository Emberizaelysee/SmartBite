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
function isStrongPassword() {
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
        messageDiv.style.color   = "green";
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
        matchDiv.style.color = "green";
        matchDiv.textContent = "Passwords match ✅";
    } else {
        matchDiv.style.color = "red";
        matchDiv.textContent = "Passwords do not match ❌";
    }
}

//verifier les donnees ds sigup.html avant de les envoyer
document.querySelector("form[action='signup.php']")?.addEventListener("submit", function(e) {
    if (!isStrongPassword()) {
        e.preventDefault();
        document.getElementById("passwordMessage").textContent = "Password must have: min 8 chars, uppercase, lowercase, number, special char ❌";
        document.getElementById("passwordMessage").style.color = "red";
        return;
    }

    const pass1 = document.getElementById("password1").value;
    const pass2 = document.getElementById("password2").value;
    if (pass1 !== pass2) {
        e.preventDefault();
        document.getElementById("matchMessage").textContent = "Passwords do not match ❌";
        document.getElementById("matchMessage").style.color = "red";
    }
});