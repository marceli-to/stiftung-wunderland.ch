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
})();