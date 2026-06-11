(function () {
  const select2Options = {
    allowClear: true,
    width: 'resolve'
  };

  function initializeSelect2() {
    const visibleSelects = Array.from(document.querySelectorAll('select.cariera-select2-search'))
      .filter(el => {
        const rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && window.getComputedStyle(el).display !== 'none';
      });

    visibleSelects.forEach(select => {
      const $sel = jQuery(select);
      if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
      }
      $sel.select2({
        placeholder: select.dataset.placeholder || '',
        allowClear: true,
        width: 'resolve'
      });
    });
  }

  function initializeCustomTooltips() {
    document.querySelectorAll('.job_filters, .resume_filters').forEach(form => {
      const processedFields = new Set();

      form.querySelectorAll('label').forEach(label => {
        const forId = label.getAttribute('for');
        if (!forId || processedFields.has(forId)) return;

        const field = form.querySelector(`#${forId}`);
        if (!field) return;

        const tooltipText = label.textContent.trim();
        field.removeAttribute('title');

        field.addEventListener('mouseenter', () => {
          if (document.querySelector('.custom-tooltip')) return;
          const tooltip = document.createElement('div');
          tooltip.className = 'custom-tooltip';
          tooltip.innerText = tooltipText;
          document.body.appendChild(tooltip);

          const rect = field.getBoundingClientRect();
          tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 8}px`;
          tooltip.style.left = `${rect.left + window.scrollX}px`;
        });

        field.addEventListener('mouseleave', () => {
          document.querySelectorAll('.custom-tooltip').forEach(t => t.remove());
        });

        processedFields.add(forId);
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const isMobile = window.matchMedia('(max-width: 767px)').matches;

    // Supprimer le bloc des tags sous forme de "nuage"
    document.querySelectorAll('.filter_wide.filter_by_tag').forEach(el => el.remove());

    // Cacher le mauvais TE selon device
    document.querySelectorAll('.te-desktop').forEach(el => el.style.display = isMobile ? 'none' : '');
    document.querySelectorAll('.te-mobile').forEach(el => el.style.display = isMobile ? '' : 'none');

    // Initialisation de Select2 avec délai (Elementor/Cariera)
    setTimeout(() => {
      initializeSelect2();
      initializeCustomTooltips();
    }, 500);

    // Observer les mutations DOM
    const observer = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        if (mutation.addedNodes.length) {
          const newSelect2Elements = Array.from(mutation.addedNodes).some(node =>
            node.nodeType === 1 && (node.matches('select.cariera-select2-search') || node.querySelector('select.cariera-select2-search'))
          );
          const newAdvancedFields = Array.from(mutation.addedNodes).some(node =>
            node.nodeType === 1 && (node.matches('.advanced-search-filters') || node.querySelector('.advanced-search-filters'))
          );

          if (newSelect2Elements || newAdvancedFields) {
            initializeSelect2();
            initializeCustomTooltips();
          }
        }
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    const forms = document.querySelectorAll('.job_filters, .resume_filters');

    forms.forEach((form, index) => {
      const isJobForm = form.classList.contains('job_filters');
      const formType = isJobForm ? 'filters' : 'resume';
      const toggleBtnId = `toggle-advanced-${formType}-${index}`;
      const wrapperId = `advanced-search-${formType}-${index}`;

      form.querySelectorAll('#toggle-advanced-filters, #advance-search, .advanced-btn, [id^="toggle-advanced-"]').forEach(btn => btn.remove());

      const categoryField = form.querySelector('.search_categories');
      if (!categoryField) return;

      let btn = form.querySelector(`#${toggleBtnId}`);
      if (!btn) {
        btn = document.createElement('a');
        btn.href = '#';
        btn.id = toggleBtnId;
        btn.className = 'button advanced-btn';
        btn.textContent = 'Recherche avancée';
        categoryField.parentNode.insertBefore(btn, categoryField.nextSibling);
      }

      const advancedFieldsSelector = isJobForm
        ? '.search_salary_min, .search_salary_max, .search_tag_list, .search_by_rate, .search_skills, .search_location_field'
        : '.search_salary_min, .search_salary_max, .search_tag_list, .search_experience, .search_education';
      const advancedFields = form.querySelectorAll(advancedFieldsSelector);
      let wrapper = form.querySelector('.advanced-search-filters');

      if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.id = wrapperId;
        wrapper.className = 'advanced-search-filters';
        wrapper.style.display = 'none';
        btn.parentNode.insertBefore(wrapper, btn.nextSibling);
      }

      advancedFields.forEach(field => {
        if (field && !wrapper.contains(field)) {
          wrapper.appendChild(field);
        }
      });

      btn.addEventListener('click', e => {
        e.preventDefault();
        const isVisible = wrapper.style.display === 'block';
        wrapper.style.display = isVisible ? 'none' : 'block';
        wrapper.classList.toggle('active', !isVisible);
        form.classList.toggle('show-advanced', !isVisible);
        btn.classList.toggle('active', !isVisible);
        btn.textContent = isVisible ? 'Recherche avancée' : 'Masquer les filtres';

        if (!isVisible) {
          initializeSelect2();
          initializeCustomTooltips();
        }
      });
    });
  });
})();