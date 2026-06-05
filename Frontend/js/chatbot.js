const chatBody = document.querySelector('.chat-body');
const messageInput = document.querySelector('.message-input');
const sendMessageButton = document.querySelector('#send-message');
const chatbotToggler = document.querySelector('#chatbot-toggler');
const closeChatbotButton = document.querySelector('#close-chatbot');

// communication securise avec Gemini API
const API_URL = '../Backend/api/chatbot/chatbot_proxy.php';

const userData = {
    message: null
};

const SYSTEM_PROMPT_BASE = `## Role
You are the Menu Assistant for SmartBite, a fine-dining restaurant. Your goal is to help users find the perfect meal, check for allergens, and provide detailed descriptions based on our official menu. Users can also book a table, browse the menu, and leave reviews on this website.

## Guidelines
1. Recommendation: If a user is unsure what to order, ask whether they prefer light or heavy meals.
2. Recommendation: When a user asks for dietary-specific options (vegetarian, gluten-free, vegan, etc.), only recommend items that explicitly match those preferences in the menu data below.
3. Request: If a user asks for something not on the menu, politely inform them and suggest the closest available alternative.
4. Safety: If a user mentions an allergy, carefully cross-reference all listed ingredients before making a recommendation.
5. Safety: If a user asks about an allergen not explicitly listed in the menu data, do not guess. Say: "I am not certain about that ingredient — let me get a human server to double-check for your safety."
6. Safety: Do not provide nutritional or medical advice. Stick strictly to the provided menu descriptions.
7. Ratings: Only mention ratings if the user specifically asks for "popular" or "highly rated" items.
8. Tone: Always maintain a friendly, professional, and helpful tone. Encourage users to ask for more details about any menu item.
9. Format: Keep your responses concise and easy to read. Use bullet points or short paragraphs when listing multiple items.

## Menu Data
__MENU_PLACEHOLDER__`;

// chat history -> context (system prompt + model acknowledgment)
const chatHistory = [
    {
        role: 'user',
        parts: [{ text: SYSTEM_PROMPT_BASE }],
    },
    {
        role: 'model',
        parts: [{ text: "Understood! I'm ready to assist SmartBite guests with menu recommendations, allergen checks, and more. How can I help you today?" }],
    },
];

// menu data bdd -> inject into system prompt
const fetchRestaurantContext = async () => {
    try {
        const response = await fetch('../Backend/api/dashboard/fetch_Menu_Items.php');
        if (!response.ok) throw new Error('Failed to fetch menu items');

        const menuItems = await response.json();
        if (!Array.isArray(menuItems) || menuItems.length === 0) return;

        const grouped = {};
        menuItems.forEach((item) => {
            const cat = item.category || 'Other';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(item);
        });

        let menuContext = '\n\n## Live Menu Data\n';
        Object.keys(grouped).forEach((category) => {
            menuContext += `\n### ${category}\n`;
            grouped[category].forEach((item) => {
                menuContext += `* **${item.name}** — $${Number(item.price).toFixed(2)}\n`;
                if (item.description) menuContext += `  - Description: ${item.description}\n`;
                if (item.ingredients) menuContext += `  - Ingredients: ${item.ingredients}\n`;
            });
        });

        chatHistory[0].parts[0].text = SYSTEM_PROMPT_BASE.replace(
            '__MENU_PLACEHOLDER__',
            menuContext
        );
    } catch (error) {
        console.error('Error loading restaurant context:', error);
    }
};

const initialHeight = messageInput.scrollHeight;

const createMessageElement = (content, ...classes) => {
    const div = document.createElement('div');
    div.classList.add('message', ...classes);
    div.innerHTML = content;
    return div;
};

const renderBotMarkdown = (rawText) => {
    return rawText
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/^\* (.+)$/gm, '<li>$1</li>')
        .replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>')
        .replace(/\n/g, '<br>');
};

const generateBotResponse = async (incomingMessageDiv) => {
    const messageElement = incomingMessageDiv.querySelector('.message-text');

    // refresh menu in system prompt before every API call
    await fetchRestaurantContext();

    chatHistory.push({
        role: 'user',
        parts: [{ text: userData.message }],
    });

    const requestOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            contents: chatHistory,
        }),
    };

    try {
        const response = await fetch(API_URL, requestOptions);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error?.message || 'Request failed');

        const rawText = data.candidates[0].content.parts[0].text.trim();
        messageElement.innerHTML = renderBotMarkdown(rawText);

        chatHistory.push({
            role: 'model',
            parts: [{ text: rawText }],
        });
    } catch (error) {
        console.log(error);
        messageElement.innerText = error.message;
        messageElement.style.color = 'rgb(220,53,69)';
    } finally {
        incomingMessageDiv.classList.remove('thinking');
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
    }
};

const handleOutgoingMessage = (e) => {
    e.preventDefault();
    userData.message = messageInput.value.trim();
    messageInput.value = '';
    messageInput.dispatchEvent(new Event('input'));

    const messageContent = `<div class="message-text"></div>`;
    const outgoingMessageDiv = createMessageElement(messageContent, 'user-message');
    outgoingMessageDiv.querySelector('.message-text').innerText = userData.message;
    chatBody.appendChild(outgoingMessageDiv);
    chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });

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
};

messageInput.addEventListener('keydown', (e) => {
    const userMessage = e.target.value.trim();
    if (e.key === 'Enter' && !e.shiftKey && userMessage !== '' && window.innerWidth > 400) {
        handleOutgoingMessage(e);
    }
});

messageInput.addEventListener('input', () => {
    messageInput.style.height = `${initialHeight}px`;
    messageInput.style.height = `${messageInput.scrollHeight}px`;
    const chatBodyEl = document.querySelector('.chat-body');
    chatBodyEl.style.borderRadius = messageInput.scrollHeight > initialHeight ? '15px' : '32px';
    if (messageInput.scrollHeight > 80) {
        messageInput.classList.add('tall-textarea');
        chatBodyEl.classList.add('input-overlap-padding');
    } else {
        messageInput.classList.remove('tall-textarea');
        chatBodyEl.classList.remove('input-overlap-padding');
    }
});

const picker = new EmojiMart.Picker({
    theme: 'auto',
    skinTonePosition: 'none',
    prewiewPosition: 'none',
    onEmojiSelect: (emoji) => {
        const { selectionStart: start, selectionEnd: end } = messageInput;
        messageInput.setRangeText(emoji.native, start, end, 'end');
        messageInput.focus();
    },
});

const chatForm = document.querySelector('.chat-form');
chatForm.appendChild(picker);

const emojiButton = document.getElementById('emoji-picker');
emojiButton.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    document.body.classList.toggle('show-emoji-picker');
});

document.addEventListener('click', (e) => {
    if (!chatForm.contains(e.target) && !picker.contains(e.target)) {
        document.body.classList.remove('show-emoji-picker');
    }
});

sendMessageButton.addEventListener('click', (e) => handleOutgoingMessage(e));

chatbotToggler.addEventListener('click', () => document.body.classList.toggle('show-chatbot'));
closeChatbotButton.addEventListener('click', () => document.body.classList.remove('show-chatbot'));
