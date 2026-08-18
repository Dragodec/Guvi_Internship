$(document).ready(function() {

    const sessionToken = localStorage.getItem('session_token');

    if (!sessionToken) {
        window.location.href = 'LoginPage.html';
        return;
    }

    const alertBox = $('#alertMessage');
    const nameContainer = $('#displayName');
    const emailContainer = $('#displayEmail');
    const dobContainer = $('#displayDob');

    $.ajax({
        url: 'http://127.0.0.1:8000/Controllers/Profile.php',
        type: 'POST',
        data: {
            token: sessionToken
        },
        dataType: 'json',

        success: function(response) {
            if (response.success && response.data) {
                nameContainer.text(response.data.name || '-');
                emailContainer.text(response.data.email || '-');
                dobContainer.text(response.data.dob || '-');
            } else {
                localStorage.removeItem('session_token');
                window.location.href = 'LoginPage.html';
            }
        },

        error: function(xhr) {
            if (xhr.status === 401) {
                localStorage.removeItem('session_token');
                window.location.href = 'LoginPage.html';
                return;
            }

            if (xhr.status === 403) {
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

            nameContainer.text('-');
            emailContainer.text('-');
            dobContainer.text('-');
        }
    });

    $('#logoutBtn').on('click', function() {
        const sessionToken = localStorage.getItem('session_token');

        if (!sessionToken) {
            window.location.href = 'LoginPage.html';
            return;
        }

        $.ajax({
            url: 'http://127.0.0.1:8000/Controllers/Logout.php',
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