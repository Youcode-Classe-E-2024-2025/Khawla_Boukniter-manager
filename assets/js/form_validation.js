document.addEventListener('DOMContentLoaded', function() {
    const validationRules = {
        register: {
            nom: /^[A-Za-zÀ-ÿ\s'-]{2,50}$/,
            prenom: /^[A-Za-zÀ-ÿ\s'-]{2,50}$/,
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/,
            errorMessages: {
                nom: 'Le nom doit contenir 2-50 caractères (lettres, espaces, traits d\'union)',
                prenom: 'Le prénom doit contenir 2-50 caractères (lettres, espaces, traits d\'union)',
                email: 'Veuillez entrer un email valide',
                password: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre'
            }
        },
        module: {
            titre: /^[A-Za-zÀ-ÿ0-9\s\-_]{3,100}$/,
            description: /^.{10,500}$/,
            duree: /^\d+$/,
            errorMessages: {
                titre: 'Le titre doit contenir 3-100 caractères (lettres, chiffres, espaces)',
                description: 'La description doit contenir entre 10 et 500 caractères',
            }
        },
        cours: {
            titre: /^[A-Za-zÀ-ÿ0-9\s\-_]{3,100}$/,
            description: /^.{10,500}$/,
            niveau: /^(Débutant|Intermédiaire|Avancé)$/,
            prix: /^\d+(\.\d{1,2})?$/,
            errorMessages: {
                titre: 'Le titre doit contenir 3-100 caractères (lettres, chiffres, espaces)',
                description: 'La description doit contenir entre 10 et 500 caractères',
                niveau: 'Niveau invalide (Débutant, Intermédiaire, Avancé)',
            }
        }
    };

    function validateForm(form, formType) {
        const rules = validationRules[formType];
        let isValid = true;

        Object.keys(rules).forEach(field => {
            if (field !== 'errorMessages') {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    const value = input.value.trim();
                    const regex = rules[field];
                    
                    if (!regex.test(value)) {
                        showError(input, rules.errorMessages[field]);
                        isValid = false;
                    } else {
                        clearError(input);
                    }
                }
            }
        });

        return isValid;
    }

    function showError(input, message) {
        clearError(input);

        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.color = 'red';
        errorElement.style.fontSize = '0.8em';
        errorElement.style.marginTop = '5px';

        input.parentNode.insertBefore(errorElement, input.nextSibling);
        input.classList.add('input-error');
    }

    function clearError(input) {
        input.classList.remove('input-error');
        const errorElement = input.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        let formType;
        if (form.id.includes('register') || form.classList.contains('register-form')) {
            formType = 'register';
        } else if (form.id.includes('module') || form.classList.contains('module-form')) {
            formType = 'module';
        } else if (form.id.includes('cours') || form.classList.contains('cours-form')) {
            formType = 'cours';
        }

        form.addEventListener('submit', function(event) {
            if (formType && !validateForm(form, formType)) {
                event.preventDefault();
            }
        });

        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (formType) {
                    const fieldName = input.name;
                    const rules = validationRules[formType];
                    
                    if (rules[fieldName]) {
                        const value = input.value.trim();
                        const regex = rules[fieldName];
                        
                        if (!regex.test(value)) {
                            showError(input, rules.errorMessages[fieldName]);
                        } else {
                            clearError(input);
                        }
                    }
                }
            });
        });
    });
});
