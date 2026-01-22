function openChatModal(orderId, riderId, riderName) {
    // Show modal
    const modal = document.getElementById('chatModal');
    modal.style.display = 'block';
    
    // Set modal details
    document.getElementById('chatOrderId').value = orderId;
    document.getElementById('chatRiderId').value = riderId;
    document.getElementById('riderNameDisplay').textContent = riderName;

    // Load existing messages
    fetchMessages(orderId);
}

function fetchChatMessages(orderId) {
    fetch('chat_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_messages',
            order_id: orderId
        })
    })
    .then(response => response.json())  // Parse the JSON response from chat_handler.php
    .then(messages => {
        const messagesContainer = document.getElementById('chatMessages');
        messagesContainer.innerHTML = ''; // Clear existing messages

        // Iterate through the fetched messages
        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message');

            // Display the sender's name correctly
            messageDiv.innerHTML = `
                <strong>${message.sender_name}</strong>  <!-- Here we correctly use sender_name -->
                <p>${message.message}</p>
                <small>${new Date(message.timestamp).toLocaleString()}</small>
            `;

            messagesContainer.appendChild(messageDiv);
        });

        // Scroll to the bottom of the chat after appending the messages
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    })
    .catch(error => {
        console.error('Error fetching chat messages:', error);
    });
}
