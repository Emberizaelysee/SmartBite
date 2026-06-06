//DATE INPUT:
const dateInput = document.getElementById("dateInput");
const today = new Date().toISOString().split("T")[0];
dateInput.setAttribute("min", today);
// MAX = 2 mois à partir d'aujourd'hui
const maxDate = new Date();
maxDate.setMonth(maxDate.getMonth() + 2);
dateInput.setAttribute("max", maxDate.toISOString().split("T")[0]);

// --- 2. TIME BUTTONS ---
const timeBtns = document.querySelectorAll(".time-btn");
const selectedTimeInput = document.getElementById("selectedTime");
timeBtns.forEach(btn => {
  btn.addEventListener("click", function() {
    timeBtns.forEach(b => b.classList.remove("active"));
    this.classList.add("active");
    selectedTimeInput.value = this.dataset.time; 
    updateSummary();
});
});

// --- 3. SUMMARY DYNAMIQUE ---
const summaryDate   = document.getElementById("summaryDate");
const summaryTime   = document.getElementById("summaryTime");
const summaryGuests = document.getElementById("summaryGuests");
const summaryTable = document.getElementById("summaryTable");
const guestsSelect  = document.getElementById("guestsSelect");

function updateSummary() {
  // Date
  if (dateInput.value) {
    const [year, month, day] = dateInput.value.split("-");
    const d = new Date(year, month - 1, day);
    summaryDate.textContent = d.toLocaleDateString("en-US", {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric"
    });
  } else {
    summaryDate.textContent = "—";
  }
  // Time
  const activeBtn = document.querySelector(".time-btn.active");
  summaryTime.textContent = activeBtn ? activeBtn.dataset.time : "—";
  // Guests
  const g = parseInt(guestsSelect.value);
  summaryGuests.textContent = g === 1 ? "1 Person" : `${g} People`;
}

dateInput.addEventListener("change", updateSummary);
guestsSelect.addEventListener("change", updateSummary);

// --- 4. GESTION DES QUERY PARAMS (après redirect PHP) ---
function handleQueryParams() {
  const params = new URLSearchParams(window.location.search);
 
  if (params.get("success") === "1") {
    const table  = params.get("table")  || "—";
    const date   = params.get("date")   || "";
    const time   = params.get("time")   || "—";
    const guests = params.get("guests") || "—";
 
    // Afficher la table assignée dans le summary
    summaryTable.textContent = `Table ${table}`;
 
    // Remplir le summary avec les valeurs de la réservation
    if (date) {
      const [year, month, day] = date.split("-");
      const d = new Date(year, month - 1, day);
      summaryDate.textContent = d.toLocaleDateString("en-US", {
        weekday: "long", month: "long", day: "numeric", year: "numeric"
      });
    }
    summaryTime.textContent   = decodeURIComponent(time);
    summaryGuests.textContent = parseInt(guests) === 1 ? "1 Person" : `${guests} People`;
 
    // Toast de succès
    showToast(
      "success",
      `<i class="fa-solid fa-circle-check me-2"></i>
       Reservation confirmed! <strong>Table ${table}</strong> is booked for you.
       A confirmation email has been sent!`
    );
 
    // Nettoyer l'URL sans recharger la page
    window.history.replaceState({}, document.title, window.location.pathname);
    
     // Vider le summary après 5s (quand le toast disparaît)
      setTimeout(() => {
      summaryDate.textContent   = "—";
      summaryTime.textContent   = "—";
      summaryGuests.textContent = "2 People";
      summaryTable.textContent  = "—";
     }, 5000);
    return;
  }
 
  // Gestion des erreurs
  const errorMessages = {
    missing_fields:     "Please fill in all required fields.",
    invalid_date:       "The date you entered is not valid.",
    past_date:          "Please select a future date.",
    invalid_time:       "The selected time is not valid.",
    no_table_available: "No table is available for this date and time. Please try another slot.",
    db_error:           "Something went wrong. Please try again."
  };
 
 const error = params.get("error");
if (error && errorMessages[error]) {
  showToast("error", `<i class="fa-solid fa-triangle-exclamation me-2"></i>${errorMessages[error]}`);

  // Restaurer les valeurs dans le formulaire
  const date   = params.get("date")   || "";
  const time   = params.get("time")   || "";
  const guests = params.get("guests") || "";

  if (date) {
    dateInput.value = date;
    updateSummary();
    updateAvailableTimes();
  }

  if (guests) {
    guestsSelect.value = guests;
    updateSummary();
  }

  if (time) {
    const decoded = decodeURIComponent(time);
    timeBtns.forEach(btn => {
      if (btn.dataset.time === decoded) {
        btn.classList.add("active");
        selectedTimeInput.value = decoded;
      }
    });
    updateSummary();
  }

  window.history.replaceState({}, document.title, window.location.pathname);
}
}
 
// --- 5. TOAST NOTIFICATION ---
function showToast(type, message) {
  const existing = document.getElementById("reservationToast");
  if (existing) existing.remove();
 
  const toast = document.createElement("div");
  toast.id        = "reservationToast";
  toast.className = `alert ${type === "success" ? "alert-success" : "alert-danger"} d-flex align-items-center gap-2 shadow`;
  toast.setAttribute("role", "alert");
  toast.innerHTML = message;
 
  Object.assign(toast.style, {
    position:     "fixed",
    top:          "20px",
    right:        "20px",
    zIndex:       "9999",
    minWidth:     "300px",
    maxWidth:     "420px",
    padding:      "1rem 1.25rem",
    borderRadius: "12px",
    animation:    "fadeInDown 0.3s ease"
  });
 
  document.body.appendChild(toast);
 
  setTimeout(() => {
    toast.style.transition = "opacity 0.4s ease";
    toast.style.opacity    = "0";
    setTimeout(() => toast.remove(), 400);
  }, 5000);
}
 
// --- 6. VALIDATION AVANT SUBMIT ---
const form      = document.getElementById("reservationForm");
const alertTime = document.getElementById("alertTime");
 
form.addEventListener("submit", async function (e) {
  e.preventDefault();
 
  // 6a. Vérifier si l'user est connecté (même endpoint que auth_navbar.js)
  try {
    const sessionRes  = await fetch('../Backend/api/auth/session_check.php', { credentials: 'include' });
    const contentType = sessionRes.headers.get('content-type') || '';
 
    if (!sessionRes.ok || !contentType.includes('application/json')) {
      showLoginAlert();
      return;
    }
 
    const sessionData = await sessionRes.json();
    if (!sessionData.logged_in) {
      showLoginAlert();
      return;
    }
  } catch (err) {
    showLoginAlert();
    return;
  }
 
  // 6b. Vérifier qu'un time slot est sélectionné
  if (!selectedTimeInput.value) {
    alertTime.classList.remove("d-none");
    alertTime.scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }
 
  alertTime.classList.add("d-none");
  form.submit();
});
  
// --- 7. ALERTE "PLEASE LOG IN" ---
function showLoginAlert() {
  const existing = document.getElementById("alertLogin");
  if (existing) existing.remove();

  const alertDiv = document.createElement("div");
  alertDiv.id        = "alertLogin";
  alertDiv.className = "alert alert-warning d-flex align-items-center justify-content-between gap-3 py-3 px-4";
  alertDiv.setAttribute("role", "alert");
  alertDiv.innerHTML = `
    <div>
      <i class="fa-solid fa-lock me-2"></i>
      <strong>You need to be logged in</strong> to complete a reservation.
    </div>
    <a href="./signin.html?redirect=reservation" class="btn btn-sm btn-green px-3 fw-semibold">
      Log In
    </a>
  `;

  const submitBtn = form.querySelector('button[type="submit"]');
  submitBtn.parentNode.insertBefore(alertDiv, submitBtn);
  alertDiv.scrollIntoView({ behavior: "smooth", block: "center" });
} 
// --- 8. DÉSACTIVER LES TIMES PASSÉS ---
function updateAvailableTimes() {
  const now     = new Date();
  const isToday = dateInput.value === today;
 
  timeBtns.forEach(btn => {
    if (!isToday) {
      btn.disabled = false;
      btn.classList.remove("disabled-time");
      return;
    }
 
    const [time, period] = btn.dataset.time.split(" ");
    let [hours, minutes] = time.split(":").map(Number);
    if (period === "PM" && hours !== 12) hours += 12;
    if (period === "AM" && hours === 12) hours = 0;
 
    const slotTime = new Date();
    slotTime.setHours(hours, minutes, 0, 0);
 
    if (slotTime <= now) {
      btn.disabled = true;
      btn.classList.add("disabled-time");
      if (btn.classList.contains("active")) {
        btn.classList.remove("active");
        selectedTimeInput.value = "";
        updateSummary();
      }
    } else {
      btn.disabled = false;
      btn.classList.remove("disabled-time");
    }
  });
}
 
dateInput.addEventListener("change", updateAvailableTimes);
updateAvailableTimes();
 
// --- INIT ---
handleQueryParams();