<?php
class FAQChatbot {
    // FAQ database stored as associative array
    private $faq_database = [
        // Predefined questions and answers with multiple keyword variations
        'greeting' => [
            'keywords' => ['hi', 'hello', 'hey', 'greetings'],
            'responses' => [
                'Hello! How can I help you today?',
                'Hi there! What questions do you have?',
                'Welcome! I\'m ready to assist you.'
            ]
        ],
        'purpose' => [
            'keywords' => ['what do you do', 'purpose', 'help', 'function'],
            'responses' => [
                'I\'m an FAQ chatbot designed to answer frequently asked questions quickly and easily.',
                'My purpose is to provide instant answers to common queries.'
            ]
        ],
        'hours' => [
            'keywords' => ['open', 'hours', 'time', 'available'],
            'responses' => [
                'We are available 24/7 online!',
                'Our support is open Monday to Friday, 9 AM to 5 PM.'
            ]
        ],
        'contact' => [
            'keywords' => ['contact', 'email', 'phone', 'reach'],
            'responses' => [
                'You can reach us at support@example.com or call 1-800-SUPPORT',
                'Contact our support team at support@example.com'
            ]
        ]
    ];

    // Process user input and return appropriate response
    public function processMessage($userInput) {
        // Convert input to lowercase for easier matching
        $userInput = strtolower(trim($userInput));

        // Check for direct matches or keyword matches
        foreach ($this->faq_database as $category => $data) {
            // Check keyword matches
            foreach ($data['keywords'] as $keyword) {
                if (strpos($userInput, $keyword) !== false) {
                    // Return random response from matching category
                    return $this->getRandomResponse($data['responses']);
                }
            }
        }

        // Fallback response if no match found
        return $this->getFallbackResponse($userInput);
    }

    // Get a random response from an array of responses
    private function getRandomResponse($responses) {
        return $responses[array_rand($responses)];
    }

    // Handle unrecognized queries
    private function getFallbackResponse($userInput) {
        $fallbackResponses = [
            'I\'m not sure I understand. Could you rephrase that?',
            'Sorry, I couldn\'t find a specific answer to that query.',
            'Could you be more specific? I\'m having trouble understanding.',
            "I don't have information about '{$userInput}'. Try asking something else."
        ];

        return $this->getRandomResponse($fallbackResponses);
    }

    // Method to add new FAQ entries dynamically
    public function addFAQEntry($category, $keywords, $responses) {
        $this->faq_database[$category] = [
            'keywords' => $keywords,
            'responses' => $responses
        ];
    }
}

// Chatbot interaction handler
class ChatbotController {
    private $chatbot;

    public function __construct() {
        $this->chatbot = new FAQChatbot();
    }

    public function handleRequest() {
        // Handle AJAX requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userMessage = $_POST['message'] ?? '';
            
            $response = [
                'message' => $this->chatbot->processMessage($userMessage),
                'timestamp' => date('H:i:s')
            ];

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

// Initialize and handle request
$controller = new ChatbotController();
$controller->handleRequest();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ Chatbot</title>
    <style>
        #chatbot-container {
            max-width: 400px;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        #chat-messages {
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .user-message, .bot-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .user-message {
            background-color: #e6f2ff;
            text-align: right;
        }
        .bot-message {
            background-color: #f0f0f0;
            text-align: left;
        }
        #message-input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div id="chatbot-container">
        <div id="chat-messages"></div>
        <input type="text" id="message-input" placeholder="Type your message...">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('message-input');
            const chatMessages = document.getElementById('chat-messages');

            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const userMessage = messageInput.value.trim();
                    if (userMessage) {
                        // Display user message
                        const userMessageEl = document.createElement('div');
                        userMessageEl.classList.add('user-message');
                        userMessageEl.textContent = userMessage;
                        chatMessages.appendChild(userMessageEl);

                        // Send message to server
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `message=${encodeURIComponent(userMessage)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Display bot response
                            const botMessageEl = document.createElement('div');
                            botMessageEl.classList.add('bot-message');
                            botMessageEl.textContent = data.message;
                            chatMessages.appendChild(botMessageEl);

                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        });

                        // Clear input
                        messageInput.value = '';
                    }
                }
            });
        });
    </script>
</body>
</html>