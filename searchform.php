<?php
/**
 * Formulaire de recherche - Ordre-Livres.fr
 */
?>
<form role="search" method="get" class="custom-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="search-wrapper">
		<span class="search-icon">🔍</span>
		<input 
			type="search" 
			class="search-input" 
			placeholder="Cherchez un auteur : Stephen King, Agatha Christie..." 
			value="<?php echo get_search_query(); ?>" 
			name="s"
			autocomplete="off"
		>
	</div>
</form>

<style>
/* Container principal */
.custom-search-form {
	width: 100%;
	flex: 1;
	max-width: 600px;
	margin: 0;
}

/* Wrapper - FORCE les angles arrondis */
.search-wrapper {
	display: flex;
	align-items: center;
	background: white;
	border-radius: 25px !important; /* 👈 FORCE */
	overflow: hidden !important; /* 👈 CRITIQUE pour cacher les angles de l'input */
	padding: 10px 20px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
	border: none;
	height: 45px;
}

.search-wrapper:hover {
	box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}

.search-wrapper:focus-within {
	box-shadow: 0 3px 12px rgba(255,107,53,0.25);
}

/* Icône loupe */
.search-icon {
	font-size: 20px;
	margin-right: 12px;
	color: #666;
	flex-shrink: 0;
}

/* Input SANS bordure ni angles */
.search-input {
	flex: 1;
	border: none !important;
	outline: none !important;
	padding: 0;
	font-size: 15px;
	background: transparent;
	color: #333;
	min-width: 0;
	width: 100%;
	border-radius: 0 !important; /* 👈 Pas d'angles sur l'input lui-même */
	box-shadow: none !important;
	-webkit-appearance: none !important;
	-moz-appearance: none !important;
	appearance: none !important;
}

.search-input::placeholder {
	color: #999;
	font-size: 14px;
}

/* Supprime TOUS les styles par défaut */
.search-input::-webkit-search-cancel-button,
.search-input::-webkit-search-decoration,
.search-input::-webkit-search-results-button,
.search-input::-webkit-search-results-decoration {
	display: none;
}

.search-input:focus {
	border: none !important;
	outline: none !important;
	box-shadow: none !important;
}

/* Responsive */
@media (max-width: 1200px) {
	.custom-search-form {
		max-width: 500px;
	}
}

@media (max-width: 992px) {
	.custom-search-form {
		max-width: 400px;
	}
	
	.search-input {
		font-size: 14px;
	}
}

@media (max-width: 768px) {
	.custom-search-form {
		max-width: 100%;
		flex: 1;
	}
	
	.search-wrapper {
		padding: 8px 15px;
		height: 42px;
	}
	
	.search-icon {
		font-size: 18px;
		margin-right: 10px;
	}
	
	.search-input {
		font-size: 13px;
	}
}

@media (max-width: 480px) {
	.search-wrapper {
		padding: 6px 12px;
		height: 38px;
		border-radius: 20px !important;
	}
	
	.search-icon {
		font-size: 16px;
	}
}
</style>