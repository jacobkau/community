 // =====================
    // Proper Post Like Handler
    //========================

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '';

    document.addEventListener('click', async (e) => {
        const likeButton = e.target.closest('.like-post');
        if (!likeButton) return;

        const postId = likeButton.dataset.postId;
        const likeCountSpan = likeButton.querySelector('.like-count');
        const icon = likeButton.querySelector('i');

        // Handle unauthenticated
        if (likeButton.hasAttribute('disabled')) {
            alert('Please login to like posts');
            return;
        }

        // Show loading spinner
        const originalHTML = likeButton.innerHTML;
        likeButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        likeButton.disabled = true;

        try {
            const response = await fetch('ajax/like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    post_id: postId,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (data.success) {
                // Update count
                likeCountSpan.textContent = data.like_count;

                // Update button style and icon
                if (data.has_liked) {
                    likeButton.classList.add('btn-primary');
                    likeButton.classList.remove('btn-outline-primary');
                    icon.classList.add('bi-hand-thumbs-up-fill');
                    icon.classList.remove('bi-hand-thumbs-up');
                    likeButton.dataset.hasLiked = '1';
                } else {
                    likeButton.classList.remove('btn-primary');
                    likeButton.classList.add('btn-outline-primary');
                    icon.classList.remove('bi-hand-thumbs-up-fill');
                    icon.classList.add('bi-hand-thumbs-up');
                    likeButton.dataset.hasLiked = '0';
                }
            } else {
                alert(data.message || 'Error updating like');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Failed to update like.');
        } finally {
            likeButton.innerHTML = originalHTML;
            likeButton.disabled = false;

        }
    });

    //=====================

    //=====================

        // Updated Comment Handler
        $(document).on('submit', '.comment-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const postId = form.data('post-id');
            const commentInput = form.find('textarea[name="comment"]');
            const commentsContainer = form.closest('.comments-section').find('.comments');
    
            $.ajax({
                url: 'ajax/add_comment.php',
                method: 'POST',
                data: {
                    post_id: postId,
                    comment: commentInput.val(),
                    csrf_token: csrfToken
                },
                dataType: 'json',
                beforeSend: () => {
                    form.find('button').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm"></span>');
                },
                success: (data) => {
                    if (data.success) {
                        // Add new comment at the bottom
                        const commentHtml = `
                        <div class="comment mb-2">
                            <strong>${data.username}:</strong>
                            ${$('<div/>').text(data.comment).html()} <!-- Proper HTML escaping -->
                            <small class="text-muted">${data.created_at}</small>
                        </div>`;
                        commentsContainer.append(commentHtml);
    
                        // Update comment count
                        form.closest('.post').find('.comment-count span').text(data.comment_count);
    
                        // Clear input
                        commentInput.val('');
                    }
                },
                error: (xhr) => {
                    alert(`Error: ${xhr.statusText}`);
                },
                complete: () => {
                    form.find('button').prop('disabled', false).text('Post');
                }
            });
        });
            //======================
    //COMMENT SECTION//
    //======================

    document.addEventListener('DOMContentLoaded', function() {
        // Event Delegation for dynamic elements
        document.querySelector('.comments-section').addEventListener('click', function(e) {
            const target = e.target.closest('button');
            if (!target) return;

            const commentId = target.dataset.commentId;

            // Handle Like button
            if (target.classList.contains('like-comment')) {
                handleLikeComment(target, commentId);
            }
            // Handle Delete button
            else if (target.classList.contains('delete-comment')) {
                deleteComment(commentId);
            }
        });
    });

    // Like Comment Handler
    async function handleLikeComment(button, commentId) {
        try {
            const response = await fetch('ajax/like_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    comment_id: commentId
                })
            });

            const data = await response.json();
            if (data.success) {
                const likeCount = button.querySelector('.like-count');
                likeCount.textContent = data.like_count;
                button.classList.toggle('btn-primary');
                button.classList.toggle('btn-outline-primary');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Delete Comment
    async function deleteComment(commentId) {
        if (!confirm('Are you sure you want to delete this comment?')) return;

        try {
            const response = await fetch('ajax/delete_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    comment_id: commentId
                })
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById(`comment-${commentId}`).remove();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    

    //========================
    //========================
    //Reply comment
    //======================
    //========================
    // Reply Button Handler
    document.addEventListener('click', function(e) {
        const replyBtn = e.target.closest('.reply-comment');
        if (!replyBtn) return;

        const commentId = replyBtn.dataset.commentId;
        const commentDiv = document.getElementById(`comment-${commentId}`);

        // Remove existing form if present
        const existingForm = commentDiv.querySelector('.reply-form');
        if (existingForm) {
            existingForm.remove();
            return;
        }

        // Create reply form
        const form = document.createElement('form');
        form.className = 'reply-form mt-2';
        form.innerHTML = `
        <div class="input-group mb-2">
            <textarea class="form-control" placeholder="Write a reply..." required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Post</button>
            <button type="button" class="btn btn-secondary btn-sm cancel-reply">Cancel</button>
        </div>
    `;

        // Cancel button handler
        form.querySelector('.cancel-reply').addEventListener('click', () => form.remove());

        // Submit handler
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = form.querySelector('textarea').value.trim();

            try {
                const response = await fetch('ajax/add_reply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        content: content,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    form.remove();

                    // Create reply element
                    const replyHtml = `
                    <div class="reply mb-2 ms-3" data-reply-id="${data.reply.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${data.reply.username}:</strong>
                                <span>${data.reply.content}</span>
                            </div>
                            <small class="text-muted">${data.reply.created_at}</small>
                        </div>
                    </div>
                `;

                    // Find or create replies container
                    let repliesContainer = commentDiv.querySelector('.replies');
                    if (!repliesContainer) {
                        repliesContainer = document.createElement('div');
                        repliesContainer.className = 'replies ms-3';
                        commentDiv.appendChild(repliesContainer);
                    }

                    repliesContainer.insertAdjacentHTML('beforeend', replyHtml);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to post reply');
            }
        });

        // Insert form
        const actionsDiv = commentDiv.querySelector('.comment-actions');
        actionsDiv.after(form);
    });
