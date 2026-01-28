<!DOCTYPE html>
<html>
<head>
    <title>Real-time Chat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@latest/dist/echo.js"></script>
    <style>
        #chat-box {
            height: 400px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .message {
            margin: 5px;
            padding: 8px;
            border-radius: 5px;
            background: #f0f0f0;
        }
        .my-message {
            background: #007bff;
            color: white;
            text-align: right;
        }
    </style>
</head>
<body>
    <div id="chat-box"></div>
    <input type="text" id="message-input" placeholder="Type your message...">
    <button onclick="sendMessage()">Send</button>
    
    <script>
        // Store session and device info
        const sessionId = "{{ $sessionId }}";
        const deviceId = "{{ $deviceId }}";
        
        // Initialize Pusher
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ env("PUSHER_APP_KEY") }}',
            cluster: 'ap3',
            forceTLS: false,
            wsHost: window.location.hostname,
            wsPort: 6001,
            disableStats: true
        });
        
        // Listen to channel for this session
        window.Echo.channel('chat.' + sessionId)
            .listen('MessageSent', (e) => {
                addMessageToChat(e.message, 'other');
            });
        
        // Load existing messages
        window.onload = function() {
            fetch(`/get-messages/${sessionId}`)
                .then(response => response.json())
                .then(messages => {
                    messages.forEach(msg => {
                        const isMine = msg.device_id === deviceId;
                        addMessageToChat(msg.message, isMine ? 'mine' : 'other');
                    });
                });
        };
        
        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (message) {
                // Add to chat immediately (optimistic update)
                addMessageToChat(message, 'mine');
                
                // Send to server
                fetch('/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: sessionId,
                        device_id: deviceId
                    })
                });
                
                input.value = '';
            }
        }
        
        function addMessageToChat(message, type) {
            const chatBox = document.getElementById('chat-box');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type === 'mine' ? 'my-message' : ''}`;
            messageDiv.textContent = message;
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        
        // Allow Enter key to send message
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>