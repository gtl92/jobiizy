document.addEventListener('DOMContentLoaded', function () {
  // Traiter tous les formulaires de filtres CV
  const forms = document.querySelectorAll('.resume_filters');

  forms.forEach((form, index) => {
    if (!form) return;

    // Générer un ID unique pour chaque formulaire
    const toggleBtnId = `toggle-advanced-resume-${index}`;
    const wrapperId = `advanced-resume-filters-${index}`;

    // Vérifier si le bouton existe déjà
    if (form.querySelector(`#${toggleBtnId}`)) return;

    // Retirer la classe show-advanced par défaut
    form.classList.remove('show-advanced');

    const categoryField = form.querySelector('.search_categories');
    if (!categoryField) return;

    // Supprimer d'anciens boutons pour éviter les doublons - étendu
    const existingBtns = form.querySelectorAll('#toggle-advanced-filters, #advance-search, .advanced-btn, [id^="toggle-advanced-"]');
    existingBtns.forEach(btn => btn.remove());

    // Créer le bouton
    const btn = document.createElement('a');
    btn.href = '#';
    btn.id = toggleBtnId;
    btn.className = 'button advanced-btn';
    btn.textContent = 'Recherche avancée';

    // L'insérer après le champ catégorie
    categoryField.parentNode.insertBefore(btn, categoryField.nextSibling);

    // Sélectionner les champs avancés - étendu pour les CV
    const advancedFields = form.querySelectorAll(
      '.search_salary_min, .search_salary_max, .search_by_rate, .search_skills, .search_tag_list, .search_experience, .search_education'
    );

    // Vérifier s'il existe déjà un wrapper
    let wrapper = form.querySelector('.advanced-search-filters');
    if (wrapper) {
      wrapper.id = wrapperId;
      wrapper.style.display = 'none';
      advancedFields.forEach(field => {
        if (field && field.parentNode && !wrapper.contains(field)) {
          wrapper.appendChild(field);
        }
      });
    } else if (advancedFields.length > 0) {
      wrapper = document.createElement('div');
      wrapper.id = wrapperId;
      wrapper.className = 'advanced-search-filters';
      wrapper.style.display = 'none';

      advancedFields.forEach(field => {
        if (field && field.parentNode) {
          wrapper.appendChild(field);
        }
      });

      // Insérer après le bouton
      btn.parentNode.insertBefore(wrapper, btn.nextSibling);
    }

    // Gestion du clic avec gestion de la classe active
    if (wrapper) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const isVisible = wrapper.style.display === 'block';
        wrapper.style.display = isVisible ? 'none' : 'block';
        btn.textContent = isVisible ? 'Recherche avancée' : 'Masquer les filtres';

        // Toggle de la classe active sur le bouton pour les styles CSS
        if (isVisible) {
          wrapper.classList.remove('active');
          form.classList.remove('show-advanced');
          btn.classList.remove('active'); // Ajout - retirer classe active
        } else {
          wrapper.classList.add('active');
          form.classList.add('show-advanced');
          btn.classList.add('active'); // Ajout - ajouter classe active
        }
      });
    }
  });
});