/**
 * Filter accessible course activities by name.
 *
 * @module format_ucl/local/search
 */

/**
 * Initialise the search field and its server-rendered result links.
 */
export const init = () => {
    const root = document.querySelector('[data-region="ucl-course-search"]');
    if (!root || root.dataset.initialised) {
        return;
    }
    root.dataset.initialised = 'true';

    const navigation = document.querySelector('.secondary-navigation');
    const field = root.querySelector('[data-region="search-field"]');
    const input = root.querySelector('input[type="search"]');
    if (navigation && field) {
        navigation.append(field);
    }

    const list = root.querySelector('#ucl-course-search-results');
    const status = root.querySelector('[data-region="search-status"]');
    const normalize = value => value.toLocaleLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
    const activities = Array.from(list.querySelectorAll('[data-search-name]'), element => ({
        element,
        name: normalize(element.dataset.searchName),
    }));

    // Focus the activity target after page load or a module-fragment change.
    const focusActivity = () => {
        const hash = window.location.hash;
        if (!/^#module-\d+$/.test(hash)) {
            return;
        }
        const activity = document.getElementById(hash.slice(1));
        if (!activity) {
            return;
        }
        const focustarget = activity.querySelector('.activity-item') ?? activity;
        if (!focustarget.hasAttribute('tabindex')) {
            focustarget.setAttribute('tabindex', '-1');
        }
        const name = activity.querySelector('.activityname a[href]');
        if (name && !focustarget.hasAttribute('aria-label') && !focustarget.hasAttribute('aria-labelledby')) {
            focustarget.setAttribute('aria-label', name.textContent.trim());
        }
        focustarget.focus({preventScroll: true});
    };

    // Covers navigation from another page and in-page fragment changes.
    window.addEventListener('hashchange', () => window.requestAnimationFrame(focusActivity));
    window.requestAnimationFrame(focusActivity);

    const update = () => {
        const terms = normalize(input.value.trim()).split(/\s+/).filter(Boolean);
        let count = 0;
        activities.forEach(activity => {
            const matches = terms.length > 0 && terms.every(term => activity.name.includes(term));
            activity.element.classList.toggle('d-none', !matches);
            if (matches) {
                count++;
            }
        });
        list.classList.toggle('d-none', count === 0);
        if (terms.length === 0) {
            status.textContent = '';
        } else if (count > 0) {
            status.textContent = status.dataset.resultsTemplate.replace('__COUNT__', String(count));
        } else {
            status.textContent = status.dataset.noResults;
        }
    };

    input.addEventListener('input', update);
    list.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
                || !link) {
            return;
        }
        input.value = '';
        update();
        const destination = new URL(link.href, window.location.href);
        // Clicking the exact current URL does not emit hashchange.
        if (destination.href === window.location.href) {
            window.requestAnimationFrame(focusActivity);
        }
    });
    update();
};
