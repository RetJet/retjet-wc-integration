document.addEventListener('DOMContentLoaded', function() {
    var copyIcon = retjetIntegration.copyIcon;
    var copiedIcon = retjetIntegration.copiedIcon;

    document.querySelectorAll('.button-copy').forEach(function(button) {
        button.addEventListener('click', function() {
            var target = document.querySelector(button.getAttribute('data-clipboard-target'));
            target.select();
            document.execCommand('copy');
            button.querySelector('img').src = copiedIcon;
            setTimeout(function() {
                button.querySelector('img').src = copyIcon;
            }, 2000);
        });
    });
});
