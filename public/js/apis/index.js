const login = () =>{
    document.querySelector('#login-form').addEventListener('submit',async (event) =>{

        event.preventDefault();

        try{

            const email = document.querySelector('#email-login').value;
            const pass = document.querySelector('#password-login').value;
    
            if(!email && !pass)
                return;
    
            const apiUrl = 'http://localhost/library/public/users/login';
            const response = await fetch(apiUrl,{
                headers: {
                    'Content-Type': 'application/json' 
                },
                method : 'POST',
                body : JSON.stringify({
                        "email" : email,
                        "password" : pass
                    })
            });
            if(!response.ok){
                const error = await response.json();
                throw error;
            }

            const token = await response.json();
            console.log(`Token : ${token.token}`);
            document.cookie = `token=${token.token}; path=/; secure; SameSite=Strict`;

            window.location.href = './authenticated/home.html';

            console.log(token);
    
        }catch(e){
            console.error(e);

            window.location.href = './index.html';


        }
    });
}

document.addEventListener('DOMContentLoaded',login);


const createAccount = () =>{

    document.querySelector('#signup-form').addEventListener('submit',async (event) =>{
        event.preventDefault();

        try{

            const usn = document.querySelector('#signup-usn').value;
            const email = document.querySelector('#signup-email').value;
            const password = document.querySelector('#signup-pass').value;

            const apiUrl = 'http://localhost/library/public/users/register';

            const response = await fetch(apiUrl,{

                method : 'POST',
                body : JSON.stringify({
                    'username' : usn,
                    'email' : email,
                    'password' : password,
                    'access_level' : 'admin'
                }),
                headers : {
                    'Content-Type' : 'application/json'
                }

            });

            if(!response.ok)
                throw await response.json();

            console.log(await response.json());

            window.location.href = './index.html';

        }catch(e){
            console.error(e);
        }

    });

}

document.addEventListener('DOMContentLoaded',createAccount);