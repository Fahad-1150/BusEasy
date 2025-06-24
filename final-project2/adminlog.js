document.querySelector('.login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const username = this.querySelector('input[type="text"]').value;
    const password = this.querySelector('input[type="password"]').value;

    if (username === 'admin' && password === 'admin123') {
        alert('Login successful!');
       
    } else {
        alert('Invalid credentials!');
    }
});
