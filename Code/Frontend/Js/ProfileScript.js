$(document).ready(function() {

    const sessionToken = localStorage.getItem('session_token');

    if (!sessionToken) {
        window.location.href = 'LoginPage.html';
        return;
    }

    const alertBox = $('#alertMessage');
    const nameContainer = $('#displayName');
    const emailContainer = $('#displayEmail');
    const contactContainer = $('#displayContact');
    const dobContainer = $('#displayDob');
    const ageContainer = $('#displayAge');

    function calculateAge(dobString) {
        if (!dobString) return '-';
        const birthDate = new Date(`${dobString}T00:00:00`);
        if (isNaN(birthDate.getTime())) return '-';
        
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age >= 0 ? `${age} years` : '-';
    }

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
                contactContainer.text(response.data.contact || '-');
                dobContainer.text(response.data.dob || '-');
                ageContainer.text(calculateAge(response.data.dob));
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

            nameContainer.text('-');
            emailContainer.text('-');
            contactContainer.text('-');
            dobContainer.text('-');
            ageContainer.text('-');
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