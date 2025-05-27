// General site-wide JavaScript
$(document).ready(function() {
    // Enable Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle any flash messages
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
