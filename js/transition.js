function transitionToPage(href) {
    document.body.classList.add('fade-out');
    setTimeout(function() {
        window.location.href = href;
    }, 100);
}

// Fade in when the page loads
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('fade-out');
    setTimeout(function() {
        document.body.classList.remove('fade-out');
    }, 0);
});

// Fade out when leaving the page
window.addEventListener('beforeunload', function() {
    document.body.classList.add('fade-out');
});