

document.addEventListener('DOMContentLoaded', ()=> {
    const form = document.getElementById('contactForm');
    const dialog = document.getElementById('successDialog');
    

    form.addEventListener('submit', (event) => {
        event.preventDefault(); 
        

        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const message = document.getElementById('message').value.trim();

        if (name === '' || email === '' || message === '') {
            alert('Veuillez remplir tous les champs du formulaire.');
            return;
        }
        else {
            console.log('Nom:', name);
            console.log('Email:', email);
            console.log('Message:', message);
            
            dialog.showModal();
            
        }
        

    });

    dialog.addEventListener('click', () => dialog.close());

    
});

     