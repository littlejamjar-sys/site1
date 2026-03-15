// Contact form handler — AJAX submission to sendmail.php

document.addEventListener("DOMContentLoaded", () => {

  const form = document.querySelector("#contactForm");
  const messageBox = document.querySelector("#formMessage");

  if (!form) return;

  let sending = false;

  form.addEventListener("submit", async (e) => {

    e.preventDefault();

    if (sending) return;
    sending = true;

    const button = form.querySelector("button[type='submit']");
    if (button) {
      button.disabled = true;
      button.textContent = "Sending...";
    }

    if (messageBox) {
      messageBox.textContent = "";
      messageBox.className = "";
    }

    try {

      const response = await fetch("/sendmail.php", {
        method: "POST",
        body: new FormData(form)
      });

      const data = await response.json();

      if (data.status === "sent") {

        if (messageBox) {
          messageBox.textContent = "Your message has been sent. We will respond as soon as possible.";
          messageBox.className = "success";
        }
        form.reset();

      } else {

        if (messageBox) {
          messageBox.textContent = "There was a problem sending your message. Please try again.";
          messageBox.className = "error";
        }

      }

    } catch {

      if (messageBox) {
        messageBox.textContent = "There was a problem sending your message. Please try again.";
        messageBox.className = "error";
      }

    }

    if (button) {
      button.disabled = false;
      button.textContent = "Send Message";
    }
    sending = false;

  });

});
