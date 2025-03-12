// Wait for document to be ready
$(document).ready(function () {
    // Ensure modal can be closed with the close button
    $('#closeModalBtn').on('click', function () {
        $('#responseModal').modal('hide');
    });

    // Also ensure the X in the corner works
    $('.modal .close').on('click', function () {
        $('#responseModal').modal('hide');
    });

    // Handle the quick contact form submission (the form with phone number)
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
                $('#modalMessage').html(response.message);
                $('#responseModal').modal('show');

                // If success, reset the form
                if (response.success) {
                    $('#search')[0].reset();
                }
            },
            error: function (xhr, status, error) {
                // Enhanced error logging
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);

                // Reset button
                $('#search button[type="submit"]').text('Отправить');
                $('#search button[type="submit"]').prop('disabled', false);

                // Show detailed error message if available
                let errorMsg = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте позже.';
                if (xhr.responseText) {
                    errorMsg += '<br><br>Детали: ' + xhr.responseText.substring(0, 100) +
                        (xhr.responseText.length > 100 ? '...' : '');
                }

                $('#modalMessage').html(errorMsg);
                $('#responseModal').modal('show');
            }
        });
    });

    // Handle the main contact form submission
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
                $('#modalMessage').html(response.message);
                $('#responseModal').modal('show');

                // If success, reset the form
                if (response.success) {
                    $('#contact')[0].reset();
                }
            },
            error: function (xhr, status, error) {
                // Enhanced error logging
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);

                // Reset button
                $('#form-submit').text('Отправить');
                $('#form-submit').prop('disabled', false);

                // Show detailed error message if available
                let errorMsg = 'Произошла ошибка при отправке формы. Пожалуйста, попробуйте позже.';
                if (xhr.responseText) {
                    errorMsg += '<br><br>Детали: ' + xhr.responseText.substring(0, 100) +
                        (xhr.responseText.length > 100 ? '...' : '');
                }

                $('#modalMessage').html(errorMsg);
                $('#responseModal').modal('show');
            }
        });
    });

    // Close modal when clicking outside of it or pressing escape
    $(document).on('click', function (e) {
        if ($(e.target).is('#responseModal')) {
            $('#responseModal').modal('hide');
        }
    });

    $(document).keyup(function (e) {
        if (e.key === "Escape") {
            $('#responseModal').modal('hide');
        }
    });
});
