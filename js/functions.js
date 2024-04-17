document.addEventListener('DOMContentLoaded', function() {
    var cancelButton = document.querySelector('.cancel-btn');
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            window.history.back();
        });
    }
});
