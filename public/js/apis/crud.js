const addBook = () =>{

    document.querySelector('#add-book').addEventListener('submit',async () =>{

        try{
            const author = document.querySelector('#add-author').value;
            const title = document.querySelector('#add-title').value;
            const genre = document.querySelector('#add-genre').value;

            const apiUrl = 'http://localhost/library/public/users/login';

        }catch(e){
            console.error(e);
        }


    });

}

const getAllAuthors = async () => {
    try {
        const token = getCookie('token');

        const urlApi = `http://localhost/library/public/authors/display`;

        const response = await fetch(urlApi, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw await response.text();
        }

        const newToken = await response.json();

        // console.log('New token : '+newToken.new_token);

        document.cookie = `token=${newToken.new_token}; path=/; secure; SameSite=Strict`;

        const select = document.querySelector('#add-book-select-author');
        select.innerHTML = '';

        newToken.data.forEach(element => {
            
            const option = document.createElement('option');
            option.id = element.authorid;
            option.textContent = element.authorname;

            select.appendChild(option);

        });
        



    } catch (e) {
        console.error(e);
    }
};

function setCookie(name, value) {
    const expires = new Date();
    expires.setTime(expires.getTime() + 60 * 60 * 1000); // Expiry in 1 hour (60 minutes * 60 seconds * 1000 milliseconds)
    document.cookie = `${name}=${value};path=/`;
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null; // Return null if the cookie is not found
}