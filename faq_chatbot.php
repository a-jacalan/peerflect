<?php
class FAQChatbot {
    // Expanded FAQ database with more detailed responses about PeerFlect
    private $faq_database = [
        'greeting' => [
            'keywords' => ['hi', 'hello', 'hey', 'greetings', 'yo', 'boss'],
            'responses' => [
                'Hello! Welcome to PeerFlect. How can I help you today?',
                'Hi there! I\'m your PeerFlect assistant. What can I help you with?',
                'Greetings! I\'m here to assist you with any questions about PeerFlect.'
            ]
        ],
        'about' => [
            'keywords' => ['what is', 'about', 'platform', 'peerflect'],
            'responses' => [
                'PeerFlect is a collaborative platform for computer networking reviews. It allows professors to share knowledge, review topics, and discuss networking technologies.',
                'We are a community-driven platform where networking professors and students can collaborate, share insights, and stay updated on the latest trends in computer networking.'
            ]
        ],
        'user_roles' => [
            'keywords' => ['roles', 'user types', 'contributor', 'user'],
            'responses' => [
                'PeerFlect has three main user roles: Guest Users (view content), Regular Users (view, react, and comment), and Contributors (post questions and answers).',
                'Our platform supports Guest Users, Regular Users, and Contributors, each with different levels of interaction and permissions.'
            ]
        ],
        'contribute' => [
            'keywords' => ['contribute', 'post', 'how to contribute', 'become contributor'],
            'responses' => [
                'To become a contributor, your school admin or program head must contact us for your school to be registered and make you a contributor. Contributors can create posts and earn points.',
                'Interested in contributing? Tell your school to collaborate on our platform and start sharing your networking knowledge!'
            ]
        ],
        'contact' => [
            'keywords' => ['contact', 'email', 'support', 'reach'],
            'responses' => [
                'You can reach us at peerflect@gmail.com for any inquiries or support.',
                'Our support contact is peerflect@gmail.com. We\'d love to hear from you!'
            ]
        ],
        'topics' => [
            'keywords' => ['topics', 'how to suggest', 'no topic'],
            'responses' => [
                'Only contributors can suggest topic.',
                'Suggest topic on the topic suggestion page.'
            ]
        ]
    ];

    public function processMessage($userInput) {
        $userInput = strtolower(trim($userInput));

        // Check for direct matches or keyword matches
        foreach ($this->faq_database as $category => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (strpos($userInput, $keyword) !== false) {
                    return $this->getRandomResponse($data['responses']);
                }
            }
        }

        return $this->getFallbackResponse($userInput);
    }

    private function getRandomResponse($responses) {
        return $responses[array_rand($responses)];
    }

    private function getFallbackResponse($userInput) {
        $fallbackResponses = [
            'I\'m not sure I understand. Could you rephrase that?',
            'Sorry, I couldn\'t find a specific answer to that query.',
            'Could you be more specific about your PeerFlect-related question?',
            "I don't have information about '{$userInput}'. Try contacting us at peerflect@gmail.com"
        ];

        return $this->getRandomResponse($fallbackResponses);
    }
}

// Chatbot interaction handler
$chatbot = new FAQChatbot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = $_POST['message'] ?? '';
    
    $response = [
        'message' => $chatbot->processMessage($userMessage),
        'timestamp' => date('H:i:s')
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}