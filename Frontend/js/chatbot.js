const chatBody = document.querySelector('.chat-body');
const messageInput = document.querySelector('.message-input');
const sendMessageButton = document.querySelector('#send-message');
const chatbotToggler = document.querySelector('#chatbot-toggler');
const closeChatbotButton = document.querySelector('#close-chatbot');

// communication securise avec Gemini API
const API_URL = '../Backend/api/chatbot/chatbot_proxy.php';

const userData = {
    message: null
}

// chat history -> context
const chatHistory = [
    {
        role: "assistant",
        parts: [{ text: `We are SmartBite, a restauration company users can book a table, look at our menu, and give us a rating throught this website.` }],
    },
];

// menu data bdd -> context chatbot
const fetchRestaurantContext = async () => {
    try {
        const response = await fetch('../Backend/api/menu/fetch_Menu_Items.php');
        if (!response.ok) throw new Error('Failed to fetch menu');

        const menuItems = await response.json();

        // menu items readable string for the AI
        let menuContext = "Here is our current menu:\n";

        // group by category to make it structured
        const categories = [...new Set(menuItems.map(item => item.category))];
        categories.forEach(category => {
            menuContext += `- **${category}**:\n`;
            const itemsInCategory = menuItems.filter(item => item.category === category);
            itemsInCategory.forEach(item => {
                menuContext += `  - ${item.name}: ${item.description} ($${item.price})\n`;
            });
        });

        // + context to the prompt
        chatHistory[0].parts[0].text += `\n\n${menuContext}`;
    } catch (error) {
        console.error("Error loading restaurant context:", error);
    }
};

// init context on load
fetchRestaurantContext();
const initialHeight = messageInput.scrollHeight;

const createMessageElement = (content, ...classes) => {
    const div = document.createElement('div');
    div.classList.add('message', ...classes);
    div.innerHTML = content;
    return div;
}

const generateBotResponse = async (incomingMessageDiv) => {
    const messageElement = incomingMessageDiv.querySelector('.message-text');

    // + user message to chat history for context
    chatHistory.push({
        role: 'user',
        parts: [{ text: `Using the details provided above, please address this query: ${userData.message}` }]
    });

    // API request options
    const requestOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            contents: chatHistory

        })
    }

    try {
        // bot response from the API
        const response = await fetch(API_URL, requestOptions);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error.message);

        // bot response text from the API response
        const apiResponseText = data.candidates[0].content.parts[0].text.replace(/\*\*(.*?)\*\*/g, '$1').trim();
        messageElement.innerText = apiResponseText;

        // + bot response to chat history for context
        chatHistory.push({
            role: 'assistant',
            parts: [{ text: apiResponseText }]
        });
    } catch (error) {
        console.log(error);
        /*
        console.error('Error fetching bot response:', error);
        console.error('Error generating bot response:', error);*/
        messageElement.innerText = error.message;
        messageElement.style.color = "rgb(220,53,69)";
    } finally {
        // stop animation + scroll to bottom
        incomingMessageDiv.classList.remove('thinking');
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
    }
}

const handleOutgoingMessage = (e) => {
    // empeche rechargement de page
    e.preventDefault();
    userData.message = messageInput.value.trim();
    messageInput.value = '';
    messageInput.dispatchEvent(new Event('input')); // Trigger input event to adjust height and border radius

    // display user message
    const messageContent = `<div class="message-text"></div>`;

    const outgoingMessageDiv = createMessageElement(messageContent, 'user-message');
    // make sure the text is a text and not html to prevent xss
    outgoingMessageDiv.querySelector('.message-text').innerText = userData.message;
    chatBody.appendChild(outgoingMessageDiv);
    chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });

    // show thinking indicator au moins 600ms
    setTimeout(() => {
        const messageContent = `<i class="fa-solid fa-robot bot-avatar"></i>
                        <div class="message-text">
                            <div class="thinking-indicator">
                                <span class="dot"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </div>
                        </div>`;

        const incomingMessageDiv = createMessageElement(messageContent, 'bot-message', 'thinking');
        chatBody.appendChild(incomingMessageDiv);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
        generateBotResponse(incomingMessageDiv);
    }, 600);
}


// sending message avec enter
messageInput.addEventListener('keydown', (e) => {
    const userMessage = e.target.value.trim();
    if (e.key === 'Enter' && !e.shiftKey && userMessage !== '' && window.innerWidth > 400) {
        handleOutgoingMessage(e);
    }
});

//input hauteur dynamique
messageInput.addEventListener('input', () => {
    messageInput.style.height = `${initialHeight}px`; // reset to initial height
    messageInput.style.height = `${messageInput.scrollHeight}px`; // adjust height to fit content
    const chatBodyEl = document.querySelector('.chat-body');
    chatBodyEl.style.borderRadius = messageInput.scrollHeight > initialHeight ? '15px' : '32px'; // adjust border radius when input expands
    // margin class si textarea > 80px
    if (messageInput.scrollHeight > 80) {
        messageInput.classList.add('tall-textarea');
        chatBodyEl.classList.add('input-overlap-padding');
    } else {
        messageInput.classList.remove('tall-textarea');
        chatBodyEl.classList.remove('input-overlap-padding');
    }
});


// Initialize emoji picker (create first, then append once)
const picker = new EmojiMart.Picker({
    theme: 'auto',
    skinTonePosition: 'none',
    prewiewPosition: 'none',
    onEmojiSelect: (emoji) => {
        const { selectionStart: start, selectionEnd: end } = messageInput;
        messageInput.setRangeText(emoji.native, start, end, 'end');
        messageInput.focus();
    }
});

const chatForm = document.querySelector('.chat-form');
chatForm.appendChild(picker);

const emojiButton = document.getElementById('emoji-picker');
emojiButton.addEventListener('click', (e) => {
    // empeche rechargement de page
    e.preventDefault();
    e.stopPropagation(); // Prevent event from bubbling to document
    document.body.classList.toggle('show-emoji-picker');
});

// hide emoji picker when clicking outside
document.addEventListener('click', (e) => {
    if (!chatForm.contains(e.target) && !picker.contains(e.target)) {
        document.body.classList.remove('show-emoji-picker');
    }
});



sendMessageButton.addEventListener('click', (e) => handleOutgoingMessage(e));

chatbotToggler.addEventListener('click', () => document.body.classList.toggle('show-chatbot'));
closeChatbotButton.addEventListener('click', () => document.body.classList.remove('show-chatbot'));