//Elements:
const dateInput = document.getElementById("dateInput");
const today = new Date().toISOString().split("T")[0];
dateInput.setAttribute("min", today);

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
dateInput.addEventListener("change", updateAvailableTimes);
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