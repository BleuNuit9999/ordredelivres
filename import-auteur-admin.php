<?php
/**
 * Import CSV des auteurs - Solution 100% WordPress native
 */

function process_auteur_csv_import($csv_file) {
    // Vérifications de base
    if (!file_exists($csv_file) || filesize($csv_file) == 0) {
        echo '<div class="notice notice-error"><p>Fichier CSV introuvable ou vide.</p></div>';
        return;
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) {
        echo '<div class="notice notice-error"><p>Impossible d\'ouvrir le fichier CSV.</p></div>';
        return;
    }

    // Lecture des en-têtes
    $headers = fgetcsv($handle, 0, ',');
    if (!$headers) {
        echo '<div class="notice notice-error"><p>Impossible de lire les en-têtes du fichier.</p></div>';
        fclose($handle);
        return;
    }

    // Nettoyer les en-têtes
    $headers = array_map(function($header) {
        return trim($header, " \t\n\r\0\x0B\xEF\xBB\xBF");
    }, $headers);

    // Vérifier que post_title existe
    if (!in_array('post_title', $headers)) {
        echo '<div class="notice notice-error"><p>La colonne "post_title" est obligatoire.</p></div>';
        fclose($handle);
        return;
    }

    echo '<div class="notice notice-info"><p>Début de l\'import...</p></div>';

    $imported = 0;
    $errors = 0;
    $row_number = 1;

    // Traitement ligne par ligne
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $row_number++;
        
        // Ignorer les lignes vides
        if (empty(array_filter($row))) continue;

        // Vérifier la cohérence des colonnes
        if (count($row) !== count($headers)) {
            echo '<div class="notice notice-warning"><p>Ligne ' . $row_number . ' : nombre de colonnes incorrect, ignorée.</p></div>';
            $errors++;
            continue;
        }

        // Combiner en-têtes et données
        $data = array_combine($headers, $row);
        $title = trim($data['post_title'] ?? '');

        if (empty($title)) {
            echo '<div class="notice notice-warning"><p>Ligne ' . $row_number . ' : titre manquant, ignorée.</p></div>';
            $errors++;
            continue;
        }

        // Vérifier si l'auteur existe déjà
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
            // Créer le nouvel auteur
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

        // Gestion des genres
        if (!empty($data['genre'])) {
            $genres = array_map('trim', explode('|', $data['genre']));
            wp_set_post_terms($post_id, $genres, 'genre_auteur');
        }

        // Traitement des données des livres
        $books_data = [
            'series' => [],
            'standalones' => []
        ];

        // Traitement des séries (1 à 10)
        for ($s = 1; $s <= 10; $s++) {
            $serie_title = trim($data["serie_{$s}_title"] ?? '');
            if (empty($serie_title)) continue;

            $serie = [
                'title' => sanitize_text_field($serie_title),
                'books' => []
            ];

            // Traitement des livres de la série (1 à 20)
            for ($b = 1; $b <= 20; $b++) {
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

        // Traitement des livres indépendants (1 à 20)
        for ($st = 1; $st <= 20; $st++) {
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

        // Sauvegarder les données des livres
        $json_data = wp_json_encode($books_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        update_post_meta($post_id, 'auteur_books_data', $json_data);

        echo '<div class="notice notice-success"><p>✅ Auteur "' . esc_html($title) . '" importé/mis à jour avec succès!</p></div>';
        $imported++;
    }

    fclose($handle);

    // Résumé final
    echo '<div class="notice notice-success"><h3>Import terminé!</h3>';
    echo '<p><strong>' . $imported . '</strong> auteur(s) traité(s), <strong>' . $errors . '</strong> erreur(s).</p>';
    echo '<p><a href="' . admin_url('edit.php?post_type=auteur') . '" class="button button-primary">Voir les auteurs importés</a></p>';
    echo '</div>';

    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}