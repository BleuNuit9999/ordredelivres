<?php
// === Charger style parent + style enfant ===
function astra_child_enqueue_styles() {
    wp_enqueue_style(
        'astra-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    wp_enqueue_style(
        'astra-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('astra-parent-style'),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');


// === Custom Post Type "Auteur" - 100% WordPress natif ===
function register_auteur_post_type() {
    register_post_type('auteur', [
        'labels' => [
            'name' => 'Auteurs',
            'singular_name' => 'Auteur',
            'add_new' => 'Ajouter un auteur',
            'add_new_item' => 'Ajouter un nouvel auteur',
            'edit_item' => 'Modifier l\'auteur',
            'new_item' => 'Nouvel auteur',
            'view_item' => 'Voir l\'auteur',
            'search_items' => 'Rechercher des auteurs',
            'not_found' => 'Aucun auteur trouvé',
            'all_items' => 'Tous les auteurs',
            'menu_name' => 'Auteurs'
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'auteur'],
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-admin-users',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
    ]);

    // Taxonomie pour les genres
    register_taxonomy('genre_auteur', 'auteur', [
        'hierarchical' => true,
        'labels' => [
            'name' => 'Genres',
            'singular_name' => 'Genre',
            'search_items' => 'Rechercher des genres',
            'all_items' => 'Tous les genres',
            'edit_item' => 'Modifier le genre',
            'update_item' => 'Mettre à jour le genre',
            'add_new_item' => 'Ajouter un nouveau genre',
            'new_item_name' => 'Nom du nouveau genre',
            'menu_name' => 'Genres',
        ],
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'genre'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'register_auteur_post_type');

// === Flush rewrite rules lors de l'activation ===
function auteur_rewrite_flush() {
    register_auteur_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'auteur_rewrite_flush');


// === Meta Box pour éditer les données des livres dans l'admin (VERSION 2.0) ===
function add_auteur_meta_boxes() {
    add_meta_box(
        'auteur_books_data',
        'Gestion des Livres',
        'auteur_books_meta_box_callback',
        'auteur',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_auteur_meta_boxes');

function auteur_books_meta_box_callback($post) {
    wp_nonce_field('auteur_books_save', 'auteur_books_nonce');
    
    $books_data = get_post_meta($post->ID, 'auteur_books_data', true);
    $data = !empty($books_data) ? json_decode($books_data, true) : ['series' => [], 'standalones' => []];
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Afficher un message d'erreur clair avec solution
        echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0;">';
        echo '<h3 style="margin-top: 0;">⚠️ Erreur de données détectée</h3>';
        echo '<p><strong>Problème :</strong> Les données de cet auteur sont corrompues (erreur JSON).</p>';
        echo '<p><strong>Cause probable :</strong> Apostrophes ou caractères spéciaux mal encodés (ex: L\'Écho).</p>';
        echo '<p><strong>Solution :</strong></p>';
        echo '<ol>';
        echo '<li>Allez dans <strong>Auteurs > Réparer JSON</strong></li>';
        echo '<li>Cliquez sur "🔧 Réparer toutes les données JSON"</li>';
        echo '<li>Revenez sur cette page et rechargez (F5)</li>';
        echo '</ol>';
        echo '<p><a href="' . admin_url('edit.php?post_type=auteur&page=repair-auteurs-json') . '" class="button button-primary">🔧 Aller à la réparation</a></p>';
        echo '</div>';
        
        // Essayer quand même d'initialiser avec des données vides
        $data = ['series' => [], 'standalones' => []];
    }
    
    ?>
    <style>
        .books-editor { margin: 20px 0; }
        .serie-section, .standalone-section { 
            background: #f9f9f9; 
            padding: 15px; 
            margin: 15px 0; 
            border-left: 4px solid #2271b1;
            border-radius: 5px;
        }
        .book-item { 
            background: white; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
        }
        .book-item input[type="text"], .book-item input[type="url"] { 
            width: 100%; 
            margin: 5px 0;
            padding: 8px;
        }
        .book-item label {
            display: inline-block;
            width: 150px;
            font-weight: 600;
            color: #2271b1;
        }
        .btn-remove { 
            background: #dc3232; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            cursor: pointer;
            border-radius: 3px;
            margin-top: 10px;
        }
        .btn-remove:hover { background: #a00; }
        .btn-add { 
            background: #2271b1; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            cursor: pointer;
            border-radius: 3px;
            margin: 10px 0;
        }
        .btn-add:hover { background: #135e96; }
        .btn-move {
            background: #50575e;
            color: white;
            border: none;
            padding: 4px 10px;
            cursor: pointer;
            border-radius: 3px;
            margin-right: 5px;
            font-size: 0.9em;
        }
        .btn-move:hover { background: #32373c; }
        .btn-move:disabled {
            background: #ddd;
            cursor: not-allowed;
            color: #999;
        }
        .book-actions {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 10px;
        }
        .serie-title-input {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 15px;
            padding: 10px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .section-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2271b1;
            margin: 0;
        }
        .help-text {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2271b1;
        }
        .book-number {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: 600;
            margin-right: 10px;
        }
    </style>

    <div class="help-text">
        <strong>💡 Comment utiliser cette interface :</strong>
        <ul>
            <li>Modifiez directement les informations existantes dans les champs</li>
            <li>Utilisez les boutons ⬆️ <strong>Monter</strong> et ⬇️ <strong>Descendre</strong> pour réorganiser l'ordre des livres</li>
            <li>Cliquez sur "➕ Ajouter un livre" pour ajouter un nouveau livre à une série</li>
            <li>Cliquez sur "➕ Ajouter une série" pour créer une nouvelle série</li>
            <li>Cliquez sur "➕ Ajouter un livre indépendant" pour ajouter un livre hors série</li>
            <li>Utilisez les boutons "🗑️ Supprimer" pour retirer des éléments</li>
            <li><strong>N'oubliez pas de cliquer sur "Mettre à jour" en haut à droite pour sauvegarder vos modifications!</strong></li>
        </ul>
    </div>

    <div class="books-editor">
        <div id="series-container">
            <h3>📚 Séries</h3>
            <?php
            if (!empty($data['series'])) {
                foreach ($data['series'] as $s_index => $serie) {
                    render_serie_editor($s_index, $serie);
                }
            }
            ?>
        </div>
        <button type="button" class="btn-add" onclick="addSerie()">➕ Ajouter une série</button>

        <hr style="margin: 30px 0;">

        <div id="standalones-container">
            <h3>📖 Livres indépendants</h3>
            <?php
            if (!empty($data['standalones'])) {
                foreach ($data['standalones'] as $st_index => $standalone) {
                    render_standalone_editor($st_index, $standalone);
                }
            }
            ?>
        </div>
        <button type="button" class="btn-add" onclick="addStandalone()">➕ Ajouter un livre indépendant</button>
    </div>

    <script>
    let serieIndex = <?php echo count($data['series'] ?? []); ?>;
    let standaloneIndex = <?php echo count($data['standalones'] ?? []); ?>;

    function addSerie() {
        const container = document.getElementById('series-container');
        const div = document.createElement('div');
        div.className = 'serie-section';
        div.id = 'serie-' + serieIndex;
        div.innerHTML = `
            <div class="section-header">
                <h4 class="section-title">Série ${serieIndex + 1}</h4>
                <button type="button" class="btn-remove" onclick="removeSerie(${serieIndex})">🗑️ Supprimer la série</button>
            </div>
            <label>Titre de la série:</label>
            <input type="text" name="series[${serieIndex}][title]" class="serie-title-input" placeholder="Ex: La saga Harry Potter">
            <div id="serie-${serieIndex}-books"></div>
            <button type="button" class="btn-add" onclick="addBookToSerie(${serieIndex})">➕ Ajouter un livre</button>
        `;
        container.appendChild(div);
        serieIndex++;
    }

    function addBookToSerie(serieIndex) {
        const container = document.getElementById('serie-' + serieIndex + '-books');
        const bookCount = container.querySelectorAll('.book-item').length;
        const div = document.createElement('div');
        div.className = 'book-item';
        div.innerHTML = `
            <h5><span class="book-number">#${bookCount + 1}</span>📖 Livre ${bookCount + 1}</h5>
            <label>Titre:</label>
            <input type="text" name="series[${serieIndex}][books][${bookCount}][title]" placeholder="Titre du livre">
            <label>Date de publication:</label>
            <input type="text" name="series[${serieIndex}][books][${bookCount}][publication_date]" placeholder="Ex: 2024">
            <label>Lien Amazon:</label>
            <input type="url" name="series[${serieIndex}][books][${bookCount}][amazon]" placeholder="https://...">
            <label>Lien Kindle:</label>
            <input type="url" name="series[${serieIndex}][books][${bookCount}][kindle]" placeholder="https://...">
            <label>Lien Audible:</label>
            <input type="url" name="series[${serieIndex}][books][${bookCount}][audible]" placeholder="https://...">
            <div class="book-actions">
                <button type="button" class="btn-move" onclick="moveBookUp(this)">⬆️ Monter</button>
                <button type="button" class="btn-move" onclick="moveBookDown(this)">⬇️ Descendre</button>
                <button type="button" class="btn-remove" onclick="this.closest('.book-item').remove(); updateBookNumbers(${serieIndex})">🗑️ Supprimer</button>
            </div>
        `;
        container.appendChild(div);
        updateMoveButtons(container);
    }

    function removeSerie(index) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette série et tous ses livres ?')) {
            document.getElementById('serie-' + index).remove();
        }
    }

    function addStandalone() {
        const container = document.getElementById('standalones-container');
        const standaloneCount = container.querySelectorAll('.standalone-section').length;
        const div = document.createElement('div');
        div.className = 'standalone-section';
        div.innerHTML = `
            <div class="section-header">
                <h4 class="section-title"><span class="book-number">#${standaloneCount + 1}</span>📖 Livre indépendant ${standaloneCount + 1}</h4>
                <div>
                    <button type="button" class="btn-move" onclick="moveStandaloneUp(this)">⬆️ Monter</button>
                    <button type="button" class="btn-move" onclick="moveStandaloneDown(this)">⬇️ Descendre</button>
                    <button type="button" class="btn-remove" onclick="this.closest('.standalone-section').remove(); updateStandaloneNumbers()">🗑️ Supprimer</button>
                </div>
            </div>
            <label>Titre:</label>
            <input type="text" name="standalones[${standaloneCount}][title]" placeholder="Titre du livre">
            <label>Date de publication:</label>
            <input type="text" name="standalones[${standaloneCount}][publication_date]" placeholder="Ex: 2024">
            <label>Lien Amazon:</label>
            <input type="url" name="standalones[${standaloneCount}][amazon]" placeholder="https://...">
            <label>Lien Kindle:</label>
            <input type="url" name="standalones[${standaloneCount}][kindle]" placeholder="https://...">
            <label>Lien Audible:</label>
            <input type="url" name="standalones[${standaloneCount}][audible]" placeholder="https://...">
        `;
        container.appendChild(div);
        standaloneIndex++;
        updateStandaloneMoveButtons();
    }

    function moveBookUp(button) {
        const bookItem = button.closest('.book-item');
        const previousBook = bookItem.previousElementSibling;
        
        if (previousBook && previousBook.classList.contains('book-item')) {
            bookItem.parentNode.insertBefore(bookItem, previousBook);
            const serieId = bookItem.closest('[id^="serie-"]').id.split('-')[1];
            updateBookNumbers(serieId);
        }
    }

    function moveBookDown(button) {
        const bookItem = button.closest('.book-item');
        const nextBook = bookItem.nextElementSibling;
        
        if (nextBook && nextBook.classList.contains('book-item')) {
            bookItem.parentNode.insertBefore(nextBook, bookItem);
            const serieId = bookItem.closest('[id^="serie-"]').id.split('-')[1];
            updateBookNumbers(serieId);
        }
    }

    function moveStandaloneUp(button) {
        const standalone = button.closest('.standalone-section');
        const previousStandalone = standalone.previousElementSibling;
        
        if (previousStandalone && previousStandalone.classList.contains('standalone-section')) {
            standalone.parentNode.insertBefore(standalone, previousStandalone);
            updateStandaloneNumbers();
        }
    }

    function moveStandaloneDown(button) {
        const standalone = button.closest('.standalone-section');
        const nextStandalone = standalone.nextElementSibling;
        
        if (nextStandalone && nextStandalone.classList.contains('standalone-section')) {
            standalone.parentNode.insertBefore(nextStandalone, standalone);
            updateStandaloneNumbers();
        }
    }

    function updateBookNumbers(serieIndex) {
        const container = document.getElementById('serie-' + serieIndex + '-books');
        const books = container.querySelectorAll('.book-item');
        
        books.forEach((book, index) => {
            // Mettre à jour le numéro affiché
            const numberSpan = book.querySelector('.book-number');
            const h5 = book.querySelector('h5');
            if (numberSpan) numberSpan.textContent = '#' + (index + 1);
            if (h5) h5.innerHTML = `<span class="book-number">#${index + 1}</span>📖 Livre ${index + 1}`;
            
            // Mettre à jour les attributs name des inputs
            const inputs = book.querySelectorAll('input');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/\[books\]\[\d+\]/, `[books][${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        
        updateMoveButtons(container);
    }

    function updateStandaloneNumbers() {
        const container = document.getElementById('standalones-container');
        const standalones = container.querySelectorAll('.standalone-section');
        
        standalones.forEach((standalone, index) => {
            // Mettre à jour le numéro affiché
            const numberSpan = standalone.querySelector('.book-number');
            const h4 = standalone.querySelector('.section-title');
            if (numberSpan) numberSpan.textContent = '#' + (index + 1);
            if (h4) h4.innerHTML = `<span class="book-number">#${index + 1}</span>📖 Livre indépendant ${index + 1}`;
            
            // Mettre à jour les attributs name des inputs
            const inputs = standalone.querySelectorAll('input');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/standalones\[\d+\]/, `standalones[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        
        updateStandaloneMoveButtons();
    }

    function updateMoveButtons(container) {
        const books = container.querySelectorAll('.book-item');
        
        books.forEach((book, index) => {
            const upButton = book.querySelector('.btn-move[onclick*="moveBookUp"]');
            const downButton = book.querySelector('.btn-move[onclick*="moveBookDown"]');
            
            // Désactiver le bouton "Monter" pour le premier livre
            if (upButton) {
                upButton.disabled = (index === 0);
            }
            
            // Désactiver le bouton "Descendre" pour le dernier livre
            if (downButton) {
                downButton.disabled = (index === books.length - 1);
            }
        });
    }

    function updateStandaloneMoveButtons() {
        const container = document.getElementById('standalones-container');
        const standalones = container.querySelectorAll('.standalone-section');
        
        standalones.forEach((standalone, index) => {
            const upButton = standalone.querySelector('.btn-move[onclick*="moveStandaloneUp"]');
            const downButton = standalone.querySelector('.btn-move[onclick*="moveStandaloneDown"]');
            
            // Désactiver le bouton "Monter" pour le premier livre
            if (upButton) {
                upButton.disabled = (index === 0);
            }
            
            // Désactiver le bouton "Descendre" pour le dernier livre
            if (downButton) {
                downButton.disabled = (index === standalones.length - 1);
            }
        });
    }

    // Mettre à jour les boutons au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="serie-"][id$="-books"]').forEach(container => {
            updateMoveButtons(container);
        });
        updateStandaloneMoveButtons();
    });
    </script>
    <?php
}

function render_serie_editor($index, $serie) {
    ?>
    <div class="serie-section" id="serie-<?php echo $index; ?>">
        <div class="section-header">
            <h4 class="section-title">Série <?php echo ($index + 1); ?></h4>
            <button type="button" class="btn-remove" onclick="removeSerie(<?php echo $index; ?>)">🗑️ Supprimer la série</button>
        </div>
        <label>Titre de la série:</label>
        <input type="text" name="series[<?php echo $index; ?>][title]" value="<?php echo esc_attr($serie['title'] ?? ''); ?>" class="serie-title-input" placeholder="Ex: La saga Harry Potter">
        
        <div id="serie-<?php echo $index; ?>-books">
            <?php
            if (!empty($serie['books'])) {
                foreach ($serie['books'] as $b_index => $book) {
                    render_book_editor($index, $b_index, $book);
                }
            }
            ?>
        </div>
        <button type="button" class="btn-add" onclick="addBookToSerie(<?php echo $index; ?>)">➕ Ajouter un livre</button>
    </div>
    <?php
}

function render_book_editor($serie_index, $book_index, $book) {
    ?>
    <div class="book-item">
        <h5><span class="book-number">#<?php echo ($book_index + 1); ?></span>📖 Livre <?php echo ($book_index + 1); ?></h5>
        <label>Titre:</label>
        <input type="text" name="series[<?php echo $serie_index; ?>][books][<?php echo $book_index; ?>][title]" value="<?php echo esc_attr($book['title'] ?? ''); ?>" placeholder="Titre du livre">
        <label>Date de publication:</label>
        <input type="text" name="series[<?php echo $serie_index; ?>][books][<?php echo $book_index; ?>][publication_date]" value="<?php echo esc_attr($book['publication_date'] ?? ''); ?>" placeholder="Ex: 2024">
        <label>Lien Amazon:</label>
        <input type="url" name="series[<?php echo $serie_index; ?>][books][<?php echo $book_index; ?>][amazon]" value="<?php echo esc_url($book['amazon'] ?? ''); ?>" placeholder="https://...">
        <label>Lien Kindle:</label>
        <input type="url" name="series[<?php echo $serie_index; ?>][books][<?php echo $book_index; ?>][kindle]" value="<?php echo esc_url($book['kindle'] ?? ''); ?>" placeholder="https://...">
        <label>Lien Audible:</label>
        <input type="url" name="series[<?php echo $serie_index; ?>][books][<?php echo $book_index; ?>][audible]" value="<?php echo esc_url($book['audible'] ?? ''); ?>" placeholder="https://...">
        <div class="book-actions">
            <button type="button" class="btn-move" onclick="moveBookUp(this)">⬆️ Monter</button>
            <button type="button" class="btn-move" onclick="moveBookDown(this)">⬇️ Descendre</button>
            <button type="button" class="btn-remove" onclick="this.closest('.book-item').remove(); updateBookNumbers(<?php echo $serie_index; ?>)">🗑️ Supprimer</button>
        </div>
    </div>
    <?php
}

function render_standalone_editor($index, $standalone) {
    ?>
    <div class="standalone-section">
        <div class="section-header">
            <h4 class="section-title"><span class="book-number">#<?php echo ($index + 1); ?></span>📖 Livre indépendant <?php echo ($index + 1); ?></h4>
            <div>
                <button type="button" class="btn-move" onclick="moveStandaloneUp(this)">⬆️ Monter</button>
                <button type="button" class="btn-move" onclick="moveStandaloneDown(this)">⬇️ Descendre</button>
                <button type="button" class="btn-remove" onclick="this.closest('.standalone-section').remove(); updateStandaloneNumbers()">🗑️ Supprimer</button>
            </div>
        </div>
        <label>Titre:</label>
        <input type="text" name="standalones[<?php echo $index; ?>][title]" value="<?php echo esc_attr($standalone['title'] ?? ''); ?>" placeholder="Titre du livre">
        <label>Date de publication:</label>
        <input type="text" name="standalones[<?php echo $index; ?>][publication_date]" value="<?php echo esc_attr($standalone['publication_date'] ?? ''); ?>" placeholder="Ex: 2024">
        <label>Lien Amazon:</label>
        <input type="url" name="standalones[<?php echo $index; ?>][amazon]" value="<?php echo esc_url($standalone['amazon'] ?? ''); ?>" placeholder="https://...">
        <label>Lien Kindle:</label>
        <input type="url" name="standalones[<?php echo $index; ?>][kindle]" value="<?php echo esc_url($standalone['kindle'] ?? ''); ?>" placeholder="https://...">
        <label>Lien Audible:</label>
        <input type="url" name="standalones[<?php echo $index; ?>][audible]" value="<?php echo esc_url($standalone['audible'] ?? ''); ?>" placeholder="https://...">
    </div>
    <?php
}

// Sauvegarder les données lors de la mise à jour du post
function save_auteur_books_data($post_id) {
    // Vérifications de sécurité
    if (!isset($_POST['auteur_books_nonce']) || !wp_verify_nonce($_POST['auteur_books_nonce'], 'auteur_books_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Récupérer et nettoyer les données
    $books_data = [
        'series' => [],
        'standalones' => []
    ];

    // Traiter les séries
    if (isset($_POST['series']) && is_array($_POST['series'])) {
        foreach ($_POST['series'] as $serie) {
            if (empty($serie['title'])) continue;

            $serie_data = [
                'title' => sanitize_text_field($serie['title']),
                'books' => []
            ];

            if (isset($serie['books']) && is_array($serie['books'])) {
                foreach ($serie['books'] as $book) {
                    if (empty($book['title'])) continue;

                    $serie_data['books'][] = [
                        'title' => sanitize_text_field($book['title']),
                        'publication_date' => sanitize_text_field($book['publication_date'] ?? ''),
                        'amazon' => esc_url_raw($book['amazon'] ?? ''),
                        'kindle' => esc_url_raw($book['kindle'] ?? ''),
                        'audible' => esc_url_raw($book['audible'] ?? '')
                    ];
                }
            }

            if (!empty($serie_data['books'])) {
                $books_data['series'][] = $serie_data;
            }
        }
    }

    // Traiter les livres indépendants
    if (isset($_POST['standalones']) && is_array($_POST['standalones'])) {
        foreach ($_POST['standalones'] as $standalone) {
            if (empty($standalone['title'])) continue;

            $books_data['standalones'][] = [
                'title' => sanitize_text_field($standalone['title']),
                'publication_date' => sanitize_text_field($standalone['publication_date'] ?? ''),
                'amazon' => esc_url_raw($standalone['amazon'] ?? ''),
                'kindle' => esc_url_raw($standalone['kindle'] ?? ''),
                'audible' => esc_url_raw($standalone['audible'] ?? '')
            ];
        }
    }

    // Sauvegarder en JSON avec échappement correct
    $json_data = json_encode($books_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    // Vérifier que l'encodage a réussi
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Erreur JSON lors de la sauvegarde: ' . json_last_error_msg());
        return;
    }
    
    update_post_meta($post_id, 'auteur_books_data', $json_data);
}
add_action('save_post_auteur', 'save_auteur_books_data');


// === Fonction pour réparer les données JSON corrompues ===
function repair_auteur_json_data() {
    // Cette fonction peut être appelée manuellement si nécessaire
    $auteurs = get_posts([
        'post_type' => 'auteur',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    $repaired = 0;
    foreach ($auteurs as $auteur) {
        $books_data = get_post_meta($auteur->ID, 'auteur_books_data', true);
        
        if (empty($books_data)) continue;
        
        // Essayer de décoder
        $data = json_decode($books_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Il y a une erreur, essayer de réparer
            // Remplacer les apostrophes non échappées
            $books_data = str_replace("'", "'", $books_data);
            $books_data = str_replace("\'", "'", $books_data);
            
            // Réencoder proprement
            $data = json_decode($books_data, true);
            
            if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                // Ré-encoder correctement
                $json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                update_post_meta($auteur->ID, 'auteur_books_data', $json_data);
                $repaired++;
            }
        }
    }
    
    return $repaired;
}

// Ajouter un bouton dans l'admin pour réparer les données
function add_repair_json_button() {
    add_submenu_page(
        'edit.php?post_type=auteur',
        'Réparer les données JSON',
        'Réparer JSON',
        'manage_options',
        'repair-auteurs-json',
        'repair_auteurs_json_page'
    );
}
add_action('admin_menu', 'add_repair_json_button');

function repair_auteurs_json_page() {
    if (isset($_POST['repair_json'])) {
        if (!wp_verify_nonce($_POST['repair_nonce'], 'repair_auteurs_json')) {
            echo '<div class="notice notice-error"><p>Erreur de sécurité.</p></div>';
        } else {
            $repaired = repair_auteur_json_data();
            echo '<div class="notice notice-success"><p>✅ ' . $repaired . ' auteur(s) réparé(s)!</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Réparer les données JSON</h1>
        
        <div class="card">
            <h2>À quoi sert cet outil ?</h2>
            <p>Si vous voyez des erreurs "Erreur JSON: Syntax error" lors de l'édition d'un auteur, cet outil va corriger automatiquement les données corrompues.</p>
            <p><strong>Problèmes corrigés :</strong></p>
            <ul>
                <li>Apostrophes mal échappées (L'Écho, L'Adieu, etc.)</li>
                <li>Caractères spéciaux mal encodés</li>
                <li>Format JSON invalide</li>
            </ul>
        </div>

        <form method="post">
            <?php wp_nonce_field('repair_auteurs_json', 'repair_nonce'); ?>
            <p>
                <input type="submit" name="repair_json" class="button button-primary" value="🔧 Réparer toutes les données JSON">
            </p>
        </form>

        <div class="card">
            <h3>État actuel</h3>
            <?php
            $count = wp_count_posts('auteur');
            echo '<p>Auteurs dans la base de données : <strong>' . $count->publish . '</strong></p>';
            ?>
        </div>
    </div>
    <?php
}


// === Page d'import dans l'admin ===
function add_import_menu() {
    add_submenu_page(
        'edit.php?post_type=auteur',
        'Import CSV',
        'Import CSV',
        'manage_options',
        'import-auteurs-csv',
        'import_auteurs_page'
    );
}
add_action('admin_menu', 'add_import_menu');

function import_auteurs_page() {
    // Traitement de l'import AVANT l'affichage du formulaire
    if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        if (!wp_verify_nonce($_POST['import_nonce'], 'import_auteurs_csv')) {
            echo '<div class="notice notice-error"><p>Erreur de sécurité.</p></div>';
        } else {
            process_auteur_csv_import($_FILES['csv_file']['tmp_name']);
        }
    }
    ?>
    <div class="wrap">
        <h1>Import des Auteurs via CSV</h1>
        
        <div class="card">
            <h2>Instructions</h2>
            <p>Votre fichier CSV doit contenir les colonnes suivantes :</p>
            <ul>
                <li><strong>post_title</strong> : Nom de l'auteur (obligatoire)</li>
                <li><strong>bio</strong> : Biographie de l'auteur</li>
                <li><strong>genre</strong> : Genres séparés par | (ex: Horreur|Thriller)</li>
            </ul>
            
            <h3>📚 Pour les séries (illimité) :</h3>
            <ul>
                <li><strong>serie_X_title</strong> : Titre de la série X</li>
                <li><strong>serie_X_book_Y_title</strong> : Titre du livre Y de la série X</li>
                <li><strong>serie_X_book_Y_amazon/kindle/audible/date</strong> : Infos du livre Y</li>
            </ul>
            
            <h3>📖 Pour les livres indépendants (illimité) :</h3>
            <ul>
                <li><strong>standalone_X_title/amazon/kindle/audible/date</strong> : Livres indépendants</li>
            </ul>
            
            <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h4>✅ Fonctionnalités :</h4>
                <ul>
                    <li><strong>Séries illimitées</strong> : serie_1, serie_2, serie_3... à l'infini</li>
                    <li><strong>Livres illimités par série</strong> : book_1, book_2, book_3... sans limite</li>
                    <li><strong>Livres indépendants illimités</strong> : standalone_1, standalone_2... sans limite</li>
                    <li><strong>Détection automatique</strong> : Le système détecte automatiquement toutes vos colonnes</li>
                </ul>
            </div>
            
            <p><strong>Exemples de colonnes :</strong></p>
            <code>serie_1_title, serie_1_book_1_title, serie_1_book_1_date, serie_1_book_2_title, serie_2_title, serie_2_book_1_title, standalone_1_title, standalone_2_title</code>
        </div>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('import_auteurs_csv', 'import_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Fichier CSV</th>
                    <td>
                        <input type="file" name="csv_file" accept=".csv" required>
                        <p class="description">Sélectionnez votre fichier CSV à importer.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Importer le CSV', 'primary', 'import_csv'); ?>
        </form>

        <div class="card">
            <h3>État actuel</h3>
            <?php
            $count = wp_count_posts('auteur');
            echo '<p>Auteurs dans la base de données : <strong>' . $count->publish . '</strong></p>';
            ?>
            <p><a href="<?php echo admin_url('edit.php?post_type=auteur'); ?>" class="button">Voir tous les auteurs</a></p>
        </div>
    </div>
    <?php
}

function process_auteur_csv_import($csv_file) {
    if (!file_exists($csv_file) || filesize($csv_file) == 0) {
        echo '<div class="notice notice-error"><p>Fichier CSV introuvable ou vide.</p></div>';
        return;
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) {
        echo '<div class="notice notice-error"><p>Impossible d\'ouvrir le fichier CSV.</p></div>';
        return;
    }

    $headers = fgetcsv($handle, 0, ',');
    if (!$headers) {
        echo '<div class="notice notice-error"><p>Impossible de lire les en-têtes du fichier.</p></div>';
        fclose($handle);
        return;
    }

    $headers = array_map(function($header) {
        return trim($header, " \t\n\r\0\x0B\xEF\xBB\xBF");
    }, $headers);

    echo '<div class="notice notice-info">';
    echo '<p><strong>En-têtes détectées (' . count($headers) . ' colonnes) :</strong><br>';
    echo '<code>' . implode(' | ', $headers) . '</code></p>';
    echo '</div>';

    if (!in_array('post_title', $headers)) {
        echo '<div class="notice notice-error"><p>La colonne "post_title" est obligatoire.</p></div>';
        fclose($handle);
        return;
    }

    echo '<div class="notice notice-info"><p>Début de l\'import...</p></div>';

    $imported = 0;
    $errors = 0;
    $row_number = 1;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $row_number++;
        
        if (empty(array_filter($row))) continue;
        
        while (count($row) < count($headers)) {
            $row[] = '';
        }
        
        if (count($row) > count($headers)) {
            $row = array_slice($row, 0, count($headers));
        }

        $data = array_combine($headers, $row);
        $title = trim($data['post_title'] ?? '');

        if (empty($title)) {
            echo '<div class="notice notice-warning"><p>Ligne ' . $row_number . ' : titre manquant, ignorée.</p></div>';
            $errors++;
            continue;
        }

        $existing = get_posts([
            'post_type' => 'auteur',
            'title' => $title,
            'post_status' => 'any',
            'numberposts' => 1
        ]);

        if (!empty($existing)) {
            echo '<div class="notice notice-info"><p>Auteur "' . esc_html($title) . '" existe déjà, mis à jour.</p></div>';
            $post_id = $existing[0]->ID;
        } else {
            $post_data = [
                'post_title' => sanitize_text_field($title),
                'post_type' => 'auteur',
                'post_status' => 'publish',
                'post_content' => wp_kses_post($data['bio'] ?? ''),
                'post_author' => get_current_user_id(),
            ];

            $post_id = wp_insert_post($post_data, true);

            if (is_wp_error($post_id)) {
                echo '<div class="notice notice-error"><p>Erreur création "' . esc_html($title) . '" : ' . $post_id->get_error_message() . '</p></div>';
                $errors++;
                continue;
            }
        }

        if (!empty($data['genre'])) {
            $genres = array_map('trim', explode('|', $data['genre']));
            wp_set_post_terms($post_id, $genres, 'genre_auteur');
        }

        $books_data = [
            'series' => [],
            'standalones' => []
        ];

        $series_found = [];
        
        foreach ($headers as $header) {
            if (preg_match('/^serie_(\d+)_title$/', $header, $matches)) {
                $series_number = (int)$matches[1];
                if (!in_array($series_number, $series_found)) {
                    $series_found[] = $series_number;
                }
            }
        }
        
        foreach ($series_found as $s) {
            $serie_title = trim($data["serie_{$s}_title"] ?? '');
            if (empty($serie_title)) continue;

            $serie = [
                'title' => sanitize_text_field($serie_title),
                'books' => []
            ];

            $books_in_series = [];
            foreach ($headers as $header) {
                if (preg_match("/^serie_{$s}_book_(\d+)_title$/", $header, $matches)) {
                    $book_number = (int)$matches[1];
                    if (!in_array($book_number, $books_in_series)) {
                        $books_in_series[] = $book_number;
                    }
                }
            }
            
            sort($books_in_series);
            
            foreach ($books_in_series as $b) {
                $book_title = trim($data["serie_{$s}_book_{$b}_title"] ?? '');
                if (empty($book_title)) continue;

                $book = [
                    'title' => sanitize_text_field($book_title),
                    'amazon' => esc_url_raw(trim($data["serie_{$s}_book_{$b}_amazon"] ?? '')),
                    'kindle' => esc_url_raw(trim($data["serie_{$s}_book_{$b}_kindle"] ?? '')),
                    'audible' => esc_url_raw(trim($data["serie_{$s}_book_{$b}_audible"] ?? '')),
                    'publication_date' => sanitize_text_field(trim($data["serie_{$s}_book_{$b}_date"] ?? ''))
                ];

                $serie['books'][] = $book;
            }

            if (!empty($serie['books'])) {
                $books_data['series'][] = $serie;
            }
        }

        $standalones_found = [];
        
        foreach ($headers as $header) {
            if (preg_match('/^standalone_(\d+)_title$/', $header, $matches)) {
                $standalone_number = (int)$matches[1];
                if (!in_array($standalone_number, $standalones_found)) {
                    $standalones_found[] = $standalone_number;
                }
            }
        }
        
        sort($standalones_found);
        
        foreach ($standalones_found as $st) {
            $standalone_title = trim($data["standalone_{$st}_title"] ?? '');
            if (empty($standalone_title)) continue;

            $standalone = [
                'title' => sanitize_text_field($standalone_title),
                'amazon' => esc_url_raw(trim($data["standalone_{$st}_amazon"] ?? '')),
                'kindle' => esc_url_raw(trim($data["standalone_{$st}_kindle"] ?? '')),
                'audible' => esc_url_raw(trim($data["standalone_{$st}_audible"] ?? '')),
                'publication_date' => sanitize_text_field(trim($data["standalone_{$st}_date"] ?? ''))
            ];

            $books_data['standalones'][] = $standalone;
        }

        $json_data = json_encode($books_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Vérifier que l'encodage a réussi
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo '<div class="notice notice-error"><p>Erreur JSON pour "' . esc_html($title) . '": ' . json_last_error_msg() . '</p></div>';
            $errors++;
            continue;
        }
        
        update_post_meta($post_id, 'auteur_books_data', $json_data);

        $series_count = count($books_data['series']);
        $standalones_count = count($books_data['standalones']);
        $total_books = 0;
        foreach ($books_data['series'] as $serie) {
            $total_books += count($serie['books']);
        }
        $total_books += $standalones_count;
        
        echo '<div class="notice notice-success">';
        echo '<p>✅ Auteur "' . esc_html($title) . '" importé/mis à jour avec succès!</p>';
        echo '<p><strong>📊 Statistiques :</strong> ' . $series_count . ' série(s), ' . $standalones_count . ' livre(s) indépendant(s), ' . $total_books . ' livre(s) au total</p>';
        if ($series_count > 0) {
            echo '<p><strong>📚 Séries :</strong> ';
            $serie_names = array_map(function($serie) {
                return $serie['title'] . ' (' . count($serie['books']) . ' livres)';
            }, $books_data['series']);
            echo implode(', ', $serie_names) . '</p>';
        }
        echo '</div>';
        
        $imported++;
    }

    fclose($handle);

    echo '<div class="notice notice-success"><h3>Import terminé!</h3>';
    echo '<p><strong>' . $imported . '</strong> auteur(s) traité(s), <strong>' . $errors . '</strong> erreur(s).</p>';
    echo '<p><a href="' . admin_url('edit.php?post_type=auteur') . '" class="button button-primary">Voir les auteurs importés</a></p>';
    echo '</div>';

    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}


// === SYSTÈME DE TRACKING DES VUES D'AUTEURS ===

function track_auteur_views() {
    if (is_singular('auteur')) {
        $post_id = get_the_ID();
        $current_views = get_post_meta($post_id, 'auteur_views_count', true);
        $current_views = $current_views ? (int)$current_views : 0;
        update_post_meta($post_id, 'auteur_views_count', $current_views + 1);
    }
}
add_action('wp_head', 'track_auteur_views');

function get_top_viewed_auteurs($limit = 6) {
    $args = array(
        'post_type' => 'auteur',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'meta_key' => 'auteur_views_count',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    );
    
    return get_posts($args);
}

function display_top_auteurs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 4,
        'title' => 'Auteurs en tendance',
    ), $atts);
    
    $top_auteurs = get_top_viewed_auteurs($atts['limit']);
    
    if (empty($top_auteurs)) {
        return '<p>Aucun auteur disponible pour le moment.</p>';
    }
    
    ob_start();
    ?>
    <div class="top-auteurs-section">
        <h2 class="section-title"><?php echo esc_html($atts['title']); ?></h2>
        <div class="auteurs-grid">
            <?php foreach ($top_auteurs as $auteur) : 
                $views = get_post_meta($auteur->ID, 'auteur_views_count', true);
                $views = $views ? (int)$views : 0;
                $thumbnail = get_the_post_thumbnail_url($auteur->ID, 'medium');
                $permalink = get_permalink($auteur->ID);
                
                $genres = get_the_terms($auteur->ID, 'genre_auteur');
                $genre_names = array();
                if ($genres && !is_wp_error($genres)) {
                    foreach ($genres as $genre) {
                        $genre_names[] = $genre->name;
                    }
                }
                ?>
                <div class="auteur-card">
                    <a href="<?php echo esc_url($permalink); ?>" class="auteur-link">
                        <?php if ($thumbnail) : ?>
                            <div class="auteur-thumbnail">
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($auteur->post_title); ?>">
                                <div class="views-badge">👁️ <?php echo number_format($views); ?> vues</div>
                            </div>
                        <?php else : ?>
                            <div class="auteur-thumbnail no-image">
                                <div class="placeholder-icon">📚</div>
                                <div class="views-badge">👁️ <?php echo number_format($views); ?> vues</div>
                            </div>
                        <?php endif; ?>
                        <div class="auteur-info">
                            <h3 class="auteur-name"><?php echo esc_html($auteur->post_title); ?></h3>
                            <?php if (!empty($genre_names)) : ?>
                                <p class="auteur-genres"><?php echo esc_html(implode(', ', $genre_names)); ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
        .top-auteurs-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 40px;
            color: #333;
            font-weight: 700;
        }
        
        .auteurs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .auteur-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .auteur-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .auteur-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .auteur-thumbnail {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .auteur-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .auteur-card:hover .auteur-thumbnail img {
            transform: scale(1.1);
        }
        
        .auteur-thumbnail.no-image {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .placeholder-icon {
            font-size: 80px;
            opacity: 0.5;
        }
        
        .views-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .auteur-info {
            padding: 20px;
        }
        
        .auteur-name {
            font-size: 1.3em;
            margin: 0 0 10px 0;
            color: #484747a9;
            font-weight: 600;
        }
        
        .auteur-genres {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .auteurs-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .auteur-thumbnail {
                height: 200px;
            }
            
            .section-title {
                font-size: 1.8em;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('top_auteurs', 'display_top_auteurs_shortcode');

function add_auteur_views_column($columns) {
    $columns['views'] = '👁️ Vues';
    return $columns;
}
add_filter('manage_auteur_posts_columns', 'add_auteur_views_column');

function display_auteur_views_column($column, $post_id) {
    if ($column === 'views') {
        $views = get_post_meta($post_id, 'auteur_views_count', true);
        echo $views ? number_format((int)$views) : '0';
    }
}
add_action('manage_auteur_posts_custom_column', 'display_auteur_views_column', 10, 2);

function make_views_column_sortable($columns) {
    $columns['views'] = 'views';
    return $columns;
}
add_filter('manage_edit-auteur_sortable_columns', 'make_views_column_sortable');

function sort_by_views_column($query) {
    if (!is_admin()) return;
    
    $orderby = $query->get('orderby');
    if ('views' === $orderby) {
        $query->set('meta_key', 'auteur_views_count');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'sort_by_views_column');

// ============================================
// AUTOMATISATION SEO RANK MATH POUR AUTEURS
// ============================================

// 1. Forcer Rank Math à reconnaître le CPT Auteur
function rankmath_support_for_auteur() {
    // Ajouter le support des custom fields
    add_post_type_support('auteur', 'custom-fields');
    
    // Activer Rank Math pour le CPT
    add_filter('rank_math/sitemap/enable_cpt_auteur', '__return_true');
}
add_action('init', 'rankmath_support_for_auteur', 99);

// 2. Générer automatiquement les métadonnées SEO pour chaque auteur
function auto_generate_auteur_seo_meta($post_id, $post, $update) {
    // Vérifier que c'est bien un auteur
    if ($post->post_type !== 'auteur') {
        return;
    }
    
    // Ne pas exécuter sur les révisions ou auto-save
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    // Récupérer le nom de l'auteur
    $author_name = $post->post_title;
    
    // Générer le titre SEO optimisé (60 caractères max recommandé)
    $seo_title = "Ordre des livres de {$author_name} | Guide chronologique";
    
    // Si trop long, raccourcir
    if (strlen($seo_title) > 60) {
        $seo_title = "Ordre livres {$author_name} | Ordre-Livres.fr";
    }
    
    // Générer la meta description (155 caractères max recommandé)
    $meta_desc = "Découvrez l'ordre chronologique complet des livres et séries de {$author_name}. Guide de lecture avec dates de publication et liens Amazon France.";
    
    // Générer le focus keyword
    $focus_keyword = "ordre livres " . strtolower($author_name);
    
    // Récupérer les genres pour enrichir le contenu
    $genres = get_the_terms($post_id, 'genre_auteur');
    $genre_names = array();
    if ($genres && !is_wp_error($genres)) {
        foreach ($genres as $genre) {
            $genre_names[] = $genre->name;
        }
    }
    
    // Si des genres existent, les ajouter à la description
    if (!empty($genre_names)) {
        $genres_string = implode(', ', $genre_names);
        $meta_desc = "Découvrez l'ordre chronologique des livres de {$author_name} ({$genres_string}). Guide de lecture complet avec dates et liens Amazon.";
    }
    
    // Tronquer la description si trop longue
    if (strlen($meta_desc) > 155) {
        $meta_desc = substr($meta_desc, 0, 152) . '...';
    }
    
    // Sauvegarder UNIQUEMENT si les champs sont vides (ne pas écraser les modifications manuelles)
    if (empty(get_post_meta($post_id, 'rank_math_title', true))) {
        update_post_meta($post_id, 'rank_math_title', $seo_title);
    }
    
    if (empty(get_post_meta($post_id, 'rank_math_description', true))) {
        update_post_meta($post_id, 'rank_math_description', $meta_desc);
    }
    
    if (empty(get_post_meta($post_id, 'rank_math_focus_keyword', true))) {
        update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
    }
    
    // Définir le Schema Type à "Person" automatiquement
    if (empty(get_post_meta($post_id, 'rank_math_schema_Article', true))) {
        update_post_meta($post_id, 'rank_math_rich_snippet', 'article');
        
        // Ajouter les données Schema Person
        $schema_data = array(
            '@type' => 'Person',
            'name' => $author_name,
            'description' => wp_strip_all_tags($post->post_content),
        );
        
        update_post_meta($post_id, 'rank_math_schema_Person', wp_json_encode($schema_data));
    }
}
add_action('save_post', 'auto_generate_auteur_seo_meta', 10, 3);

// 3. Ajouter des mots-clés additionnels automatiques basés sur les genres
function auto_generate_additional_keywords($post_id) {
    if (get_post_type($post_id) !== 'auteur') {
        return;
    }
    
    // Ne mettre à jour que si vide
    if (!empty(get_post_meta($post_id, 'rank_math_additional_keywords', true))) {
        return;
    }
    
    $author_name = get_the_title($post_id);
    $genres = get_the_terms($post_id, 'genre_auteur');
    
    $additional_keywords = array(
        "chronologie {$author_name}",
        "bibliographie {$author_name}",
        "livres {$author_name}",
        "séries {$author_name}"
    );
    
    // Ajouter les genres comme mots-clés
    if ($genres && !is_wp_error($genres)) {
        foreach ($genres as $genre) {
            $additional_keywords[] = strtolower($genre->name) . " " . strtolower($author_name);
        }
    }
    
    // Limiter à 5 mots-clés max
    $additional_keywords = array_slice($additional_keywords, 0, 5);
    
    update_post_meta($post_id, 'rank_math_additional_keywords', implode(', ', $additional_keywords));
}
add_action('save_post', 'auto_generate_additional_keywords', 20);

// 4. Personnaliser l'URL slug automatiquement (format: prenom-nom)
function auto_generate_auteur_slug($slug, $post_ID, $post_status, $post_type) {
    if ($post_type !== 'auteur') {
        return $slug;
    }
    
    // Ne modifier que si le slug est vide
    if (!empty($slug)) {
        return $slug;
    }
    
    $title = get_the_title($post_ID);
    
    // Créer un slug optimisé SEO
    $slug = sanitize_title($title);
    
    return $slug;
}
add_filter('wp_unique_post_slug', 'auto_generate_auteur_slug', 10, 4);

// 5. Ajouter un message admin pour confirmer la génération SEO
function auteur_seo_admin_notice() {
    $screen = get_current_screen();
    
    if ($screen->post_type === 'auteur' && isset($_GET['message']) && $_GET['message'] == 1) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✅ SEO automatique généré !</strong> Les métadonnées Rank Math ont été créées automatiquement. Vous pouvez les personnaliser ci-dessous si nécessaire.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'auteur_seo_admin_notice');

// 6. Ajouter une colonne "SEO Score" dans l'admin des auteurs
function add_seo_score_column($columns) {
    $columns['seo_score'] = '📊 SEO';
    return $columns;
}
add_filter('manage_auteur_posts_columns', 'add_seo_score_column');

function display_seo_score_column($column, $post_id) {
    if ($column === 'seo_score') {
        $has_title = !empty(get_post_meta($post_id, 'rank_math_title', true));
        $has_desc = !empty(get_post_meta($post_id, 'rank_math_description', true));
        $has_keyword = !empty(get_post_meta($post_id, 'rank_math_focus_keyword', true));
        
        $score = 0;
        if ($has_title) $score += 33;
        if ($has_desc) $score += 33;
        if ($has_keyword) $score += 34;
        
        if ($score >= 90) {
            echo '🟢 ' . $score . '%';
        } elseif ($score >= 60) {
            echo '🟡 ' . $score . '%';
        } else {
            echo '🔴 ' . $score . '%';
        }
    }
}
add_action('manage_auteur_posts_custom_column', 'display_seo_score_column', 10, 2);

// Ajouter rel="sponsored" automatiquement aux liens Amazon
function add_sponsored_to_amazon_links($content) {
    // Remplacer tous les liens Amazon
    $content = preg_replace_callback(
        '/<a(.*?)href=["\'](.*?amazon\.[a-z.]+.*?)["\']([^>]*)>/i',
        function($matches) {
            $before = $matches[1];
            $url = $matches[2];
            $after = $matches[3];
            
            // Vérifier si rel existe déjà
            if (strpos($after, 'rel=') !== false) {
                // Ajouter sponsored au rel existant
                $after = preg_replace('/rel=["\']([^"\']*)["\']/', 'rel="$1 sponsored"', $after);
            } else {
                // Ajouter un nouveau rel
                $after .= ' rel="noopener sponsored"';
            }
            
            // Ajouter target="_blank" si absent
            if (strpos($after, 'target=') === false) {
                $after .= ' target="_blank"';
            }
            
            return '<a' . $before . 'href="' . $url . '"' . $after . '>';
        },
        $content
    );
    
    return $content;
}
add_filter('the_content', 'add_sponsored_to_amazon_links');


// === BARRE DE RECHERCHE DANS LE HEADER ===
function custom_header_search() {
    ?>
    <div class="custom-header-search">
        <?php get_search_form(); ?>
    </div>
    
    <style>
        /* Container de la recherche */
        .custom-header-search {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #3c4043;
        }
        
        /* Formulaire de recherche */
        .custom-header-search .search-form {
            width: 100%;
            max-width: 600px;
            position: relative;
        }
        
        /* Champ de saisie */
        .custom-header-search .search-field {
            width: 100%;
            padding: 12px 50px 12px 20px;
            font-size: 16px;
            border: 2px solid #3c4043;
            border-radius: 30px;
            outline: none;
            transition: all 0.3s ease;
            background: white;
        }
        
        .custom-header-search .search-field:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        /* Bouton de recherche */
        .custom-header-search .search-submit,
        .custom-header-search .ast-search-submit {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #FF6B35;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .custom-header-search .search-submit:hover,
        .custom-header-search .ast-search-submit:hover {
            background: #E55A2B;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .custom-header-search {
                padding: 10px 15px;
            }
            
            .custom-header-search .search-field {
                font-size: 14px;
                padding: 10px 45px 10px 15px;
            }
            
            .custom-header-search .search-submit,
            .custom-header-search .ast-search-submit {
                padding: 6px 15px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .custom-header-search .search-form {
                max-width: 100%;
            }
        }
    </style>
    <?php
}
add_action('astra_header_after', 'custom_header_search');