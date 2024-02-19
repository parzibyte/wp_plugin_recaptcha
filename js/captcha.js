document.addEventListener("DOMContentLoaded", () => {
    let isLoaded = false;
    let isLoading = false;
    let originalContent = "";
    const commentForm = document.querySelector("form.comment-form");
    if (!commentForm) {
        return;
    }
    window.onCaptchaLoadedCallback = () => {
        isLoaded = true;
        submit.value = originalContent;
        submit.disabled = false;
    }
    const submit = commentForm.querySelector("#submit");
    submit.disabled = true;
    commentForm.querySelector("#comment").setAttribute("maxlength", 255);
    ["comment", "author", "email"].forEach(fieldName => {
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