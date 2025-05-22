<script>
    //================THEME CHANGER =================

    function toggleTheme() {
        const body = document.body;
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        const themeIcon = document.querySelector('.theme-icon');

        // Update theme
        body.setAttribute('data-theme', newTheme);
        document.querySelector('nav').setAttribute('data-bs-theme', newTheme);

        // Update icon
        themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';

        // Save preference
        localStorage.setItem('theme', newTheme);

        // Force redraw for smooth transition
        document.querySelector('nav').style.display = 'none';
        document.querySelector('nav').offsetHeight;
        document.querySelector('nav').style.display = 'flex';
    }

    // Initialize theme
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const themeIcon = document.querySelector('.theme-icon');

        document.body.setAttribute('data-theme', savedTheme);
        document.querySelector('nav').setAttribute('data-bs-theme', savedTheme);
        themeIcon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';
    });
















    
document.querySelectorAll('.edit-post').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const postDiv = document.getElementById(`post-${postId}`);
        const contentDiv = postDiv.querySelector('.post-content');
        const mediaContainer = postDiv.querySelector('.post-media');

        // Collect existing media items for editing
        const mediaItems = Array.from(mediaContainer?.querySelectorAll('[data-media-id]') || []).map(item => {
            const mediaElement = item.querySelector('img, video');
            return {
                id: item.dataset.mediaId,
                type: mediaElement.tagName.toLowerCase(),
                src: mediaElement.src || mediaElement.querySelector('source')?.src
            };
        });

        // Create edit form with hidden edit_post input for backend detection
        const form = document.createElement('form');
        form.className = 'edit-post-form';
        form.enctype = "multipart/form-data"; // important for file upload
        form.innerHTML = `
            <input type="hidden" name="edit_post" value="1">
            <div class="post-content-edit">
                <textarea name="content" rows="5" style="width: 100%;">${contentDiv.textContent.trim()}</textarea>

                <div class="edit-media-grid" style="display:flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    ${mediaItems.map(media => `
                        <div class="edit-media-item" style="position: relative; width: 150px;">
                            ${media.type === 'img' ? 
                                `<img src="${media.src}" class="edit-media-preview" style="width:100%; height: auto; border-radius: 5px;">` : 
                                `<video class="edit-media-preview" controls style="width:100%; border-radius: 5px;">
                                    <source src="${media.src}">
                                </video>`}
                            <label style="display:block; margin-top: 5px; cursor: pointer; font-size: 0.9em;">
                                <input type="checkbox" name="remove_media[]" value="${parseInt(media.id)}"> Remove
                            </label>
                        </div>
                    `).join('')}
                </div>

                <div class="new-media-upload" style="margin-top: 10px;">
                    <label for="new-media-files">Add new media:</label>
                    <input id="new-media-files" type="file" name="new_media[]" multiple accept="image/*,video/*" style="display: block; margin-top: 5px;">
                </div>

                <div class="form-controls" style="margin-top: 15px;">
                    <button type="submit" style="padding: 8px 15px;">Save</button>
                    <button type="button" class="cancel-edit" style="padding: 8px 15px; margin-left: 10px;">Cancel</button>
                </div>
            </div>
        `;

        // Hide original media container to avoid confusion
        if (mediaContainer) mediaContainer.style.display = 'none';

        // Replace content div with form
        contentDiv.replaceWith(form);

        // Cancel edit handler restores original content & media visibility
        form.querySelector('.cancel-edit').addEventListener('click', () => {
            form.replaceWith(contentDiv);
            if (mediaContainer) mediaContainer.style.display = 'flex';
        });

        // Submit form via AJAX with files and data
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            formData.append('post_id', postId);

            // Log form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            try {
                const response = await fetch('ajax/edit_post.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message || 'Post updated successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Update error:', error);
                alert('An error occurred while updating the post.');
            }
        });
    });
});






































    
    /*=======================================
             DELETE POST HANDLING
    =========================================*/
    document.querySelectorAll('.delete-post').forEach(button => {
    button.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to delete this post?')) return;

        const postId = this.dataset.postId;
        const csrfToken = this.dataset.csrf;

        try {
            const response = await fetch('ajax/delete_post.php', {  // Specific endpoint
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    delete_post: 1,
                    post_id: postId,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to delete post');
            }

            // Remove post and check if container is empty
            const postElement = document.getElementById(`post-${postId}`);
            postElement.remove();
            
            const container = document.getElementById('posts-container');
            if (container.children.length === 0) {
                container.innerHTML = `<div class="alert alert-info">No posts found</div>`;
            }

        } catch (error) {
            console.error('Delete error:', error);
            alert(error.message);
        }
    });
});


    //========== FUNCTION TO CREATE COMMENT ELEMENT ============//




    // ===================================
    //    ===== POST LIKES ============
    //  ==================================


    document.addEventListener('click', async (e) => {
        if (e.target.closest('.like-post')) {
            const button = e.target.closest('.like-post');
            const postId = button.dataset.postId;

            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Please login to like posts');
                return;
            <?php endif; ?>

            // Assuming you've added the data-csrf-token to the .container element
            const csrfTokenElement = document.querySelector('.container');
            const csrfToken = csrfTokenElement ? csrfTokenElement.dataset.csrfToken : null;

            if (!csrfToken) {
                console.error('CSRF token not found in data-csrf-token attribute');
                alert('CSRF token is missing. Please reload the page.');
                return;
            }

            const icon = button.querySelector('i');

            // Show loading state (optional, you can remove this if you don't want any loading indication)
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

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
                    // Update button appearance
                    if (data.has_liked) {
                        button.classList.add('btn-primary');
                        button.classList.remove('btn-outline-primary');
                        if (icon) {
                            icon.classList.add('bi-hand-thumbs-up-fill');
                            icon.classList.remove('bi-hand-thumbs-up');
                        }
                        button.innerHTML = '<i class="bi bi-hand-thumbs-up-fill"></i> Unlike';

                    } else {
                        button.classList.add('btn-outline-primary');
                        button.classList.remove('btn-primary');
                        if (icon) {
                            icon.classList.add('bi-hand-thumbs-up');
                            icon.classList.remove('bi-hand-thumbs-up-fill');
                        }
                        button.innerHTML = '<i class="bi bi-hand-thumbs-up"></i> Like';
                    }
                } else {
                    alert(data.message || 'Error updating like');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update like');
            } finally {
                button.disabled = false;
            }
        }
    });



    //===================================
    // COMMENT HANDLER
    //==================================

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
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
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



    //===========================
    // DELETE COMMENT
    //==============================
    document.addEventListener('DOMContentLoaded', function() {
        // Event Delegation for dynamic elements
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-comment');
            if (!deleteBtn) return;

            const commentId = deleteBtn.dataset.commentId;
            const csrfToken = deleteBtn.dataset.csrfToken;

            deleteComment(commentId, csrfToken);
        });
    });

    async function deleteComment(commentId, csrfToken) {
        if (!confirm('Are you sure you want to delete this comment?')) return;

        try {
            const response = await fetch('ajax/delete_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    comment_id: commentId,
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete comment');
            }

            if (data.success) {
                // Remove the comment container
                const commentElement = document.getElementById(`comment-${commentId}`);
                if (commentElement) {
                    commentElement.remove();
                } else {
                    console.warn('Comment element not found, refreshing page');
                    location.reload();
                }
            } else {
                alert(data.message || 'Failed to delete comment');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while deleting the comment');
        }
    }



    $(document).ready(function() {
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>'; // CSRF token from PHP

        // =========================================================================
        //  Edit Comment Functionality
        // =========================================================================
        $(document).on('click', '.edit-comment', function() {
            const commentId = $(this).data('comment-id');

            // More precise targeting - goes directly to the comment bubble content
            const commentContent = $(`#main-comment-${commentId}`)
                .find('> .d-flex > .flex-grow-1 > .comment-bubble > .comment-content');

            const currentContent = commentContent.text().trim();

            // Create edit interface
            const editHtml = `
        <textarea class="form-control edit-comment-field mb-2" rows="3">${currentContent}</textarea>
        <div class="edit-actions d-flex gap-2 mt-2">
            <button class="btn btn-sm btn-primary save-edit" data-comment-id="${commentId}">
                <i class="fas fa-save"></i> Save
            </button>
            <button class="btn btn-sm btn-outline-secondary cancel-edit" data-comment-id="${commentId}">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    `;

            // Store original content and replace with edit interface
            commentContent.data('original', currentContent).html(editHtml);

            // Hide other action buttons during edit
            $(this).closest('.comment-actions').find('button').hide();
        });

        function nl2br(str) {
            return str.replace(/\n/g, '<br>');
        }

        function htmlspecialchars(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        $(document).on('click', '.save-edit', function() {
            const saveButton = $(this);
            const commentId = saveButton.data('comment-id');
            const commentContainer = $(`#main-comment-${commentId}`);
            const editField = commentContainer.find('.edit-comment-field');
            const newContent = editField.val().trim();

            if (!newContent) {
                alert('Comment cannot be empty');
                return;
            }

            const originalButtonHtml = saveButton.html();
            saveButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: 'ajax/edit_comment.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    comment_id: commentId,
                    content: newContent,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        // VERY SPECIFIC selector to target only the main comment content
                        commentContainer.find('> .d-flex > .flex-grow-1 > .comment-bubble > .comment-content')
                            .html(nl2br(htmlspecialchars(newContent)))
                            .append(' <span class="text-muted small">(edited)</span>');

                        // Clean up edit interface
                        commentContainer.find('.edit-comment-field, .edit-actions').remove();
                        commentContainer.find('.comment-actions button').show();
                    } else {
                        alert(response.message || 'Failed to update comment');
                        // Re-enable button if failed
                        saveButton.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
                    }
                },
                complete: function(xhr) {
                    saveButton.prop('disabled', false).html(originalButtonHtml);
                    if (!xhr.responseJSON || xhr.responseJSON.success) {
                        commentContainer.find('.comment-actions button').show();
                        commentContainer.find('.edit-comment-field, .edit-actions').remove();
                    }
                }
            });
        });


        $(document).on('click', '.cancel-edit', function() {
            const commentId = $(this).data('comment-id');
            const commentContainer = $(`#comment-${commentId}`);

            // Restore original content
            const originalContent = commentContainer.find('.comment-content').data('original');
            commentContainer.find('.comment-bubble .comment-content')
                .html(nl2br(htmlspecialchars(originalContent)));

            // Restore action buttons
            commentContainer.find('.comment-actions button').show();

            // Remove edit interface
            commentContainer.find('.edit-comment-field, .edit-actions').remove();
        });
    });







    // =========================
    //      SHARE BUTTON
    //==========================
    // Initialize modal when DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        const shareModal = document.querySelector('.share-modal-overlay');

        // Close modal when clicking X or outside
        document.querySelector('.share-modal-close').addEventListener('click', closeShareModal);
        shareModal.addEventListener('click', function(e) {
            if (e.target === shareModal) {
                closeShareModal();
            }
        });

        // Handle all share buttons
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const type = this.getAttribute('data-type') || 'item';
                openShareModal(url, type);
            });
        });
    });

    function openShareModal(url, type) {
        const shareModal = document.querySelector('.share-modal-overlay');
        const urlInput = shareModal.querySelector('.share-url');
        const typeDisplay = shareModal.querySelector('.share-item-type');

        urlInput.value = url;
        typeDisplay.textContent = type;

        // Show modal with animation
        shareModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeShareModal() {
        const shareModal = document.querySelector('.share-modal-overlay');
        shareModal.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable scrolling
    }

    // Copy URL function
    function copyShareUrl() {
        const urlInput = document.querySelector('.share-url');
        urlInput.select();
        document.execCommand('copy');

        // Show feedback
        const copyBtn = document.querySelector('.copy-url-btn');
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        }, 2000);
    }

    //=======LOAD MORE POSTS =====

    $(document).ready(function() {
        // Load more posts functionality
        $('.load-more-btn').click(function() {
            const button = $(this);
            const offset = button.data('offset');
            const limit = button.data('limit');
            const totalPosts = button.data('total-posts');

            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

            $.ajax({
                url: 'index.php',
                url: 'post.php',
                type: 'GET',
                data: {
                    offset: offset,
                    limit: limit
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.trim() === '') {
                        button.hide();
                        $('.no-more-posts-message').show();
                    } else {
                        $('.posts-container').append(response);
                        const newOffset = offset + limit;
                        button.data('offset', newOffset);

                        if (newOffset >= totalPosts) {
                            button.hide();
                            $('.no-more-posts-message').show();
                        } else {
                            button.prop('disabled', false).text('See More...');
                        }

                        // Reinitialize components for new posts
                        initPostComponents();
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Error! Try again.');
                }
            });
        });

        // Initialize post components (dropdowns, etc.)
        function initPostComponents() {
            // Initialize Bootstrap dropdowns
            $('[data-bs-toggle="dropdown"]').dropdown();

            // Reattach like button handlers
            $('.like-post').off('click').on('click', function() {
                const button = $(this);
                const postId = button.data('post-id');

                $.post('ajax/like_post.php', {
                    post_id: postId,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                }, function(response) {
                    // Handle like response
                });
            });

            // Reattach other event handlers as needed
        }

        // Initial initialization
        initPostComponents();
    });

    // ============Hightlight post ============
 document.addEventListener('DOMContentLoaded', function() {
            const lightbox = GLightbox({
                selector: '.glightbox'
            });
        });

        function openLightbox(galleryId) {
            const lightbox = GLightbox({
                selector: `[data-gallery="${galleryId}"]`
            });
            lightbox.open();
        }
        // Helper functions for reactions
        function getReactionIcon(reaction) {
            const icons = {
                'like': 'bi-hand-thumbs-up-fill',
                'love': 'bi-heart-fill',
                'haha': 'bi-emoji-laughing-fill',
                'wow': 'bi-emoji-surprise-fill',
                'sad': 'bi-emoji-frown-fill',
                'angry': 'bi-emoji-angry-fill'
            };
            return icons[reaction] || 'bi-hand-thumbs-up-fill';
        }

        function getReactionText(reaction) {
            const texts = {
                'like': 'Like',
                'love': 'Love',
                'haha': 'Haha',
                'wow': 'Wow',
                'sad': 'Sad',
                'angry': 'Angry'
            };
            return texts[reaction] || 'Like';
        }
    //============ View more comments =========
</script>