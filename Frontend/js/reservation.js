//Elements:
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
    selectedTimeInput.value = this.dataset.time; // pour PHP plus tard
    updateSummary();
});
});

// --- 3. SUMMARY DYNAMIQUE ---
const summaryDate   = document.getElementById("summaryDate");
const summaryTime   = document.getElementById("summaryTime");
const summaryGuests = document.getElementById("summaryGuests");
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


// --- 4. VALIDATION AVANT SUBMIT ---
const form      = document.getElementById("reservationForm");
const alertTime = document.getElementById("alertTime");

form.addEventListener("submit", function(e) {
  if (!selectedTimeInput.value) {
    e.preventDefault();
    alertTime.classList.remove("d-none");
    alertTime.scrollIntoView({ behavior: "smooth", block: "center" });
  } else {
    alertTime.classList.add("d-none");
  }
});

// --- 5. DÉSACTIVER LES times PASSÉS ---
function updateAvailableTimes() {
  const now = new Date();
  const isToday = dateInput.value === today;

  timeBtns.forEach(btn => {
    if (!isToday) {
      btn.disabled = false;
      btn.classList.remove("disabled-time");
      return;
    }

    // Convertir → objet Date d'aujourd'hui
    const [time, period] = btn.dataset.time.split(" ");
    let [hours, minutes] = time.split(":").map(Number);
    if (period === "PM" && hours !== 12) hours += 12;
    if (period === "AM" && hours === 12) hours = 0;

    const slotTime = new Date();
    slotTime.setHours(hours, minutes, 0, 0);

    if (slotTime <= now) {
      btn.disabled = true;
      btn.classList.add("disabled-time");
      // Si ce bouton était sélectionné, on le désélectionne
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