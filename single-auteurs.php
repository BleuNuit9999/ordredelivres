<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $author_name = get_the_title();
        echo '<div class="auteur-container">';
        echo '<h1 class="auteur-title">Ordre des livres de ' . esc_html($author_name) . '</h1>';

        // Afficher la biographie si elle existe
        if (get_the_content()) {
            echo '<div class="auteur-bio">';
            echo '<h2>Biographie</h2>';
            the_content();
            echo '</div>';
        }

        // Récupérer les genres
        $genres = get_the_terms(get_the_ID(), 'genre');
        if ($genres && !is_wp_error($genres)) {
            echo '<div class="auteur-genres">';
            echo '<h3>Genres : ';
            $genre_names = array();
            foreach ($genres as $genre) {
                $genre_names[] = esc_html($genre->name);
            }
            echo implode(', ', $genre_names);
            echo '</h3>';
            echo '</div>';
        }

        // Récupérer et décoder les données JSON des livres
        $books_json = get_post_meta(get_the_ID(), 'books_json', true);
        
        if (empty($books_json)) {
            echo '<div class="notice"><p>Aucune donnée de livre trouvée pour cet auteur.</p></div>';
        } else {
            $data = json_decode($books_json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<div class="notice notice-error"><p>Erreur lors du décodage des données JSON : ' . json_last_error_msg() . '</p></div>';
            } else {
                // Afficher les séries
                if (!empty($data['series'])) {
                    foreach ($data['series'] as $serie) {
                        if (!empty($serie['books'])) {
                            echo '<h2 class="series-title">Série : ' . esc_html($serie['title']) . '</h2>';
                            echo '<table class="books-table">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Titre</th>';
                            echo '<th>Format Papier</th>';
                            echo '<th>Format Kindle</th>';
                            echo '<th>Format Audible</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            foreach ($serie['books'] as $book) {
                                echo '<tr>';
                                echo '<td class="book-title">' . esc_html($book['title']) . '</td>';
                                echo '<td class="book-link">' . 
                                     (!empty($book['amazon']) ? 
                                      '<a href="' . esc_url($book['amazon']) . '" target="_blank" rel="noopener">📚 Papier</a>' : 
                                      '<span class="unavailable">-</span>') . 
                                     '</td>';
                                echo '<td class="book-link">' . 
                                     (!empty($book['kindle']) ? 
                                      '<a href="' . esc_url($book['kindle']) . '" target="_blank" rel="noopener">📱 Kindle</a>' : 
                                      '<span class="unavailable">-</span>') . 
                                     '</td>';
                                echo '<td class="book-link">' . 
                                     (!empty($book['audible']) ? 
                                      '<a href="' . esc_url($book['audible']) . '" target="_blank" rel="noopener">🎧 Audible</a>' : 
                                      '<span class="unavailable">-</span>') . 
                                     '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        }
                    }
                }

                // Afficher les livres indépendants
                if (!empty($data['standalones'])) {
                    echo '<h2 class="series-title">Œuvres hors série de ' . esc_html($author_name) . '</h2>';
                    echo '<table class="books-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Titre</th>';
                    echo '<th>Format Papier</th>';
                    echo '<th>Format Kindle</th>';
                    echo '<th>Format Audible</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    foreach ($data['standalones'] as $book) {
                        echo '<tr>';
                        echo '<td class="book-title">' . esc_html($book['title']) . '</td>';
                        echo '<td class="book-link">' . 
                             (!empty($book['amazon']) ? 
                              '<a href="' . esc_url($book['amazon']) . '" target="_blank" rel="noopener">📚 Papier</a>' : 
                              '<span class="unavailable">-</span>') . 
                             '</td>';
                        echo '<td class="book-link">' . 
                             (!empty($book['kindle']) ? 
                              '<a href="' . esc_url($book['kindle']) . '" target="_blank" rel="noopener">📱 Kindle</a>' : 
                              '<span class="unavailable">-</span>') . 
                             '</td>';
                        echo '<td class="book-link">' . 
                             (!empty($book['audible']) ? 
                              '<a href="' . esc_url($book['audible']) . '" target="_blank" rel="noopener">🎧 Audible</a>' : 
                              '<span class="unavailable">-</span>') . 
                             '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                }

                // Si aucune série ni livre indépendant
                if (empty($data['series']) && empty($data['standalones'])) {
                    echo '<div class="notice"><p>Aucun livre enregistré pour cet auteur.</p></div>';
                }
            }
        }
        
        echo '</div>'; // Fermer auteur-container

    endwhile;
endif;

get_footer();
?>