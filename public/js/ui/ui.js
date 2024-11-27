const addBookModal = () =>{

    document.querySelector('#add-book-modal').classList.remove('hidden');
    document.querySelector('#modal-bg').classList.remove('hidden');

    getAllAuthors();

}


const ShowCreateAccountModal = () =>{

    document.querySelector('#login-modal').classList.add('hidden');
    document.querySelector('#signup-modal').classList.remove('hidden');

}

const hideCreateAccountModal = () =>{

    document.querySelector('#login-modal').classList.remove('hidden');
    document.querySelector('#signup-modal').classList.add('hidden');

}
