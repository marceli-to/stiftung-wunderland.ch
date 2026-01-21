(function() {
  const classes = {
    'label': 'text-red-600',
    'input': '!border-red-600'
  };
  const inputs = document.querySelectorAll('input[type="text"], input[type="email"], textarea');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      this.classList.remove(classes.input);
      const previousSibling = this.previousElementSibling;
      if (previousSibling && previousSibling.tagName.toLowerCase() === 'label') {
        previousSibling.classList.remove(classes.label);
      }
    });
  });

  // Spam prevention: Generate JS token after 2-second delay
  // Bots typically don't wait, so this helps filter automated submissions
  setTimeout(function() {
    const tokenField = document.getElementById('js-token');
    if (tokenField) {
      tokenField.value = 'human_' + Date.now();
    }
  }, 2000);
})();