// Update the modal handling part of your existing JavaScript
$(document).ready(function () {
    // Show modal function
    function showModal(message) {
        $('#modalMessage').html(message);
        $('#responseModal').addClass('show animated');
        $('body').css('overflow', 'hidden'); // Prevent scrolling
    }

    // Hide modal function
    function hideModal() {
        $('#responseModal').removeClass('show');
        setTimeout(function () {
            $('#responseModal').removeClass('animated');
            $('body').css('overflow', '');
        }, 300);
    }

    // Close modal buttons
    $('#closeModalBtn, #closeModalBtnBottom').on('click', function () {
        hideModal();
    });

    // Close on click outside modal
    $(document).on('click', function (e) {
        if ($(e.target).is('#responseModal')) {
            hideModal();
        }
    });

    // Close on ESC key
    $(document).keyup(function (e) {
        if (e.key === "Escape") {
            hideModal();
        }
    });

    // Update AJAX success handlers to use showModal instead of $('#responseModal').modal('show')
    // For the quick contact form
    $('#search').submit(function (e) {
        e.preventDefault();

        // Add form type to identify which form is being submitted
        var formData = $(this).serialize() + '&form_type=quick_contact';

        // Show loading indication
        $('#search button[type="submit"]').text('Отправка...');
        $('#search button[type="submit"]').prop('disabled', true);

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'handler.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Reset button
                $('#search button[type="submit"]').text('Отправить');
                $('#search button[type="submit"]').prop('disabled', false);

                // Show response in modal
                showModal(response.message);

                // If success, reset the form
                if (response.success) {
                    $('#search')[0].reset();
                }
            },
            error: function (xhr, status, error) {
                // Reset button
                $('#search button[type="submit"]').text('Отправить');
                $('#search button[type="submit"]').prop('disabled', false);

                // Show detailed error message if available
                let errorMsg = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте позже.';
                if (xhr.responseText) {
                    errorMsg += '<br><br>Детали: ' + xhr.responseText.substring(0, 100) +
                        (xhr.responseText.length > 100 ? '...' : '');
                }

                showModal(errorMsg);
            }
        });
    });

    // For the main contact form
    $('#contact').submit(function (e) {
        e.preventDefault();

        // Add form type to identify which form is being submitted
        var formData = $(this).serialize() + '&form_type=main_contact';

        // Show loading indication
        $('#form-submit').text('Отправка...');
        $('#form-submit').prop('disabled', true);

        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'handler.php',
            data: formData,
            dataType: 'json',
            success: function (response) {
                // Reset button
                $('#form-submit').text('Отправить');
                $('#form-submit').prop('disabled', false);

                // Show response in modal
                showModal(response.message);

                // If success, reset the form
                if (response.success) {
                    $('#contact')[0].reset();
                }
            },
            error: function (xhr, status, error) {
                // Reset button
                $('#form-submit').text('Отправить');
                $('#form-submit').prop('disabled', false);

                // Show detailed error message if available
                let errorMsg = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте позже.';
                if (xhr.responseText) {
                    errorMsg += '<br><br>Детали: ' + xhr.responseText.substring(0, 100) +
                        (xhr.responseText.length > 100 ? '...' : '');
                }

                showModal(errorMsg);
            }
        });
    });
});
