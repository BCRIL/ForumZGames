
// Funcion para buscar noticias en la base de datos según su videojuego relacionado y el texto introducido
function buscarNoticias() {
    var query = document.getElementById("search-bar").value;
    if (query.length > 0) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "buscar_noticias.php?q=" + query, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById("resultados-busqueda").innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    } else {
        document.getElementById("resultados-busqueda").innerHTML = "";
    }
}

// Funciones para seleccionar un videojuego de la base de datos según el texto introducido
function seleccionarJuegos() {
    const query = document.getElementById('search-game').value;

    // Si la entrada está vacía, no se hace nada
    if (query.length === 0) {
        document.getElementById('search-results').style.display = 'none';
        return;
    }

    // Realiza la llamada AJAX para buscar juegos
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'seleccionar_juego.php?q=' + query, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const results = JSON.parse(xhr.responseText);
            mostrarResultados(results);
        }
    };
    xhr.send();
}

// Mostrar resultados de juego encontrado
function mostrarResultados(results) {
    const resultsContainer = document.getElementById('search-results');
    resultsContainer.innerHTML = ''; // Limpiar resultados anteriores

    if (results.length > 0) {
        results.forEach(game => {
            const div = document.createElement('div');
            div.className = 'result-item';
            div.textContent = game.nombre; // Asumiendo que 'nombre' es el campo con el nombre del juego
            div.setAttribute('data-id', game.id_videojuego); // Guarda el ID del videojuego
            div.onclick = function() {
                selectGame(this);
            };
            resultsContainer.appendChild(div);
        });
        resultsContainer.style.display = 'block'; // Mostrar resultados
    } else {
        resultsContainer.style.display = 'none'; // Ocultar si no hay resultados
    }
}

// Seleccionar el juego y los datos del juego introducido
function selectGame(element) {
    const gameId = element.getAttribute('data-id');
    const gameName = element.textContent;

    // Aquí puedes establecer el valor seleccionado en otro campo o usarlo como necesites
    document.getElementById('selected-game-id').value = gameId; // Este campo oculto debe existir
    document.getElementById('search-game').value = gameName; // Establece el nombre del juego en el campo de búsqueda
    document.getElementById('search-results').style.display = 'none'; // Oculta los resultados
}
