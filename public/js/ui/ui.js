const showAddBookModal = () =>{

    document.querySelector('#add-book-modal').classList.remove('hidden');
    document.querySelector('#modal-bg').classList.remove('hidden');

    getAllAuthors();

}

const hideAddBookModal = () =>{

    document.querySelector('#add-book-modal').classList.add('hidden');
    document.querySelector('#modal-bg').classList.add('hidden');

}


const ShowCreateAccountModal = () =>{

    document.querySelector('#login-modal').classList.add('hidden');
    document.querySelector('#signup-modal').classList.remove('hidden');

}

const hideCreateAccountModal = () =>{

    document.querySelector('#login-modal').classList.remove('hidden');
    document.querySelector('#signup-modal').classList.add('hidden');

}
