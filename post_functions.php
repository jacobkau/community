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


    //=================================//
    // POST EDITING AND HANDLING EVENTS //
    //=================================// 
    document.querySelectorAll('.edit-post').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const postDiv = document.getElementById(`post-${postId}`);
            const content = postDiv.querySelector('.post-content').textContent;
            const image = postDiv.querySelector('.post-image img')?.src;

            // Create edit form
            const form = document.createElement('form');
            form.innerHTML = `
    <input type="hidden" name="edit_post" value="1">
    <input type="hidden" name="post_id" value="${postId}">
    <div class="mb-3">
        <textarea class="form-control" name="content" rows="3">${content.trim()}</textarea>
    </div>
    ${image ? `
    <div class="current-image mb-3">
        <img src="${image}" class="img-thumbnail" style="max-height: 200px;">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remove_image" id="remove-${postId}">
            <label class="form-check-label" for="remove-${postId}">Remove image</label>
        </div>
    </div>` : ''}
    
    ${video ? `
    <div class="current-video mb-3">
        <video controls class="img-thumbnail" style="max-height: 200px;">
            <source src="${video}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remove_video" id="remove-video-${postId}">
            <label class="form-check-label" for="remove-video-${postId}">Remove video</label>
        </div>
    </div>` : ''}

    <div class="mb-3">
        <input type="file" class="form-control" name="new_image" accept="image/*">
    </div>
    <div class="mb-3">
        <input type="file" class="form-control" name="new_video" accept="video/*">
    </div>
    
    <input type="hidden" name="csrf_token" value="${this.dataset.csrfToken}">
    <button type="submit" class="btn btn-primary">Save</button>
    <button type="button" class="btn btn-secondary cancel-edit">Cancel</button>
`;

            // Replace content with form
            postDiv.querySelector('.post-content').replaceWith(form);

            // Handle form submission
            // Handle form submission in edit
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.error) throw new Error(result.error);

                    // Update content
                    const contentDiv = document.createElement('div');
                    contentDiv.className = 'post-content mb-3';
                    contentDiv.innerHTML = result.content;
                    form.replaceWith(contentDiv);

                    // Update image
                    const imgContainer = postDiv.querySelector('.post-image');
                    if (result.image_path) {
                        if (!imgContainer) {
                            imgContainer = document.createElement('div');
                            imgContainer.className = 'post-image mb-3';
                            postDiv.insertBefore(imgContainer, contentDiv.nextSibling);
                        }
                        imgContainer.innerHTML =
                            `<img src="${result.image_path}" class="img-fluid rounded">`;
                    } else if (imgContainer) {
                        imgContainer.remove();
                    }

                    // Update timestamp
                    const timeElement = postDiv.querySelector('.post-time');
                    if (timeElement) {
                        timeElement.textContent = result.updated_at;
                    }

                } catch (error) {
                    alert(error.message);
                    console.error('Error:', error);
                }
            });
            // Handle cancel
            form.querySelector('.cancel-edit').addEventListener('click', () => {
                const contentDiv = document.createElement('div');
                contentDiv.className = 'post-content mb-3';
                contentDiv.textContent = content;
                form.replaceWith(contentDiv);

                if (image) {
                    postDiv.querySelector('.post-image').style.display = 'block';
                }
            });
        });
    });

    //============ DELETE POST HANDLER ================
    document.querySelectorAll('.delete-post').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to delete this post?')) return;

            const postId = this.dataset.postId;
            const csrfToken = this.dataset.csrfToken;

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        delete_post: '1',
                        post_id: postId,
                        csrf_token: csrfToken
                    })
                });

                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Failed to delete post');

                // Remove post from DOM
                document.getElementById(`post-${postId}`).remove();

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




    //======================
    // LIKE COMMENT SECTION//
    //======================





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
            const commentElement = $(`#comment-${commentId}`).find('.comment-content');
            const currentContent = commentElement.text().trim();

            // Replace content with a textarea for editing
            const inputField = $('<textarea>', {
                class: 'form-control edit-input w-100', // Added w-100 to make it take the full container width
                'data-comment-id': commentId,
                val: currentContent,
                rows: 3
            });

            commentElement.replaceWith(inputField);

            // Change "Edit" button to "Save"
            $(this).replaceWith(
                $('<button>', {
                    class: 'btn btn-sm btn-success save-comment',
                    'data-comment-id': commentId,
                    html: '<i class="far fa-save"></i> Save'
                })
            );
        });

        $(document).on('click', '.save-comment', function() {
            const commentId = $(this).data('comment-id');
            const inputField = $(`#comment-${commentId}`).find('.edit-input');
            const newContent = inputField.val().trim();

            // Send AJAX request to update comment
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
                        const updatedContent = $('<span>', {
                            class: 'comment-content',
                            text: newContent
                        });

                        inputField.replaceWith(updatedContent);

                        // Restore "Edit" button
                        $(`[data-comment-id="${commentId}"].save-comment`).replaceWith(
                            $('<button>', {
                                class: 'btn btn-sm btn-outline-warning edit-comment',
                                'data-comment-id': commentId,
                                html: '<i class="far fa-edit"></i> Edit'
                            })
                        );
                    } else {
                        alert(response.message || 'Failed to update comment.');
                    }
                },
                error: function() {
                    alert('Error communicating with the server.');
                }
            });
        });

        // =========================================================================
        //  Reply Comment Functionality
        //======================================



        // =========================================================================
        //  Handle Reply Submission via AJAX
        // =========================================================================

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

    //============ View more comments =========
</script>