let pagPos = 0;

document.addEventListener('DOMContentLoaded', (event) => {

    // Función para crear el modal
    function createModal(url, formData) {
        var modal = document.createElement('div');
        modal.id = 'modal';
        var modalContent = document.createElement('div');
        modalContent.id = 'modal-content';

        // Crear el botón de cerrar y agregarlo al modalContent
        var closeButton = document.createElement('button');
        closeButton.textContent = 'Cerrar';
        closeButton.onclick = function() {
            modal.style.display = 'none';
            modal.remove();
        };

        fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                modalContent.innerHTML = html;
                modalContent.appendChild(closeButton);
                createBtnSubmit();
            })
            .catch(error => {
                console.error('Error al cargar el contenido: ', error);
                modalContent.innerHTML = '<p>Error al cargar el contenido.</p>';
            });

        modal.appendChild(modalContent);
        document.body.appendChild(modal);
    }

    // Función para crear una ventana modal de confirmación
    function createConfirmModal(confirmAction) {
        var modal = document.createElement('div');
        modal.id = 'modal';

        var modalContent = document.createElement('div');
        modalContent.id = 'modal-content';

        var modalText = document.createElement('p');
        modalText.textContent = '¿Estás seguro de que deseas realizar esta acción?';
        modalContent.appendChild(modalText);

        // Crear botón de confirmación
        var confirmBtn = document.createElement('button');
        confirmBtn.textContent = 'Confirmar';
        confirmBtn.onclick = function() {
            modal.style.display = 'none';
            modal.remove();
            confirmAction();
        };
        modalContent.appendChild(confirmBtn);

        // Crear botón de cancelación
        var cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'Cancelar';
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
            modal.remove();
        };
        modalContent.appendChild(cancelBtn);

        modal.appendChild(modalContent);
        document.body.appendChild(modal);
    }

    // Evento para el botón 'btn-add'
    function createBtnSubmit() {
        var formulario = document.querySelector('#webForm');
        formulario.addEventListener('submit', function (e) {
            e.preventDefault(); // Evitar el envío estándar del formulario
    
            let datos = new FormData(formulario); // Recoger los datos del formulario
    
            let action = "save";
            if(document.querySelector('#id').value > 0){
                action = "update";
            }
            datos.append('webAction', action);

            fetch(module, { // Reemplaza con la URL a la que deseas enviar el formulario
                method: 'POST',
                body: datos
            })
            .then(response => response.text()) // o .text(), dependiendo de lo que responda tu servidor
            .then(data => {
                var modal = document.querySelector('#modal');
                modal.style.display = 'none';
                modal.remove();
                document.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }

    function cargaBtns(){
            
        // Evento para el botón 'btn-add'
        var btnAdd = document.querySelector('.btn-add');
        if (btnAdd) {
            btnAdd.addEventListener('click', function() {
                var formData = new FormData();
                formData.append('id', 0);
                createModal(module,formData);
            });
        }

        // Eventos para los botones 'btn-edit'
        var editButtons = document.querySelectorAll('.btn-edit');
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var formData = new FormData();
                formData.append('webAction', 'get');
                formData.append('id', this.getAttribute('data-id'));
                createModal(module,formData);
            });
        });

        // Eventos para los botones 'btn-del'
        var delButtons = document.querySelectorAll('.btn-del');
        delButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                console.log("to delete ...");
                let iddata = this.getAttribute('data-id');
                createConfirmModal(function() {

                    var formData = new FormData();
                    formData.append('webAction', 'delete');
                    formData.append('id', iddata);
        
                    fetch(module, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(html => {
                        console.log('Elemento eliminado.');
                        document.location.reload();
                    })
                    .catch(error => {
                        console.error('Error al cargar el contenido: ', error);
                        modalContent.innerHTML = '<p>Error al cargar el contenido.</p>';
                    });
                    
                });
            });
        });

        //Evento cambio de pagina
        var btnPagina = document.querySelectorAll('#webPagination .pag');
        btnPagina.forEach(function(pagina,posicion) {

            pagina.addEventListener('click', function() {
                irPagina(posicion*10);
                pagPos = posicion;
            });

        });

        //Evento cambio de pagina Prev
        var btnPrevPagina = document.querySelector('#webPagination .previous');
        if(btnPrevPagina){
                
            btnPrevPagina.addEventListener('click', function() {
                pagPos--;
                irPagina(pagPos*10)
            });

        }

        //Evento cambio de pagina Next
        var btnNextPagina = document.querySelector('#webPagination .next');
        if(btnNextPagina){

            btnNextPagina.addEventListener('click', function() {
                pagPos++;
                irPagina(pagPos*10)
            });

        }

    }

    //Funcion para ir a una pagina
    function irPagina(posicion){
        let datos = new FormData();
        datos.append('webAction', 'list');
        datos.append('webPageIni', posicion );

        fetch(module+'s', { // Reemplaza con la URL a la que deseas enviar el formulario
            method: 'POST',
            body: datos
        })
        .then(response => response.text())
        .then(html => {
            document.querySelector('#main').innerHTML = html;
            console.log(pagPos);
            cargaBtns();
        })
        .catch(error => {
            console.error('Error:', error);
        });

    }

    //Inicializacion de botones
    cargaBtns();

});
