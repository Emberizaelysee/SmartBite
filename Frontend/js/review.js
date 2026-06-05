// --- 1. STARS ---
const stars = document.querySelectorAll(".star");
const ratingInput = document.getElementById("rating");

stars.forEach(star => {
    star.addEventListener("click", () => {
        const value = parseInt(star.getAttribute("data-value"));
        ratingInput.value = value;

        stars.forEach(s => s.classList.remove("active"));
        for (let i = 0; i < value; i++) {
            stars[i].classList.add("active");
        }
    });
});
// --- 2. TOAST ---
function showToast(type, message) {
    const existing = document.getElementById("reviewToast");
    if (existing) existing.remove();

    const toast = document.createElement("div");
    toast.id        = "reviewToast";
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

// --- 3. HANDLE QUERY PARAMS ---
function handleQueryParams() {
    const params = new URLSearchParams(window.location.search);

    if (params.get("success") === "1") {
        showToast("success", `<i class="fa-solid fa-circle-check me-2"></i>Your review has been submitted successfully!`);
        window.history.replaceState({}, document.title, window.location.pathname);
        return;
    }

    const errorMessages = {
        missing_fields: "Please fill in all required fields.",
        invalid_rating: "Please select a rating between 1 and 5.",
        invalid_dish:   "The selected dish is not valid.",
        db_error:       "Something went wrong. Please try again."
    };

    const error = params.get("error");
    if (error && errorMessages[error]) {
        showToast("error", `<i class="fa-solid fa-triangle-exclamation me-2"></i>${errorMessages[error]}`);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// --- 4. LOGIN ALERT ---
function showLoginAlert() {
    const existing = document.getElementById("alertLogin");
    if (existing) return;

    const alertDiv = document.createElement("div");
    alertDiv.id        = "alertLogin";
    alertDiv.className = "alert alert-warning d-flex align-items-center justify-content-between gap-3 py-3 px-4";
    alertDiv.setAttribute("role", "alert");
    alertDiv.innerHTML = `
        <div>
            <i class="fa-solid fa-lock me-2"></i>
            <strong>You need to be logged in</strong> to leave a review.
        </div>
        <a href="./signin.html?redirect=review" class="btn btn-sm btn-green px-3 fw-semibold">Log In</a>
    `;

    const submitBtn = document.querySelector('#reviewForm button[type="submit"]');
    submitBtn.parentNode.insertBefore(alertDiv, submitBtn);
    alertDiv.scrollIntoView({ behavior: "smooth", block: "center" });
}

// --- 5. CATEGORY FILTER ---
async function loadCategories() {
    const res = await fetch('/SmartBite/Backend/api/menu/get_categories.php');
    const data = await res.json();

    const select = document.getElementById("categorySelect");
    data.forEach(cat => {
        const option = document.createElement("option");
        option.value       = cat.IdCategory;
        option.textContent = cat.CategoryName;
        select.appendChild(option);
    });
}

document.getElementById("categorySelect").addEventListener("change", function () {
    const catId = this.value;
    if (catId === "") {
        document.getElementById("dishTableBody").innerHTML = "";
        return;
    }

    fetch(`/SmartBite/Backend/api/review/get_menu.php?cat=${catId}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById("dishTableBody").innerHTML = html;
        });
});

// --- 6. SUBMIT ---
document.getElementById("reviewForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    // 6a. session check
    try {
        const sessionRes  = await fetch('/SmartBite/Backend/api/auth/session_check.php', { credentials: 'include' });
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

    // 6b. validation client-side
    const dish   = document.querySelector('input[name="dish_id"]:checked');
    const rating = document.getElementById("rating").value;
    const review = document.getElementById("review").value.trim();

    if (!dish || rating === "" || review === "") {
        showToast("error", `<i class="fa-solid fa-triangle-exclamation me-2"></i>Please fill in all required fields.`);
        return;
    }

    // 6c. submit
    this.submit();
});

// --- INIT ---
handleQueryParams();
loadCategories();