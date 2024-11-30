function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

const addBook = () => {
    document.querySelector('#add-book').addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const author = document.querySelector('#add-book-select-author').value; 

            const title = document.querySelector('#add-book-title').value;
            const genre = document.querySelector('#add-book-genre').value;


            const apiUrl = 'http://localhost/library/public/books/add';

            const token = getCookie('token');

            const response = await fetch(apiUrl, {
                method: 'POST',
                body: JSON.stringify({
                    'title': title,
                    'authorid': author, // Match backend's expected field.
                    'genre': genre
                }),
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw await response.json();
            }

            const result = await response.json();

            document.cookie = `token=${result.new_token}; path=/; secure; SameSite=Strict`;

            await getAllBooks();

            hideAddBookModal();

        } catch (e) {
            console.error(e);
        }
    });
};


document.addEventListener('DOMContentLoaded',addBook);


const deleteBook = async (id) =>{
    
    try{

        const apiUrl = `http://localhost/library/public/books/delete/${id}`;

        const token = getCookie('token');

        const response = await fetch(apiUrl, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if(!response.ok)
            throw await response.json();

        const result = await response.json();

        if(result){
            
            console.log('Delete new token : '+result.new_token);

            document.cookie = `token=${result.new_token}; path=/; secure; SameSite=Strict`;

            await getAllBooks();
    
        }


    }catch(e){
        console.error(e);
    }


}

const getAllBooks = async () => {
    try {
        const token = getCookie('token');
        
        if (!token) {
            alert("Token is not available in the cookies");
            window.location.href = '../index.html';
            return;
        }

        console.log("Token get all book : ", token);  

        const urlApi = `http://localhost/library/public/books/displayAll`;

        const response = await fetch(urlApi, {
            method: 'GET',
            credentials: 'include', // Ensures cookies are included in the request
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            throw await response.text();
        }

        const result = await response.json();

        console.log('Result get all books : '+result);

        if(result){
            document.cookie = `token=${result.new_token}; path=/; secure; SameSite=Strict`;

            const tbody = document.querySelector('#display-books-tbody');
            tbody.innerHTML = '';
    
            result.data.forEach(data => {
                const book = data;
    
                const tr = document.createElement('tr');
                tr.classList.add('p-4');
    
                const title = document.createElement('td');
                title.classList.add('text-center');
                title.textContent = book.title;
    
                const genre = document.createElement('td');
                genre.classList.add('text-center');
                genre.textContent = book.genre;
    
                const operationDelete = document.createElement('td');
                operationDelete.classList.add('flex','justify-center');
                
                const deleteBook = document.createElement('button');
                deleteBook.classList.add('p-2','rounded-md','bg-red-900','text-white');
                deleteBook.textContent = 'Delete';
                deleteBook.setAttribute('onclick',`deleteBook(${book.bookid})`);
                deleteBook.id = book.bookid;
                operationDelete.appendChild(deleteBook);
    
                const operationUpdate = document.createElement('td');
                operationUpdate.classList.add('flex','justify-center');

                const update = document.createElement('button');
                update.value = 'Rename';
                update.setAttribute('onclick','updateBook()');
                update.id = book.bookid;
                operationUpdate.appendChild(update);
    
                const author = document.createElement('td');
                author.classList.add('text-center');
                author.textContent = book.authorname;
    
                tr.appendChild(author);
                tr.appendChild(title);
                tr.appendChild(genre);
                tr.appendChild(operationDelete);
                tr.appendChild(operationUpdate);
    
                tbody.appendChild(tr);
                
            });
        }

    } catch (e) {
        console.error("Error fetching books:", e);
    }
};


document.addEventListener('DOMContentLoaded',async()=>{
    await getAllBooks();
});

// const findAuthorById = async (id) =>{

//     try{

//         const apiUrl = `http://localhost/library/public/authors/${id}`;

//         const token = getCookie('token');

//         const response = await fetch(apiUrl,{
//             method : 'GET',
//             credentials : 'include',
//             headers : {
//                 'Authorization' : `Bearer ${token}`
//             }
//         });

//         if(!response.ok)
//             throw await response.json();

//         const result = await response.json();

//         document.cookie = `token=${result.new_token}; path=/; secure; SameSite=Strict`;

//         return result.data.authorname;

//     }catch(e){
//         console.error(e);
//     }

// }


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

        const result = await response.json();
        if(result){
        
            // console.log('New token : '+newToken.new_token);

            document.cookie = `token=${result.new_token}; path=/; secure; SameSite=Strict`;

            const select = document.querySelector('#add-book-select-author');
            select.innerHTML = '';

            result.data.forEach(element => {
                
                const option = document.createElement('option');
                option.value = element.authorid;
                option.id = element.authorid;
                option.textContent = element.authorname;

                select.appendChild(option);

            });
        }
            
    } catch (e) {
        console.error(e);
    }
};

const logout =  () =>{

    const time = new Date();
    time.setTime(time.getTime() - 1000); // Set time to 1 second in the past
    
    document.cookie = `token=; expires=${time.toUTCString()}; path=/; secure; SameSite=Strict`;
    
    window.location.href = '../index.html';


}