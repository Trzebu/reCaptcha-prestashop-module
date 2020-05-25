var PUBLIC_KEY = "publicKeyReplacement";

window.onload = function () {
    $.getScript("https://www.google.com/recaptcha/api.js", function () {
        insertCaptchaHtml();
    });
}

function insertCaptchaHtml () {
    $("<div></div>").addClass(
        'g-recaptcha'
    ).attr(
        'data-sitekey', PUBLIC_KEY
    ).insertBefore(
        "form > footer > .btn"
    );
}