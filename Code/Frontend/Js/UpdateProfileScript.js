$(document).ready(function() {

    const sessionToken = localStorage.getItem('session_token');

    if (!sessionToken) {
        window.location.href = 'LoginPage.html';
        return;
    }

    const alertBox = $('#alertMessage');
    const nameField = $('#name');
    const emailField = $('#email');
    const contactField = $('#contact');
    const dobField = $('#dob');

    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const year = yesterday.getFullYear();
    const month = String(yesterday.getMonth() + 1).padStart(2, '0');
    const day = String(yesterday.getDate()).padStart(2, '0');
    dobField.attr('max', `${year}-${month}-${day}`);

    $.ajax({
        url: 'https://guvi-internship-cw1p.onrender.com/Controllers/Profile.php',
        type: 'POST',
        data: {
            token: sessionToken
        },
        dataType: 'json',

        success: function(response) {
            if (response.success && response.data) {
                nameField.val(response.data.name || '');
                emailField.val(response.data.email || '');
                contactField.val(response.data.contact || '');
                dobField.val(response.data.dob || '');
            } else {
                localStorage.removeItem('session_token');
                window.location.href = 'LoginPage.html';
            }
        },

        error: function(xhr) {
            if (xhr.status === 401 || xhr.status === 403) {
                localStorage.removeItem('session_token');
                window.location.href = 'LoginPage.html';
                return;
            }

            const message = xhr.responseJSON?.message ||
                'Failed to load profile details.';

            alertBox
                .removeClass('d-none')
                .addClass('alert-danger')
                .text(message);
        }
    });

    $('#updateProfileForm').on('submit', function(e) {
        e.preventDefault();

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

        const contactValue = contactField.val().trim();
        const contactRegex = /^\d{10}$/;

        if (!contactValue || !contactRegex.test(contactValue)) {
            contactField.addClass('is-invalid');
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

        if (!isValid) {
            return;
        }

        const formData = {
            token: sessionToken,
            name: nameValue,
            contact: contactValue,
            dob: dobValue
        };

        $.ajax({
            url: 'https://guvi-internship-cw1p.onrender.com/Controllers/UpdateProfile.php',
            type: 'POST',
            data: formData,
            dataType: 'json',

            success: function(response) {
                if (response.success) {
                    alertBox
                        .removeClass('d-none')
                        .addClass('alert-success')
                        .text('Profile updated successfully! Redirecting...');

                    setTimeout(function() {
                        window.location.href = 'ProfilePage.html';
                    }, 1500);
                } else {
                    alertBox
                        .removeClass('d-none')
                        .addClass('alert-danger')
                        .text(response.message || 'Failed to update profile.');
                }
            },

            error: function(xhr) {
                if (xhr.status === 401 || xhr.status === 403) {
                    localStorage.removeItem('session_token');
                    window.location.href = 'LoginPage.html';
                    return;
                }

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

    $('#logoutBtn').on('click', function() {
        const sessionToken = localStorage.getItem('session_token');

        if (!sessionToken) {
            window.location.href = 'LoginPage.html';
            return;
        }

        $.ajax({
            url: 'https://guvi-internship-cw1p.onrender.com/Controllers/Logout.php',
            type: 'POST',
            data: {
                token: sessionToken
            },
            dataType: 'json',

            complete: function() {
                localStorage.removeItem('session_token');
                window.location.href = 'LoginPage.html';
            }
        });
    });
});