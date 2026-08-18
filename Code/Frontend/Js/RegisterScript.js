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

    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        const nameField = $('#name');
        const emailField = $('#email');
        const dobField = $('#dob');
        const passwordField = $('#password');
        const confirmPasswordField = $('#confirmPassword');
        const alertBox = $('#alertMessage');

        alertBox.addClass('d-none').removeClass('alert-danger alert-success').text('');
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

        if (!dobField.val()) {
            dobField.addClass('is-invalid');
            isValid = false;
        }

        if (!passwordField.val()) {
            passwordField.addClass('is-invalid');
            isValid = false;
        }

        if (!confirmPasswordField.val() || passwordField.val() !== confirmPasswordField.val()) {
            confirmPasswordField.addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) return;

        const formData = {
            name: nameValue,
            email: emailValue,
            dob: dobField.val(),
            password: passwordField.val()
        };

        $.ajax({
            url: '../../Backend/Controllers/Register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alertBox.removeClass('d-none').addClass('alert-success').text('Registration successful! Redirecting...');
                    setTimeout(function() {
                        window.location.href = 'LoginPage.html';
                    }, 2000);
                } else {
                    alertBox.removeClass('d-none').addClass('alert-danger').text(response.message || 'Registration failed.');
                }
            },
            error: function() {
                alertBox.removeClass('d-none').addClass('alert-danger').text('An error occurred. Please try again.');
            }
        });
    });

    $('input').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
});