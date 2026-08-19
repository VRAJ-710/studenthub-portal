
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const inputs = Array.from(form.querySelectorAll('input'))
        .filter(i => !['hidden', 'submit', 'button'].includes(i.type));

      for (const inp of inputs) {
        if (inp.value.trim() === '') {
          alert('Please fill all fields.');
          inp.focus();
          return;
        }
      }

      // If the form has a `username` field we treat it as the login form
      if (form.querySelector('input[name="username"]')) {
        alert('Login successful!');
      } else {
        alert('Registration successful!');
      }

      form.reset();
    });
  });
});


