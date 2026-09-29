
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form').forEach(function (form) {
    if (form.id === 'registration-form') {
      addRegistrationValidation(form);
      return;
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var inputs = Array.from(form.querySelectorAll('input'))
        .filter(function (input) { return !['hidden', 'submit', 'button'].includes(input.type); });

      for (var inp of inputs) {
        if (inp.value.trim() === '') {
          alert('Please fill all fields.');
          inp.focus();
          return;
        }
      }

      if (form.querySelector('input[name="username"]')) {
        alert('Login successful!');
      } else {
        alert('Registration successful!');
      }

      form.reset();
    });
  });
});

function addRegistrationValidation(form) {
  var fields = ['name', 'email', 'mobile', 'password', 'confirm-password', 'course', 'year', 'gender', 'terms'];
  var password = form.querySelector('#password');

  password.addEventListener('input', function () {
    var strength = getPasswordStrength(password.value);
    document.querySelector('#password-strength').textContent = 'Password strength: ' + strength;
  });

  fields.forEach(function (fieldName) {
    var field = form.querySelector('[name="' + fieldName + '"]');
    if (field) {
      field.addEventListener('blur', function () {
        validateRegistrationForm(form);
      });
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (validateRegistrationForm(form)) {
      document.querySelector('#success-message').textContent = 'Registration successful!';
      form.reset();
      document.querySelector('#password-strength').textContent = 'Password strength: Not checked';
    }
  });
}

function validateRegistrationForm(form) {
  var valid = true;
  var name = form.querySelector('#name').value.trim();
  var email = form.querySelector('#email').value.trim();
  var mobile = form.querySelector('#mobile').value.trim();
  var password = form.querySelector('#password').value;
  var confirmPassword = form.querySelector('#confirm-password').value;
  var course = form.querySelector('#course').value;
  var year = form.querySelector('#year').value;
  var gender = form.querySelector('input[name="gender"]:checked');
  var terms = form.querySelector('#terms').checked;

  clearErrors();
  if (!/^[A-Za-z ]{2,50}$/.test(name)) { showError('name', 'Enter a name using letters and spaces.'); valid = false; }
  if (!/^[^ ]+@[^ ]+\.[^ ]+$/.test(email)) { showError('email', 'Enter a valid email address.'); valid = false; }
  if (!/^\d{10}$/.test(mobile)) { showError('mobile', 'Enter exactly 10 digits.'); valid = false; }
  if (!/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/.test(password)) { showError('password', 'Use 8 characters, uppercase, lowercase, and a number.'); valid = false; }
  if (password !== confirmPassword || confirmPassword === '') { showError('confirm-password', 'Passwords must match.'); valid = false; }
  if (course === '') { showError('course', 'Select a course.'); valid = false; }
  if (year === '') { showError('year', 'Select your year.'); valid = false; }
  if (!gender) { showError('gender', 'Select a gender.'); valid = false; }
  if (!terms) { showError('terms', 'Accept the terms to continue.'); valid = false; }

  return valid;
}

function showError(fieldName, message) {
  var error = document.querySelector('#' + fieldName + '-error');
  error.textContent = message;
  var field = document.querySelector('[name="' + fieldName + '"]');
  if (field) { field.setAttribute('aria-describedby', fieldName + '-error'); }
}

function clearErrors() {
  document.querySelectorAll('.error').forEach(function (error) {
    error.textContent = '';
  });
  document.querySelectorAll('[aria-describedby]').forEach(function (field) {
    field.removeAttribute('aria-describedby');
  });
  document.querySelector('#success-message').textContent = '';
}

function getPasswordStrength(password) {
  if (password.length < 8) { return 'Weak'; }
  if (/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)/.test(password)) { return 'Strong'; }
  return 'Medium';
}


