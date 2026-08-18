$(document).ready(function() {

    $('.toggle-password').on('click', function() {
        const targetId = $(this).attr('data-target');
        const inputField = $('#' + targetId);
        const icon = $(this).find('i');

        if (inputField.attr('type') === 'password') {
            inputField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            inputField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        const emailField = $('#email');
        const passwordField = $('#password');
        const alertBox = $('#alertMessage');

        alertBox
            .addClass('d-none')
            .removeClass('alert-danger alert-success')
            .text('');

        $('input').removeClass('is-invalid');

        let isValid = true;

        const emailValue = emailField.val().trim();
        const emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

        if (!emailValue || !emailRegex.test(emailValue)) {
            emailField.addClass('is-invalid');
            isValid = false;
        }

        if (!passwordField.val()) {
            passwordField.addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        const formData = {
            email: emailValue,
            password: passwordField.val()
        };

        $.ajax({
            url: 'http://127.0.0.1:8000/Controllers/Login.php',
            type: 'POST',
            data: formData,
            dataType: 'json',

            success: function(response) {
                if (response.success) {
                    localStorage.setItem('session_token', response.token);

                    alertBox
                        .removeClass('d-none')
                        .addClass('alert-success')
                        .text('Login successful! Redirecting...');

                    setTimeout(function() {
                        window.location.href = 'ProfilePage.html';
                    }, 1500);
                } else {
                    alertBox
                        .removeClass('d-none')
                        .addClass('alert-danger')
                        .text(response.message || 'Something went wrong. Please try again.');
                }
            },

            error: function(xhr) {
                const message = xhr.responseJSON?.message ||
                    'Something went wrong. Please try again.';

                alertBox
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(message);
            }
        });
    });

    $('input').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
});