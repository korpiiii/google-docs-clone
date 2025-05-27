$(document).ready(function() {
    // Auto-save functionality
    let saveTimeout;
    const saveDelay = 3000; // 3 seconds

    editor.isReady.then(() => {
        editor.save().then(savedData => {
            $('#content').val(JSON.stringify(savedData));
        });
    });

    editor.on('change', function() {
        // Clear previous timeout
        clearTimeout(saveTimeout);

        // Update save status
        $('#save-status').removeClass('bg-success').addClass('bg-warning').text('Saving...');

        // Set new timeout
        saveTimeout = setTimeout(function() {
            editor.save().then(savedData => {
                const content = JSON.stringify(savedData);
                $('#content').val(content);

                // Send to server
                const documentId = $('#document_id').val();

                $.ajax({
                    url: '../ajax/autosave.php',
                    method: 'POST',
                    data: {
                        document_id: documentId,
                        content: content
                    },
                    success: function(response) {
                        $('#save-status').removeClass('bg-warning').addClass('bg-success').text('Saved');
                    },
                    error: function(xhr) {
                        $('#save-status').removeClass('bg-warning bg-success').addClass('bg-danger').text('Failed to save');
                        console.error('Auto-save failed:', xhr.responseText);
                    }
                });
            });
        }, saveDelay);
    });

    // User search functionality
    $('#search-user').on('input', function() {
        const searchTerm = $(this).val();

        if (searchTerm.length < 2) {
            $('#search-results').empty();
            return;
        }

        $.ajax({
            url: '../ajax/search_users.php',
            method: 'GET',
            data: { q: searchTerm },
            success: function(users) {
                const $results = $('#search-results');
                $results.empty();

                if (users.length === 0) {
                    $results.append('<div class="text-muted">No users found</div>');
                    return;
                }

                users.forEach(user => {
                    $results.append(`
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>${user.username}</span>
                            <button class="btn btn-sm btn-primary add-collaborator"
                                    data-user-id="${user.id}">
                                Add
                            </button>
                        </div>
                    `);
                });
            }
        });
    });

    // Add collaborator
    $(document).on('click', '.add-collaborator', function() {
        const userId = $(this).data('user-id');
        const documentId = $('#document_id').val();

        $.ajax({
            url: '../ajax/add_user_to_doc.php',
            method: 'POST',
            data: {
                document_id: documentId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    // Add to collaborators list
                    $('#collaborators-list').append(`
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ${response.user.username}
                            <button class="btn btn-sm btn-danger remove-collaborator"
                                    data-user-id="${response.user.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                    `);

                    // Clear search
                    $('#search-user').val('');
                    $('#search-results').empty();
                }
            },
            error: function(xhr) {
                console.error('Failed to add collaborator:', xhr.responseText);
                alert('Failed to add collaborator');
            }
        });
    });

    // Remove collaborator
    $(document).on('click', '.remove-collaborator', function() {
        if (!confirm('Are you sure you want to remove this collaborator?')) {
            return;
        }

        const userId = $(this).data('user-id');
        const documentId = $('#document_id').val();

        $.ajax({
            url: '../ajax/remove_user_from_doc.php',
            method: 'POST',
            data: {
                document_id: documentId,
                user_id: userId
            },
            success: function() {
                $(`button[data-user-id="${userId}"]`).closest('li').remove();
            },
            error: function(xhr) {
                console.error('Failed to remove collaborator:', xhr.responseText);
                alert('Failed to remove collaborator');
            }
        });
    });

    // Chat functionality
    function loadMessages() {
        const documentId = $('#document_id').val();

        $.ajax({
            url: '../ajax/get_messages.php',
            method: 'GET',
            data: { document_id: documentId },
            success: function(messages) {
                const $chat = $('#chat-messages');
                $chat.empty();

                messages.forEach(message => {
                    $chat.append(`
                        <div class="mb-2">
                            <strong>${message.username}</strong>
                            <small class="text-muted">${message.created_at}</small><br>
                            ${message.message}
                        </div>
                    `);
                });

                // Scroll to bottom
                $chat.scrollTop($chat[0].scrollHeight);
            }
        });
    }

    // Load messages initially and then every 5 seconds
    loadMessages();
    setInterval(loadMessages, 5000);

    // Send message
    $('#send-message').click(function() {
        sendMessage();
    });

    $('#chat-message').keypress(function(e) {
        if (e.which === 13) { // Enter key
            sendMessage();
        }
    });

    function sendMessage() {
        const message = $('#chat-message').val().trim();
        const documentId = $('#document_id').val();

        if (message === '') {
            return;
        }

        $.ajax({
            url: '../ajax/send_message.php',
            method: 'POST',
            data: {
                document_id: documentId,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    // Clear input
                    $('#chat-message').val('');

                    // Reload messages
                    loadMessages();
                }
            },
            error: function(xhr) {
                console.error('Failed to send message:', xhr.responseText);
                alert('Failed to send message');
            }
        });
    }
});
