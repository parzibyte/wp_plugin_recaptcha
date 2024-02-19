document.addEventListener("DOMContentLoaded", () => {
    let isLoaded = false;
    let isLoading = false;
    let originalContent = "";
    const commentForm = document.querySelector("#loginform");
    if (!commentForm) {
        return;
    }
    window.onCaptchaLoadedCallback = () => {
        isLoaded = true;
        submit.value = originalContent;
        submit.disabled = false;
    }
    const submit = commentForm.querySelector("#wp-submit");
    submit.disabled = true;
    ["user_login", "user_pass"].forEach(fieldName => {
        const el = commentForm.querySelector("#" + fieldName);
        if (!el) {
            return;
        }
        el.addEventListener("focus", () => {
            if (isLoaded || isLoading) {
                return;
            }
            isLoading = true;
            originalContent = submit.value;
            submit.value = "Loading captcha...";
            const script = document.createElement("script");
            script.src = "https://www.google.com/recaptcha/api.js?onload=onCaptchaLoadedCallback";
            document.head.appendChild(script);
        });
    });
    commentForm.addEventListener("submit", (event) => {
        if (!isLoaded) {
            event.preventDefault();
            return;
        }
    });
});