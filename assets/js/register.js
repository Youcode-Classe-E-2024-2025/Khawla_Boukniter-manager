document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    const steps = document.querySelectorAll('.step');
    const stepContents = document.querySelectorAll('.step-content');
    const nextButtons = document.querySelectorAll('.btn-next');
    const prevButtons = document.querySelectorAll('.btn-prev');
    let currentStep = 0;

    function validatePersonalInfo() {
        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        const errors = [];

        if (nom.length < 2) {
            errors.push("Le nom doit contenir au moins 2 caractères.");
        }

        if (prenom.length < 2) {
            errors.push("Le prénom doit contenir au moins 2 caractères.");
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push("Veuillez entrer un email valide.");
        }

        const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(password)) {
            errors.push("Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.");
        }

        if (password !== confirmPassword) {
            errors.push("Les mots de passe ne correspondent pas.");
        }

        return errors;
    }

    function validateRoleSelection() {
        const roleSelect = document.getElementById('role_id');
        const selectedRole = roleSelect.value;

        const errors = [];

        if (!selectedRole) {
            errors.push("Veuillez sélectionner un rôle.");
        }

        return errors;
    }

    function displayErrors(errors) {
        const errorContainer = document.getElementById('form-errors');
        errorContainer.innerHTML = errors.map(error => `<p>${error}</p>`).join('');
        errorContainer.style.display = errors.length > 0 ? 'block' : 'none';
    }

    function changeStep(direction) {
        let errors = [];

        if (direction === 'next') {
            errors = currentStep === 0 ? validatePersonalInfo() : validateRoleSelection();
        }

        if (errors.length > 0) {
            displayErrors(errors);
            return;
        }

        steps[currentStep].classList.remove('active');
        stepContents[currentStep].classList.remove('active');

        currentStep += direction === 'next' ? 1 : -1;

        steps[currentStep].classList.add('active');
        stepContents[currentStep].classList.add('active');

        displayErrors([]);
    }

    nextButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            changeStep('next');
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            changeStep('prev');
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirmation',
            text: 'Voulez-vous vraiment créer ce compte ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, créer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
