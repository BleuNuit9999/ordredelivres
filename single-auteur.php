<?php
get_header();

// CSS pour un affichage propre
?>
<style>
.auteur-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #e5e5e5;
    min-height: 100vh;
}

.auteur-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(255, 255, 255, 1);
}

.auteur-title {
    font-size: 2.5em;
    margin: 0 0 15px 0;
    text-shadow: 2px 2px 4px rgba(248, 247, 247, 0.98);
}

.auteur-genres {
    font-size: 1.1em;
    opacity: 0.9;
}

.auteur-bio {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
    border-left: 4px solid #3c4043;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.series-section, .standalones-section {
    margin: 30px 0;
}

.series-title {
    color: #3c4043;
    border-bottom: 3px solid #3c4043;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-size: 1.8em;
}

.books-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 30px;
    background: white;
}

.books-table th {
    background: #3c4043;
    color: white;
    padding: 15px;
    text-align: center;
    font-weight: 600;
}

.books-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

.books-table tr:nth-child(even) {
    background-color: #f8f9fa;
}

.book-title {
    font-weight: 500;
    color: #333;
    text-align: center !important;
}

.book-date {
    color: #666;
    font-size: 0.9em;
}

/* Amazon - Priorité maximale avec orange vif */
.book-link .amazon-link {
    display: inline-block;
    padding: 8px 16px;
    background: #FF6B35;
    color: white;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.95em;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
}

.book-link .amazon-link:hover {
    background: #E55A2B;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
}

/* Kindle - Couleur secondaire */
.book-link .kindle-link {
    display: inline-block;
    padding: 6px 12px;
    background: #2c3e50;
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-size: 0.85em;
    transition: all 0.3s ease;
}

.book-link .kindle-link:hover {
    background: #1a252f;
}

/* Audible - Couleur tertiaire */
.book-link .audible-link {
    display: inline-block;
    padding: 6px 12px;
    background: #7f8c8d;
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-size: 0.85em;
    transition: all 0.3s ease;
}

.book-link .audible-link:hover {
    background: #6c7b7d;
}

.unavailable {
    color: #999;
    font-style: italic;
}

.no-books {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    color: #666;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<?php
if (have_posts()) :
    while (have_posts()) : the_post();
        $author_name = get_the_title();
        $author_id = get_the_ID();
        ?>
        
        <div class="auteur-container">
    <?php
    if (function_exists('rank_math_the_breadcrumbs')) {
        echo '<div class="breadcrumbs-wrapper" style="margin-bottom: 20px; padding: 10px 0; font-size: 14px; color: #666;">';
        rank_math_the_breadcrumbs();
        echo '</div>';
    }
    ?>
            <!-- En-tête de l'auteur -->
            <div class="auteur-header">
                <h1 class="auteur-title">Ordre des livres de <?php echo esc_html($author_name); ?></h1>
                
                <?php
                // Afficher les genres
                $genres = get_the_terms($author_id, 'genre_auteur');
                if ($genres && !is_wp_error($genres)) {
                    $genre_names = array_map(function($term) {
                        return esc_html($term->name);
                    }, $genres);
                    echo '<div class="auteur-genres">📚 ' . implode(' • ', $genre_names) . '</div>';
                }
                ?>
            </div>

            <!-- Biographie - déplacée après les tableaux -->

            <?php
            // Récupérer et décoder les données des livres avec debug
            $books_json = get_post_meta($author_id, 'auteur_books_data', true);
            
            // DEBUG : Afficher les informations de debug
            if (current_user_can('manage_options')) {
                echo '<div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">';
                echo '<strong>🔍 Debug (visible seulement pour les admins) :</strong><br>';
                echo '<strong>Post ID:</strong> ' . $author_id . '<br>';
                echo '<strong>Meta key:</strong> auteur_books_data<br>';
                echo '<strong>Raw data length:</strong> ' . strlen($books_json) . ' caractères<br>';
                if (!empty($books_json)) {
                    echo '<strong>Premiers 200 caractères:</strong><br>';
                    echo '<code>' . esc_html(substr($books_json, 0, 200)) . '...</code><br>';
                } else {
                    echo '<strong>⚠️ Données vides ou inexistantes</strong><br>';
                }
                echo '</div>';
            }
            
            if (empty($books_json)) {
                echo '<div class="no-books">📚 Aucune donnée de livre disponible pour cet auteur.</div>';
                
                // DEBUG supplémentaire : vérifier toutes les meta keys
                if (current_user_can('manage_options')) {
                    $all_meta = get_post_meta($author_id);
                    echo '<div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">';
                    echo '<strong>🔍 Debug - Toutes les meta keys pour cet auteur :</strong><br>';
                    foreach ($all_meta as $key => $values) {
                        echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html(substr(print_r($values, true), 0, 100)) . '<br>';
                    }
                    echo '</div>';
                }
            } else {
                $data = json_decode($books_json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo '<div class="no-books">❌ Erreur lors du chargement des données des livres.</div>';
                    
                    // DEBUG : Afficher l'erreur JSON
                    if (current_user_can('manage_options')) {
                        echo '<div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">';
                        echo '<strong>🔍 Erreur JSON:</strong> ' . json_last_error_msg() . '<br>';
                        echo '<strong>Données brutes:</strong><br>';
                        echo '<code>' . esc_html($books_json) . '</code>';
                        echo '</div>';
                    }
                } else {
                    // DEBUG : Afficher la structure décodée
                    if (current_user_can('manage_options')) {
                        echo '<div style="background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">';
                        echo '<strong>🔍 Données décodées :</strong><br>';
                        echo '<strong>Séries trouvées:</strong> ' . (isset($data['series']) ? count($data['series']) : 0) . '<br>';
                        echo '<strong>Livres indépendants:</strong> ' . (isset($data['standalones']) ? count($data['standalones']) : 0) . '<br>';
                        if (!empty($data['series'])) {
                            foreach ($data['series'] as $index => $serie) {
                                echo '<strong>Série ' . ($index + 1) . ':</strong> ' . esc_html($serie['title'] ?? 'Sans titre') . ' (' . count($serie['books'] ?? []) . ' livres)<br>';
                            }
                        }
                        echo '</div>';
                    }
                    
                    $has_content = false;

                    // Afficher les séries
                    if (!empty($data['series'])) {
                        $has_content = true;
                        foreach ($data['series'] as $index => $serie) {
                            if (!empty($serie['books'])) {
                                ?>
                                <div class="series-section">
                                    <h2 class="series-title">📚 Ordre des livres de la série : <?php echo esc_html($serie['title']); ?></h2>
                                    <table class="books-table">
                                        <thead>
                                            <tr>
                                                <th>📖 Titre du livre</th>
                                                <th>📅 Date de publication</th>
                                                <th>🛒 Format Papier</th>
                                                <th>💻 Format Kindle</th>
                                                <th>🎧 Format Audible</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($serie['books'] as $book) : ?>
                                            <tr>
                                                <td class="book-title"><?php echo esc_html($book['title']); ?></td>
                                                <td class="book-date">
                                                    <?php
                                                    // Date de publication (sera ajoutée dans l'import CSV plus tard)
                                                    $pub_date = $book['publication_date'] ?? '';
                                                    echo !empty($pub_date) ? esc_html($pub_date) : '<span class="unavailable">À définir</span>';
                                                    ?>
                                                </td>
                                                <td class="book-link">
                                                    <?php if (!empty($book['amazon'])) : ?>
                                                        <a href="<?php echo esc_url($book['amazon']); ?>" target="_blank" rel="noopener" class="amazon-link">📚 Amazon</a>
                                                    <?php else : ?>
                                                        <span class="unavailable">Non disponible</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="book-link">
                                                    <?php if (!empty($book['kindle'])) : ?>
                                                        <a href="<?php echo esc_url($book['kindle']); ?>" target="_blank" rel="noopener" class="kindle-link">💻 Kindle</a>
                                                    <?php else : ?>
                                                        <span class="unavailable">Non disponible</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="book-link">
                                                    <?php if (!empty($book['audible'])) : ?>
                                                        <a href="<?php echo esc_url($book['audible']); ?>" target="_blank" rel="noopener" class="audible-link">🎧 Audible</a>
                                                    <?php else : ?>
                                                        <span class="unavailable">Non disponible</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                            }
                        }
                    }

                    // Afficher les livres indépendants
                    if (!empty($data['standalones'])) {
                        $has_content = true;
                        ?>
                        <div class="standalones-section">
                            <h2 class="series-title">📖 Œuvres indépendantes de <?php echo esc_html($author_name); ?></h2>
                            <table class="books-table">
                                <thead>
                                    <tr>
                                        <th>📖 Titre du livre</th>
                                        <th>📅 Date de publication</th>
                                        <th>🛒 Format Papier</th>
                                        <th>💻 Format Kindle</th>
                                        <th>🎧 Format Audible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['standalones'] as $book) : ?>
                                    <tr>
                                        <td class="book-title"><?php echo esc_html($book['title']); ?></td>
                                        <td class="book-date">
                                            <?php
                                            $pub_date = $book['publication_date'] ?? '';
                                            echo !empty($pub_date) ? esc_html($pub_date) : '<span class="unavailable">À définir</span>';
                                            ?>
                                        </td>
                                        <td class="book-link">
                                            <?php if (!empty($book['amazon'])) : ?>
                                                <a href="<?php echo esc_url($book['amazon']); ?>" target="_blank" rel="noopener" class="amazon-link">📚 Amazon</a>
                                            <?php else : ?>
                                                <span class="unavailable">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="book-link">
                                            <?php if (!empty($book['kindle'])) : ?>
                                                <a href="<?php echo esc_url($book['kindle']); ?>" target="_blank" rel="noopener" class="kindle-link">💻 Kindle</a>
                                            <?php else : ?>
                                                <span class="unavailable">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="book-link">
                                            <?php if (!empty($book['audible'])) : ?>
                                                <a href="<?php echo esc_url($book['audible']); ?>" target="_blank" rel="noopener" class="audible-link">🎧 Audible</a>
                                            <?php else : ?>
                                                <span class="unavailable">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    }

                    // Si aucune donnée de livre n'a été trouvée
                    if (!$has_content) {
                        echo '<div class="no-books">📚 Aucun livre enregistré pour cet auteur.</div>';
                    }
                }
            }
            ?>

            <!-- Biographie - maintenant après les tableaux -->
            <?php if (get_the_content()) : ?>
            <div class="auteur-bio">
                <h2>📖 Biographie</h2>
                <?php the_content(); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php
    endwhile;
endif;

get_footer();
?>