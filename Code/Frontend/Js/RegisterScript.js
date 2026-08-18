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

    const dobField = $('#dob');

    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    const year = yesterday.getFullYear();
    const month = String(yesterday.getMonth() + 1).padStart(2, '0');
    const day = String(yesterday.getDate()).padStart(2, '0');

    dobField.attr('max', `${year}-${month}-${day}`);

    $('#registerForm').on('submit', function(e) {
        e.preventDefault();

        const nameField = $('#name');
        const emailField = $('#email');
        const passwordField = $('#password');
        const confirmPasswordField = $('#confirmPassword');
        const alertBox = $('#alertMessage');

        alertBox
            .addClass('d-none')
            .removeClass('alert-danger alert-success')
            .text('');

        $('input').removeClass('is-invalid');

        let isValid = true;

        const nameValue = nameField.val().trim();
        const nameRegex = /^[^\d]{1,50}$/;

        if (!nameValue || !nameRegex.test(nameValue)) {
            nameField.addClass('is-invalid');
            isValid = false;
        }

        const emailValue = emailField.val().trim();
        const emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

        if (!emailValue || !emailRegex.test(emailValue)) {
            emailField.addClass('is-invalid');
            isValid = false;
        }

        const dobValue = dobField.val();

        if (!dobValue) {
            dobField.addClass('is-invalid');
            dobField.siblings('.invalid-feedback').text('Please select your date of birth.');
            isValid = false;
        } else {
            const selectedDate = new Date(`${dobValue}T00:00:00`);
            const today = new Date();

            today.setHours(0, 0, 0, 0);

            if (selectedDate >= today) {
                dobField.addClass('is-invalid');
                dobField.siblings('.invalid-feedback').text('Date of birth cannot be today or a future date.');
                isValid = false;
            }
        }

        if (!passwordField.val()) {
            passwordField.addClass('is-invalid');
            isValid = false;
        }

        if (
            !confirmPasswordField.val() ||
            passwordField.val() !== confirmPasswordField.val()
        ) {
            confirmPasswordField.addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        const formData = {
            name: nameValue,
            email: emailValue,
            dob: dobValue,
            password: passwordField.val()
        };

        $.ajax({
            url: 'http://127.0.0.1:8000/Controllers/Register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',

            success: function(response) {
                if (response.success) {
                    alertBox
                        .removeClass('d-none')
                        .addClass('alert-success')
                        .text('Registration successful! Redirecting...');

                    setTimeout(function() {
                        window.location.href = 'LoginPage.html';
                    }, 2000);
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