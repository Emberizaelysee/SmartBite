const chatBody = document.querySelector(".chat-body");
const messageInput = document.querySelector(".message-input");
const sendMessageButton = document.querySelector("#send-message");
const chatbotToggler = document.querySelector("#chatbot-toggler");
const closeChatbotButton = document.querySelector("#close-chatbot");
const chatForm = document.querySelector(".chat-form");
const emojiButton = document.getElementById("emoji-picker");

if (chatBody && messageInput && sendMessageButton && chatbotToggler && closeChatbotButton && chatForm && emojiButton) {
    
    /*sendMessageButton.addEventListener("click", handleOutgoingMessage);*/
    chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
    closeChatbotButton.addEventListener("click", () => document.body.classList.remove("show-chatbot"));
}
