const stars = document.querySelectorAll(".star");
const ratingInput = document.getElementById("rating");

stars.forEach(star => {

star.addEventListener("click", () => {

let value = parseInt(star.getAttribute("data-value"));

ratingInput.value = value;

stars.forEach(s => s.classList.remove("active"));

for(let i=0;i<value;i++){
stars[i].classList.add("active");
}

});

});


document.getElementById("reviewForm").addEventListener("submit", function(e){
e.preventDefault();

let name = document.getElementById("name").value;
let email = document.getElementById("email").value;
let rating = document.getElementById("rating").value;
let review = document.getElementById("review").value;

let message = document.getElementById("message");

if(name === "" || email === "" || rating === "" || review === ""){

message.style.color = "red";
message.innerText = "Please fill all fields";

}else{

message.style.color = "green";
message.innerText = "Review submitted successfully!";

// RESET FORM
    document.getElementById("reviewForm").reset();
    stars.forEach(s => s.classList.remove("active"));
}

});